<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
    ];
    /**
     * Define the application's command schedule.
     * 
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     * 
     * @return void
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('sync:lms-integrations')
            ->dailyAt('06:30');

        $schedule->command('sync:lms-schools')->dailyAt('06:50');
    }

    /**
     * Register the commands for the application.
     * 
     * @return void
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
