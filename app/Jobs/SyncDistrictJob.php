<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\DistrictService;
use App\Providers\HttpServiceProvider;

class SyncDistrictJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public $districtService;
    
    public $httpServiceProvider;
    /**
     * Create a new job instance.
     */
    public function __construct(HttpServiceProvider $httpServiceProvider)
    {
        $this->httpServiceProvider = $httpServiceProvider;
        $this->districtService = new DistrictService($httpServiceProvider);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $this->districtService->syncAllDistricts();
        } catch (Exception $e) {
            throw $e;
        }
    }
}
