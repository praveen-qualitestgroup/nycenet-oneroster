<?php

namespace App\Services;

use App\Models\Districts;
use App\Providers\HttpServiceProvider;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;
use App\Models\Schools;
use Illuminate\Support\Facades\Log;

function p($data){
    echo '<pre>';
    die(print_r($data));
}
class DistrictService
{
    /**
     * Redis key for district
     */
    public const REDIS_DISTRICT_FIELD_KEY = 'district:';
    public const REDIS_DISTRICT_ID_KEY = 'district_id_';
    public const REDIS_SCHOOL_ID_KEY = 'school_id_';
    /*
     * HttpServiceProvider instance
     */
    public $httpServiceProvider;
    private $newDistricts = [];

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
     * Sync Schools from oneRoster api
     * 
     * @param array $district
     * 
     * @return void
     */
    public function syncSchoolsForDistrict($district){
        $existingRelations = Schools::where('code',trim($district['sourcedId']))->pluck('ext_school_id')->toArray();
        forEach($district['children'] as $school){
            if($school['type'] === 'school' && !in_array(trim($school['sourcedId']),$existingRelations)){
                $districtId = Redis::get(static::REDIS_DISTRICT_ID_KEY.trim($district['sourcedId']));
                $schoolData = Schools::create([
                    'ext_school_id' => trim($school['sourcedId']),
                    'code' => trim($district['sourcedId']),
                    'district_id' => ($districtId) ?? NULL
                ]);
                Redis::set(static::REDIS_SCHOOL_ID_KEY.trim($school['sourcedId']),$schoolData->id);
            }
        }
    }

    /**
     * Sync districts from oneRoster api
     * 
     * @param int $integrationId
     * @param string $accessToken
     * 
     * @return void
     */
    public function syncDistrict($district){
        //either the key is not set Or dateTime on key don't match
        if(is_null(Redis::get(static::REDIS_DISTRICT_FIELD_KEY.trim($district['sourcedId']))) ||
        (!is_null(Redis::get(static::REDIS_DISTRICT_FIELD_KEY.trim($district['sourcedId']))) &&
        Redis::get(static::REDIS_DISTRICT_FIELD_KEY.trim($district['sourcedId'])) !== $district['dateLastModified'])){
            try { 
                $districtData = Districts::withTrashed()->updateOrCreate(
                [
                 'ext_district_id' => $district['sourcedId'],
                 'identifier' => $district['identifier']
                ],
                [
                    'name' => $district['name'],
                    'ext_district_id' => trim($district['sourcedId']),
                    'identifier' => trim($district['identifier']),
                    'status' => $district['status'] == 'active' ? 1 : 0,
                    'ext_updated_at' => Carbon::parse($district['dateLastModified']),
                    'deleted_at' => NULL,
                ]
                );
                //After database entry is made set the key
                Redis::set(static::REDIS_DISTRICT_ID_KEY.trim($district['sourcedId']),$districtData->id);
                Redis::set(static::REDIS_DISTRICT_FIELD_KEY.trim($district['sourcedId']),$district['dateLastModified']);
            if(!empty($district['children'])){
                $this->syncSchoolsForDistrict($district);
            }
            } catch (Exception $ex) {
                Log::debug("failed in syncDistrict with error: ". $ex->getMessage());
            }
        }
        else
        {
            p([
                'case 1' => is_null(Redis::get('district_' . $district['sourcedId'])),
                'case2' => !is_null(Redis::get('district_'.$district['sourcedId'])),
                'case 3' => Redis::get('district_'.$district['sourcedId']) !== $district['dateLastModified']
            ]);
        }
    }
}
