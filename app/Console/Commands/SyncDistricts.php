<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SyncDistricts extends Command
{
     const REDIS_DISTRICT_FIELD_KEY = 'district:';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:lms-districts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync district data from Edlink API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        
    }
}
