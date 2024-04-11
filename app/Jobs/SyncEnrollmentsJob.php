<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\SchoolService;
use Exception;
use Illuminate\Support\Facades\DB;

class SyncEnrollementJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Enrollment data from OneRoster
     */
    protected $enrollment;
    
    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 6000;

    /**
     * Indicate if the job should be marked as failed on timeout.
     *
     * @var bool
     */
    public $failOnTimeout = true;

    /**
     * Create a new job instance.
     * 
     * @param array $enrollment
     * 
     */
    public function __construct(array $enrollment)
    {
        $this->enrollment = $enrollment;
    }


    /**
     * Execute the job.
     * 
     * @param SchoolService $userService
     * 
     * @return void
     */
    public function handle(UserService $userService): void
    {
        try {
            DB::beginTransaction();
            $userService->syncTeachers($this->school);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

}
