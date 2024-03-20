<?php

namespace App\Services;

use App\Models\Schools;
use App\Models\User;
use App\Models\Classes;
use App\Models\Student;
use App\Models\StudentClass;
use App\Models\UserClass;
use App\Providers\HttpServiceProvider;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class UserService
{
    /**
     * Redis key for user
     */
    const REDIS_USER_FIELD_KEY = 'user:';
    /**
     * Redis key for student
     */
    const REDIS_STUDENT_FIELD_KEY = 'student:';

    const SCHOOL_ADMIN = "school_admin";
    const TEACHER = "teacher";
    const STAFF = "staff";
    const OBSERVER = "observer";
    const STUDENT = "student";
    const DISTRICT_ADMIN = "district-administrator";
    const ADMINISTRATOR = "administrator";

    /**
     * HttpServiceProvider instance
     */
    public $httpServiceProvider;

    /**
     * Create a construct function
     * 
     * @param HttpServiceProvider $httpServiceProvider
     * 
     */
    public function __construct(HttpServiceProvider $httpServiceProvider)
    {
        $this->httpServiceProvider = $httpServiceProvider;
    }

    /**
     * Sync users from edlink api
     * 
     * @param string $extSchoolId
     * @param string $accessToken
     * 
     * @return void
     */
    public function syncUsers(string $extSchoolId, string $accessToken): void
    {

        $this->httpServiceProvider->setAccessToken($accessToken);
        $users = $this->httpServiceProvider->getResponse('schools/' . $extSchoolId . '/people?$first=1000');
        $schoolId = Schools::where('ext_school_id', $extSchoolId)->exists() ? Schools::where('ext_school_id', $extSchoolId)->first()->id : NULL;

        // $existingUsers = User::where('school_id', $schoolId)->first() ? User::where('school_id', $schoolId)->pluck('ext_user_id')->toArray() : [];
        // $existingStudents = Student::where('school_id', $schoolId)->first() ? Student::where('school_id', $schoolId)->pluck('ext_student_id')->toArray() : [];

        Redis::pipeline(function ($pipe) use ($users, $accessToken, $schoolId) {
            // $newUsers = [];
            // $newStudents = [];
            if (count($users)) {
                Log::info("Syncing Users data from Edlink API for School ID:" . $schoolId);
                foreach ($users as $key => $user) {

                    if (User::where('email', $user['email'])->first()) {
                        Log::info("The User for email:" . $user['email'] . ' already exists in the db.');
                    } else {
                        if (in_array(self::STUDENT, $user['roles'])) {
                            $role = self::STUDENT;
                        }
                        if (in_array(self::TEACHER, $user['roles'])) {
                            $role = self::TEACHER;
                        }
                        if (in_array(self::STAFF, $user['roles'])) {
                            $role = self::TEACHER;
                        }
                        if (in_array(self::OBSERVER, $user['roles'])) {
                            $role = self::TEACHER;
                        }
                        if (in_array(self::DISTRICT_ADMIN, $user['roles'])) {
                            $role = self::SCHOOL_ADMIN;
                        }
                        if (in_array(self::ADMINISTRATOR, $user['roles'])) {
                            $role = self::SCHOOL_ADMIN;
                        }

                        if (empty($role)) {
                            Log::info("The role found for edlink User ID:" . $user['id'] . ' are not supported in LMS. The roles are: ' . json_encode($user['roles']));
                            continue;
                        }


                        if ($role == self::SCHOOL_ADMIN || $role == self::TEACHER) {
                            //$newUsers[$key] = $user['id'];
                            $userHash = md5(json_encode($user));
                            if (
                                !Redis::exists(static::REDIS_USER_FIELD_KEY . $user['id']) ||
                                (Redis::exists(static::REDIS_USER_FIELD_KEY . $user['id']) &&
                                    (Redis::get(static::REDIS_USER_FIELD_KEY . $user['id']) !== $userHash)
                                )
                            ) {
                                //Insert update the hash and db value
                                $pipe->set(static::REDIS_USER_FIELD_KEY . $user['id'], $userHash);

                                $updatedUser = User::withTrashed()->updateOrCreate(
                                    [
                                        // 'ext_user_id' => $user['id'],
                                        // 'school_id' => $schoolId,
                                        'email' => $user['email']
                                    ],
                                    [
                                        'ext_user_id' => $user['id'],
                                        'school_id' => $schoolId,
                                        'first_name' => $user['first_name'],
                                        'middle_name' => $user['middle_name'],
                                        'last_name' => $user['last_name'],
                                        'email' => $user['email'],
                                        'user_type' => $role,
                                        'ext_user_type' => implode(",", $user['roles']),
                                        'ext_updated_at' => Carbon::parse($user['updated_date']),
                                        'roster_provider_name' => 'edlink',
                                        'deleted_at' => NULL,
                                        'identifiers' => json_encode($user['identifiers']),
                                        'status' => 1
                                    ]
                                );
                                Log::debug('Data for User with ID: ' . $user['id'] . ' and name: ' . $user['first_name'] . ' ' . $user['last_name'] . ' updated/created successfully');

                                if (!empty($updatedUser)) {
                                    $userId = $updatedUser->id;
                                } else if (User::where('ext_user_id', $user['id'])->first()) {
                                    $userId = User::where('ext_user_id', $user['id'])->first()->id;
                                } else {
                                    Log::debug('User Id: ' . $user['id'] . ' neither exist in DB nor created.');
                                }

                                //CURD operation for user classes
                                $newUserClasses = [];
                                $existingUserClasses = UserClass::where('user_id', $userId)->exists() ? UserClass::where('user_id', $userId)->pluck('classroom_id')->toArray() : [];
                                $this->httpServiceProvider->setAccessToken($accessToken);
                                $userClassData = $this->httpServiceProvider->getResponse('people/' . $user['id'] . '/enrollments');
                                if (!empty($userClassData)) {
                                    foreach ($userClassData as $key => $userClass) {
                                        if (Classes::where('ext_class_id', '=', $userClass['class_id'])->exists()) {
                                            $classId = Classes::where('ext_class_id', '=', $userClass['class_id'])->exists() ? Classes::where('ext_class_id', '=', $userClass['class_id'])->first()->id : NULL;
                                            $newUserClasses[$key] = $classId;

                                            Log::debug('Add User Classes with user Id:' . $userId . ' and Class Id:' . $classId);
                                            UserClass::withTrashed()->updateOrCreate(
                                                [
                                                    "user_id" => $userId,
                                                    "classroom_id" => $classId
                                                ],
                                                [
                                                    'deleted_at' => NULL
                                                ]
                                            );

                                        }
                                    }

                                } else {
                                    Log::debug("No User Classes data found for user id: " . $userId);
                                }
                                $deletedUserClasses = array_diff($existingUserClasses, $newUserClasses);
                                if (!empty($deletedUserClasses)) {
                                    UserClass::whereIn('class_id', $deletedUserClasses)->where('user_id', $userId)->delete();
                                    Log::debug("Deleted User Class data:" . json_encode($deletedUserClasses));
                                }
                            } else {
                                Log::debug("Redis Key for User ID: " . $user['id'] . " already exists with other school.");
                            }


                        } else {
                            Log::debug("User with ID: " . $schoolId . "-" . $user['id'] . " is a student.");
                            // $newStudents[$key] = $user['id'];
                            $studentHash = md5(json_encode($user));
                            if (
                                !Redis::exists(static::REDIS_STUDENT_FIELD_KEY . $user['id']) ||
                                (Redis::exists(static::REDIS_STUDENT_FIELD_KEY . $user['id']) &&
                                    (Redis::get(static::REDIS_STUDENT_FIELD_KEY . $user['id']) !== $studentHash)
                                )
                            ) {
                                //Insert update the hash and db value
                                $pipe->set(static::REDIS_STUDENT_FIELD_KEY . $user['id'], $studentHash);

                                $updatedStudent = Student::withTrashed()->updateOrCreate(
                                    [
                                        // 'ext_student_id' => $user['id'],
                                        // 'school_id' => $schoolId
                                        'email' => $user['email']
                                    ],
                                    [

                                        'ext_user_id' => $user['id'],
                                        'name' => $user['first_name'] . ' ' . $user['middle_name'] . ' ' . $user['last_name'],
                                        'email' => $user['email'],
                                        'school_id' => $schoolId,
                                        'roster_provider_name' => 'edlink',
                                        'maxtries' => 0,
                                        'user_id' => 0,
                                        'ext_updated_at' => Carbon::parse($user['updated_date']),
                                        'deleted_at' => NULL,
                                        'identifiers' => json_encode($user['identifiers']),
                                        'lesson_group_id' => 0,
                                        'login_code' => 0
                                    ]
                                );
                                Log::debug('Data for Student with ID: ' . $user['id'] . ' and name: ' . $user['first_name'] . ' ' . $user['last_name'] . ' updated/created successfully');

                                if (!empty($updatedStudent)) {
                                    $studentId = $updatedStudent->id;
                                } else if (Student::where('ext_student_id', $user['id'])->first()) {
                                    $studentId = Student::where('ext_student_id', $user['id'])->first()->id;
                                } else {
                                    Log::debug('student Id: ' . $user['id'] . ' neither exist in DB nor created.');
                                }


                                //CURD operation for student classes
                                $newStudentClasses = [];
                                $existingSudentClasses = StudentClass::where('student_id', $studentId)->exists() ? StudentClass::where('student_id', $studentId)->pluck('classroom_id')->toArray() : [];
                                $this->httpServiceProvider->setAccessToken($accessToken);
                                $studentClassData = $this->httpServiceProvider->getResponse('people/' . $user['id'] . '/enrollments');
                                if (!empty($studentClassData)) {
                                    foreach ($studentClassData as $key => $studentClass) {
                                        if (Classes::where('ext_class_id', '=', $studentClass['class_id'])->exists()) {
                                            $classId = Classes::where('ext_class_id', '=', $studentClass['class_id'])->exists() ? Classes::where('ext_class_id', '=', $studentClass['class_id'])->first()->id : NULL;
                                            $newStudentClasses[$key] = $classId;
                                            Log::debug('Add Student Classes with user Id:' . $studentId . ' and Class Id:' . $classId);
                                            StudentClass::withTrashed()->updateOrCreate(
                                                [
                                                    "student_id" => $studentId,
                                                    "classroom_id" => $classId
                                                ],
                                                [
                                                    'deleted_at' => NULL
                                                ]
                                            );

                                        }
                                    }

                                } else {
                                    Log::debug("No Student Classes data found for student id: " . $studentId);
                                }
                                $deletedStudentClasses = array_diff($existingSudentClasses, $newStudentClasses);
                                if (!empty($deletedStudentClasses)) {
                                    StudentClass::whereIn('class_id', $deletedStudentClasses)->where('student_id', $studentId)->delete();
                                    Log::debug("Deleted Student Class data:" . json_encode($deletedStudentClasses));
                                }

                            } else {
                                Log::debug("Redis Key for Student ID: " . $user['id'] . " already exists for other school.");
                            }

                        }
                    }


                }
                //delete users which are not in edlink
                // $deletedUsers = array_diff($existingUsers, $newUsers);
                // if (!empty($deletedUsers)) {
                //     foreach ($deletedUsers as $key => $value) {
                //         Redis::del(static::REDIS_USER_FIELD_KEY . $value);
                //     }
                //     User::whereIn('ext_user_id', $deletedUsers)->delete();
                //     Log::debug("Deleted Users data:" . json_encode($deletedUsers));
                // }

                //delete students which are not in edlink
                // $deletedStudents = array_diff($existingStudents, $newStudents);
                // if (!empty($deletedStudents)) {
                //     foreach ($deletedStudents as $key => $value) {
                //         Redis::del(static::REDIS_STUDENT_FIELD_KEY . $value);
                //     }
                //     Student::whereIn('ext_student_id', $deletedStudents)->delete();
                //     Log::debug("Deleted Students data:" . json_encode($deletedStudents));
                // }
            } else {
                Log::info("No Users found for School ID:" . $schoolId);
            }

        });

    }
}
