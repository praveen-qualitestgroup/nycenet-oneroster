<?php

namespace App\Services;

use App\Models\Integrations;
use App\Providers\HttpServiceProvider;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;


class IntegrationService
{
    /**
     * Redis key for integration
     */
    const REDIS_INTEGRATION_FIELD_KEY = 'integration:';

    /**
     * HttpServiceProvider instance
     */
    public $httpServiceProvider;

    /**
     * DistrictService instance
     */
    public $districtService;

    /**
     * Create a construct function
     * 
     * @param HttpServiceProvider $httpServiceProvider
     * @param DistrictService $districtService
     * 
     */
    public function __construct(HttpServiceProvider $httpServiceProvider, DistrictService $districtService)
    {
        $this->httpServiceProvider = $httpServiceProvider;
        $this->districtService = $districtService;
    }

    /**
     * Sync integrations from edlink api
     * 
     * @param int $applicationId
     * @param string $applicationAccessToken
     * 
     * @return void
     */
    public function syncIntegrations(int $applicationId, string $applicationAccessToken): void
    {
        $integrationsData = $this->httpServiceProvider->getIntegrations($applicationAccessToken);
        $integrations = $integrationsData['$data'];
        Log::info("Sync Started for Integration Id: " . implode(',', $integrationsData));
        $existingIntegrations = Integrations::pluck('ext_integration_id')->toArray();
        
        Redis::pipeline(function ($pipe) use ($integrations, $existingIntegrations, $applicationId) {
            $newIntegrations = [];
            if (count($integrations)) {
                foreach ($integrations as $key => $integration) {
                    if ($integration['status'] == 'active') {

                        Log::info("Sync Started for Integration Id: " . $integration['id'] . " and Name: " . $integration['source']['name'] . "");
                        $newIntegrations[$key] = $integration['id'];
                        $integrationHash = md5(json_encode($integration));
                        if (
                            !Redis::exists(static::REDIS_INTEGRATION_FIELD_KEY . $integration['id']) ||
                            (Redis::exists(static::REDIS_INTEGRATION_FIELD_KEY . $integration['id']) &&
                                (Redis::get(static::REDIS_INTEGRATION_FIELD_KEY . $integration['id']) !== $integrationHash)
                            )
                        ) {
                            //Insert update the hash and db value
                            $pipe->set(static::REDIS_INTEGRATION_FIELD_KEY . $integration['id'], $integrationHash);
                            log::info("accesstoken:" . $integration['access_token']);
                            // log::info("encrypt:" . $this->httpServiceProvider->tokenencrypt($integration['access_token']));
                            // log::info("decrypt:" . $this->httpServiceProvider->tokendecrypt($this->httpServiceProvider->tokendecrypt($this->httpServiceProvider->tokenencrypt($integration['access_token']))));
                            Integrations::withTrashed()->updateOrCreate(
                                ['ext_integration_id' => $integration['id']],
                                [
                                    'access_token' => $this->httpServiceProvider->tokenencrypt($integration['access_token']),
                                    'ext_source_id' => $integration['source']['id'],
                                    'ext_source_name' => $integration['source']['name'],
                                    'application_id' => $applicationId,
                                    'ext_updated_at' => Carbon::parse($integration['updated_date']),
                                    'deleted_at' => NULL
                                ]
                            );
                            Log::debug("Data for Integration Id: " . $integration['id'] . " and Name: " . $integration['source']['name'] . " created/updated in DB.");
                        } else {
                            Log::debug("Redis Key " . static::REDIS_INTEGRATION_FIELD_KEY . $integration['id'] . " for Integration Id: " . $integration['id'] . " already exists.");
                        }

                        $integrationData = Integrations::where('ext_integration_id', $integration['id'])->exists() ? Integrations::where('ext_integration_id', $integration['id'])->first(['access_token', 'id'])->toArray() : [];
                        if (!empty($integrationData)) {
                            $integrationAccessToken = $this->httpServiceProvider->tokendecrypt($integrationData['access_token']);
                            $this->districtService->syncdistricts($integrationData['id'], $integrationAccessToken);
                        } else {
                            Log::debug("No integration data found for  integration Id:" . $integration['id'] . " and Name: " . $integration['source']['name'] . " in DB.");

                        }
                        Log::info("Sync Completed for Integration Id: " . $integration['id'] . " and Name: " . $integration['source']['name'] . "");
                    } else {
                        Log::info("Integration Id: " . $integration['id'] . " and Name: " . $integration['source']['name'] . " is " . ($integration['status'] ? $integration['status'] : "not active"));
                    }
                }
            } else {
                Log::info("No Integrations found");
            }
            // delete integrations which are not in edlink
            $deletedIntegrations = array_diff($existingIntegrations, $newIntegrations);
            if (!empty($deletedIntegrations)) {
                foreach ($deletedIntegrations as $key => $value) {
                    Redis::del(static::REDIS_INTEGRATION_FIELD_KEY . $value);
                }
                Integrations::whereIn('ext_integration_id', $deletedIntegrations)->delete();
                Log::debug("Deleted Integrations: " . json_encode($deletedIntegrations));
            }
        });
    }
}
