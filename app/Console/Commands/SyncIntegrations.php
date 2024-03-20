<?php

namespace App\Console\Commands;

use App\Jobs\SyncIntegrationsJob;
use App\Models\Applications;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncIntegrations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:lms-integrations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync integrations data from Edlink API';

    /**
     * Execute the console command.
     * 
     * @return void
     */
    public function handle(): void
    {
        try {
            $this->info("Syncing integrations from Edlink API");

            $allApplications = Applications::all();
            Log::info("all applications founds aar as : ".$allApplications);
            if (count($allApplications)) {
                foreach ($allApplications as $application) {
                    SyncIntegrationsJob::dispatch($application->id, $application->application_secret);
                    Log::info(date("Y-m-d H:i:s") . ": Job Scheduled for Application name:" . $application->application_name . " and ID:" . $application->id . " for LMS.");
                }
            }else{
                $this->info(" There is no job to run");
            }
        } catch (\Exception $e) {
            Log::error("Error syncing application integrations:" . $e->getMessage() . "for LMS.");
        }
    }
}
