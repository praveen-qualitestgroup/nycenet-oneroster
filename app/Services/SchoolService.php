<?php

namespace App\Services;

use App\Models\Schools;
use App\Models\Districts;
use App\Providers\HttpServiceProvider;
use Carbon\Carbon;
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
    public function syncSchool(array $school, string $accessToken): void
    {
        Redis::pipeline(function ($pipe) use ($school, $accessToken) {
            $schoolHash = md5(json_encode($school));
            //enter only if hash is changed
            if (
                !Redis::exists(static::REDIS_FIELD_KEY . $school['id']) ||
                (Redis::exists(static::REDIS_FIELD_KEY . $school['id']) &&
                    (Redis::get(static::REDIS_FIELD_KEY . $school['id']) !== $schoolHash)
                )
            ) {
                //Insert or update the hash and db value
                $pipe->set(static::REDIS_FIELD_KEY . $school['id'], $schoolHash);
                $districtId = Districts::where('ext_district_id', $school['district_id'])->exists() ? Districts::where('ext_district_id', $school['district_id'])->first()->id : NULL;
                foreach ($school['identifiers'] as $identifier) {
                    if ($identifier['type'] === 'sis_id') {
                        $code = $identifier['value'];
                        break;
                    }
                }


                Schools::withTrashed()->updateOrCreate(
                    ['ext_school_id' => $school['id']],
                    [
                        'district_id' => $districtId,
                        'name' => $school['name'],
                        'address' => $school['address']['unit'] . ' ' . $school['address']['street'] . ' ' . $school['location'] . ' ' . $school['address']['city'] . ' ' . $school['address']['state'] . ' ' . $school['address']['country'],
                        'city' => $school['address']['city'] ?? 'Not Available',
                        'state' => $school['address']['state'] ?? 'Not Available',
                        'zip' => $school['address']['postal_code'],
                        'phone' => $school['address']['phone'],
                        'ext_updated_at' => Carbon::parse($school['updated_date']),
                        'deleted_at' => NULL,
                        'region_id' => env('REGION_ID', 0),
                        'code' => $code ?? NULL,
                        'status' => 1
                    ]
                );
                Log::debug('Data for School with Id: ' . $school['id'] . ' and name: ' . $school['name'] . ' updated/created successfully');
            } else {
                Log::debug('Redis Key ' . static::REDIS_FIELD_KEY . $school['id'] . ' for School Id: ' . $school['id'] . ' already exists.');
            }

            $this->classService->syncClasses($school['id'], $accessToken);
            $this->userService->syncUsers($school['id'], $accessToken);

        });

    }
}
