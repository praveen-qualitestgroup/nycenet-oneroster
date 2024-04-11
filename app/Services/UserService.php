<?php

namespace App\Services;

use App\Models\Schools;
use App\Models\User;
use App\Models\Classes;
use App\Models\Students;
use App\Models\StudentClass;
use App\Models\UserClass;
use App\Providers\HttpServiceProvider;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class UserService
{
    /**
     * Redis key for student
     */
    const REDIS_STUDENT_FIELD_KEY = 'student:';
    const REDIS_TEACHER_FIELD_KEY = 'teacher:';

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
    public function syncUsers(array $user): void
    {
        Redis::pipeline(function ($pipe) use ($user) {
            Log::info("Syncing Users data from oneRoster API");
            if($user['role'] === self::TEACHER){
               $this->syncTeacherData($user,$pipe);
            }else if($user['role'] === self::STUDENT){
                die("WTF am i doing here");
            }
        });
    }
    
    public function syncTeacherData(array $user,$pipe){
        //if either the Teacher's last modified date is changed or don't exists
        if(is_null(Redis::get(static::REDIS_TEACHER_FIELD_KEY.trim($user['sourcedId']))) ||
        (!is_null(Redis::get(static::REDIS_TEACHER_FIELD_KEY.trim($user['sourcedId']))) &&
        Redis::get(static::REDIS_TEACHER_FIELD_KEY.trim($user['sourcedId'])) !== $user['dateLastModified'])){
               if (Students::where('email', $user['email'])->first()){
            Log::info("Teacher with email:" . $user['email'] . ' already exists in the db.');
        } else {
            User::withTrashed()->updateOrCreate(
                [
                 'ext_user_id' => trim($user['sourcedId'])
                ],
                [
                    'ext_user_id' => trim($user['sourcedId']),
                    'identifiers' => trim($user['sourcedId']),
                    'status' => $user['status'] === 'active' ? 1 : 0,
                    'first_name' => isset($user['givenName']) ? $user['givenName'] : NULL,
                    'middle_name' => isset($user['middleName']) ? $user['middleName'] : NULL,
                    'last_name' => isset($user['familyName']) ? $user['familyName'] : NULL,
                    'email' => $user['email'],
                    'created_at' => Carbon::now(),
                    'school_id' => (Redis::get(DistrictService::REDIS_SCHOOL_ID_KEY.trim($user['orgs'][0]['sourcedId']))) ?? NULL,
                    'user_type' => $user['role'],
                    'ext_updated_at' => Carbon::parse($user['dateLastModified']),
                    'deleted_at' => NULL,
                ]
            );
            $pipe->set(static::REDIS_TEACHER_FIELD_KEY.trim($user['sourcedId']),$user['dateLastModified']);
        }
       }
    }
}

