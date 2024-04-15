<?php

namespace App\Services;

use App\Models\Schools;
use App\Models\Classes;
use App\Providers\HttpServiceProvider;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class ClassService
{
    /**
     * Redis key for class
     */
    const REDIS_CLASS_FIELD_KEY = 'class:';

    /**
     * HttpServiceProvider instance
     */
    public $httpServiceProvider;

    /**
     * Create a construct function
     * 
     * @param HttpServiceProvider $httpServiceProvider
     */
    public function __construct(HttpServiceProvider $httpServiceProvider)
    {
        $this->httpServiceProvider = $httpServiceProvider;
    }

    /**
     * Sync classes from edlink api
     * 
     * @param string $extSchoolId
     * @param string$accessToken
     * 
     * @return void
     */
    public function syncClasses($class): void
    {

        $this->httpServiceProvider->setAccessToken($accessToken);
        $classes = $this->httpServiceProvider->getResponse('schools/' . $extSchoolId . '/classes?$first=1000');

        $schoolId = Schools::where('ext_school_id', $extSchoolId)->exists() ? Schools::where('ext_school_id', $extSchoolId)->first()->id : NULL;
        $existingClasses = Classes::where('school_id', $schoolId)->exists() ? Classes::where('school_id', $schoolId)->pluck('ext_class_id')->toArray() : [];

        if (count($classes)) {
            Log::info("Syncing Classes data from Edlink API for School ID:" . $schoolId);

            Redis::pipeline(function ($pipe) use ($classes, $schoolId, $existingClasses, $extSchoolId) {
                $newClasses = [];
                foreach ($classes as $key => $class) {
                    $newClasses[$key] = $class['id'];
                    $classHash = md5(json_encode($class));
                    if (
                        !Redis::exists(static::REDIS_CLASS_FIELD_KEY . $extSchoolId . '-' . $class['id']) ||
                        (Redis::exists(static::REDIS_CLASS_FIELD_KEY . $extSchoolId . '-' . $class['id']) &&
                            (Redis::get(static::REDIS_CLASS_FIELD_KEY . $extSchoolId . '-' . $class['id']) !== $classHash)
                        )
                    ) {
                        //Insert or update the hash and db value
                        $pipe->set(static::REDIS_CLASS_FIELD_KEY . $extSchoolId . ' ' . $class['id'], $classHash);

                        Classes::withTrashed()->updateOrCreate(
                            [
                                'ext_class_id' => $class['id'],
                                'school_id' => $schoolId
                            ],
                            [
                                'name' => $class['name'],
                                'school_id' => $schoolId,
                                'grade' => implode(",", $class['grade_levels']),
                                'ext_updated_at' => Carbon::parse($class['updated_date']),
                                'deleted_at' => NULL
                            ]
                        );
                        Log::debug('Data for Class with ID: ' . $class['id'] . ' and name: ' . $class['name'] . ' created/updated successfully');
                    } else {
                        Log::debug('Redis Key' . static::REDIS_CLASS_FIELD_KEY . $class['id'] . ' with Class ID: ' . $class['id'] . ' already exists.');
                    }

                }

                // delete classes which are not in edlink
                $deletedClasses = array_diff($existingClasses, $newClasses);
                if (!empty($deletedClasses)) {
                    foreach ($deletedClasses as $key => $value) {
                        Redis::del(static::REDIS_CLASS_FIELD_KEY . $extSchoolId . '-' . $value);
                    }
                    Classes::whereIn('ext_class_id', $deletedClasses)->delete();
                    Log::debug("Deleted Classes data: " . json_encode($deletedClasses) . " for School ID:" . $schoolId);
                }
            });
        } else {
            Log::info("No Classes found for School ID:" . $schoolId);
        }
    }
}
