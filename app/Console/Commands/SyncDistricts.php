<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Providers\HttpServiceProvider;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use App\Jobs\SyncDistrictJob;
function p($data){
    echo '<pre>';
    die(print_r($data));
}
class SyncDistricts extends Command
{
     const REDIS_DISTRICT_FIELD_KEY = 'district:';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:lms-districts';
    protected $accessToken = NULL;
    public $httpServiceProvider = NULL;
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync district data from Edlink API';
    
    public function __construct(HttpServiceProvider $httpServiceProvider)
    {
        parent::__construct();
        $this->httpServiceProvider = $httpServiceProvider;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            Log::info("Syncing Districts from OneRoster API");
            SyncDistrictJob::dispatch($this->httpServiceProvider);
        } catch (Exception $ex) {
            Log::debug("failed in syncDistrict with error: ". $ex->getMessage());
        }
    }
    
    
}
