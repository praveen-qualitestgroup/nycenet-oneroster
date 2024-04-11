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

class SyncDistrictJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public $district;
    /**
     * Create a new job instance.
     */
    public function __construct(array $district)
    {
        $this->district = $district;
    }

    /**
     * Execute the job.
     */
    public function handle(DistrictService $districtService): void
    {
        try {
            DB::beginTransaction();
            $districtService->syncDistrict($this->district);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
