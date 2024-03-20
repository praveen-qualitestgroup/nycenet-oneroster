<?php

namespace App\Services;

use App\Models\Districts;
use App\Providers\HttpServiceProvider;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DistrictService
{
    /**
     * Redis key for district
     */
    const REDIS_DISTRICT_FIELD_KEY = 'district:';

    /*
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
     * Sync districts from edlink api
     * 
     * @param int $integrationId
     * @param string $accessToken
     * 
     * @return void
     */
    public function syncdistricts(int $integrationId, string $accessToken): void
    {

        $this->httpServiceProvider->setAccessToken($accessToken);
        $districts = $this->httpServiceProvider->getResponse('districts?$first=1000');

        $existingDistricts = Districts::Where('integration_id', $integrationId)->exists() ? Districts::Where('integration_id', $integrationId)->pluck('ext_district_id')->toArray() : [];
        Redis::pipeline(function ($pipe) use ($districts, $integrationId, $existingDistricts) {
            Log::info("Syncing Districts data from Edlink API for Integration ID:" . $integrationId);
            $newDistricts = [];
            if (count($districts)) {
                foreach ($districts as $key => $district) {
                    $newDistricts[$key] = $district['id'];
                    $districtHash = md5(json_encode($district));
                    if (
                        !Redis::exists(static::REDIS_DISTRICT_FIELD_KEY . $district['id']) ||
                        (Redis::exists(static::REDIS_DISTRICT_FIELD_KEY . $district['id']) &&
                            (Redis::get(static::REDIS_DISTRICT_FIELD_KEY . $district['id']) !== $districtHash)
                        )
                    ) {
                        //Insert or update the hash and db value
                        $pipe->set(static::REDIS_DISTRICT_FIELD_KEY . $district['id'], $districtHash);
                        Districts::withTrashed()->updateOrCreate(
                            ['ext_district_id' => $district['id']],
                            [
                                'integration_id' => $integrationId,
                                'name' => $district['name'],
                                'addr_street' => $district['address']['street'],
                                'addr_unit' => $district['address']['unit'],
                                'addr_postal_code' => $district['address']['postal_code'],
                                'addr_city' => $district['address']['city'],
                                'addr_state' => $district['address']['state'],
                                'addr_country' => $district['address']['country'],
                                'phone' => $district['address']['phone'],
                                'time_zone' => $district['time_zone'],
                                'ext_updated_at' => Carbon::parse($district['updated_date']),
                                'deleted_at' => NULL
                            ]
                        );

                        Log::debug("Data for District Id: " . $district['id'] . " and name: " . $district['name'] . " created/updated in DB.");
                    } else {
                        Log::debug("Redis Key " . static::REDIS_DISTRICT_FIELD_KEY . $district['id'] . " for district Id: " . $district['id'] . " already exists.");
                    }
                }
            } else {
                Log::info("No Districts found");
            }
            // delete districts which are not in edlink
            $deletedDistricts = array_diff($existingDistricts, $newDistricts);
            if (!empty($deletedDistricts)) {
                foreach ($deletedDistricts as $key => $value) {
                    Redis::del(static::REDIS_DISTRICT_FIELD_KEY . $value);
                }
                Districts::whereIn('ext_district_id', $deletedDistricts)->delete();
                Log::debug('Deleted districts: ' . json_encode($deletedDistricts));
            }
        });

    }


}
