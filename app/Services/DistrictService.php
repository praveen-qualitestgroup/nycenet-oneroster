<?php

namespace App\Services;

use App\Models\District;
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
     * Sync districts from edlink api
     * 
     * @param int $integrationId
     * @param string $accessToken
     * 
     * @return void
     */
    public function syncAllDistricts(){
        $this->httpServiceProvider->generateNewToken();
        $allDistrictIds = District::all()->pluck('sourcedId')->toArray();
        $response = $this->httpServiceProvider->getResponse('orgs');
        if($response['success'] === true  && $response['status'] === 200){
            $dataKey = $response['message'];
            $this->syncDistrict($response['data'][$dataKey]);
        }
        else
        {
            return $response;
        }
    }
    
    private function syncDistrict($data){
        $chunkSize = env('CHUNK_SIZE',100);
        $dataChunks = array_chunk($data, $chunkSize);
        
        //chunking to handle large requests
        foreach ($dataChunks as $chunk){
            try {
                Redis::pipeline(function ($pipe) use ($chunk) {
                    $this->addOrUpdateRecord($pipe, $chunk);
                });
            } catch (Exception $ex) {
                Log::debug("failed in syncDistrict with error: ". $ex->getMessage());
                continue;
            }
        }
    }
    
    private function addOrUpdateRecord($pipe,$chunk){
        foreach ($chunk as $detail){
            try {
                array_push($this->newDistricts, trim($detail['sourcedId']));
                $pipe->set('district_'.$detail['sourcedId'],$detail['dateLastModified']);
            } catch (Exception $ex) {
                Log::debug("failed in addOrUpdate with error: ". $ex->getMessage());
                continue;
            }
        }
    }
}
