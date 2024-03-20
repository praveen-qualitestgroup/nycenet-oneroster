<?php

namespace App\Jobs;

use App\Services\IntegrationService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class SyncIntegrationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $applicationId;
    protected $applicationAccessToken;
    /**
     * Create a new job instance.
     * @param string $applicationId
     * @param string $applicationAccessToken
     */
    public function __construct($applicationId, $applicationAccessToken)
    {
        $this->applicationId = $applicationId;
        $this->applicationAccessToken = $applicationAccessToken;
    }

    /**
     * Execute the job.
     * 
     * @param IntegrationService $integrationService
     * 
     * @return void
     */
    public function handle(IntegrationService $integrationService): void
    {
        try {
            DB::beginTransaction();
            $integrationService->syncIntegrations($this->applicationId, $this->applicationAccessToken);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

}
