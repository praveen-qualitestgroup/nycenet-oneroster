<?php

namespace App\Services;

use App\Models\Schools;
use App\Models\Districts;
use App\Providers\HttpServiceProvider;
use Carbon\Carbon;
use App\Services\DistrictService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class SchoolService
{
    /**
     * Redis key for school
     */
    const REDIS_FIELD_KEY = 'school:';

    /**
     * HttpServiceProvider instance
     */
    public $httpServiceProvider;

    /**
     * ClassService instance
     */
    public $classService;

    /**
     * UserService instance
     */
    public $userService;

    /**
     * Create a construct function
     * 
     * @param HttpServiceProvider $httpServiceProvider
     * @param ClassService $classService
     * @param UserService $userService
     * 
     */
    public function __construct(HttpServiceProvider $httpServiceProvider, ClassService $classService, UserService $userService)
    {
        $this->httpServiceProvider = $httpServiceProvider;
        $this->classService = $classService;
        $this->userService = $userService;
    }

    /**
     * Sync schools from edlink api
     * 
     * @param string $extDistrictId
     * @param string $accessToken
     * 
     * @return void
     */
    public function syncSchool(array $school): void
    {
        Redis::pipeline(function ($pipe) use ($school) {
        if(is_null(Redis::get(static::REDIS_FIELD_KEY.$school['sourcedId'])) ||
        (!is_null(Redis::get(static::REDIS_FIELD_KEY.$school['sourcedId'])) &&
        Redis::get(static::REDIS_FIELD_KEY.$school['sourcedId']) !== $school['dateLastModified']))
        {
            //Insert or update the hash and db value
            $schoolData = Schools::withTrashed()->updateOrCreate(
                [
                 'ext_school_id' => trim($school['sourcedId'])
                ],
                [
                    'ext_school_id' => trim($school['sourcedId']),
                    'identifier' => trim($school['identifier']),
                    'name' => $school['name'],
                    'status' => $school['status'] == 'active' ? 1 : 0,
                    'ext_updated_at' => Carbon::parse($school['dateLastModified']),
                    'deleted_at' => NULL,
                ]
            );
            
            //After database entry is made set the Key
            $pipe->set(DistrictService::REDIS_SCHOOL_ID_KEY.trim($school['sourcedId']),$schoolData->id);
            $pipe->set(static::REDIS_FIELD_KEY. $school['sourcedId'], $school['dateLastModified']);
            Log::debug('Data for School with Id: ' . $school['sourcedId'] . ' and name: ' . $school['name'] . ' updated/created successfully');
        } else {
            Log::debug('Redis Key ' . static::REDIS_FIELD_KEY . $school['sourcedId'] . ' already exists.');
        }
    });
    }
}
