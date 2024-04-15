<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\DistrictService;
use Illuminate\Support\Facades\DB;

class SyncClassJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public $class;
    /**
     * Create a new job instance.
     */
    public function __construct(array $class)
    {
        $this->class = $class;
    }

    /**
     * Execute the job.
     */
    public function handle(ClassService $classService): void
    {
        try {
            DB::beginTransaction();
            $classService->syncClass($this->class);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
