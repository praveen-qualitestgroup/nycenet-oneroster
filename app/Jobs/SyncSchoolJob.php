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

class SyncSchoolJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * School data from edlink
     */
    protected $school;
    protected $accessToken;

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
     * @param array $school
     * @param string $accessToken
     * 
     */
    public function __construct(array $school, string $accessToken)
    {
        $this->school = $school;
        $this->accessToken = $accessToken;
    }


    /**
     * Execute the job.
     * 
     * @param SchoolService $schoolService
     * 
     * @return void
     */
    public function handle(SchoolService $schoolService): void
    {
        try {
            DB::beginTransaction();

            $schoolService->syncSchool($this->school, $this->accessToken);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

}
