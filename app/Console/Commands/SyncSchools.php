<?php

namespace App\Console\Commands;

use App\Jobs\SyncSchoolJob;
use App\Models\Districts;
use App\Models\Integrations;
use App\Models\Schools;
use App\Providers\HttpServiceProvider;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class SyncSchools extends Command
{
    const REDIS_SCHOOL_FIELD_KEY = 'school:';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:lms-schools';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync district data from Edlink API';

    /**
     * HttpServiceProvider instance
     */
    public $httpServiceProvider;

    /**
     * Create a new command instance.
     * 
     * @param HttpServiceProvider $httpServiceProvider
     */
    public function __construct(HttpServiceProvider $httpServiceProvider)
    {
        parent::__construct();
        $this->httpServiceProvider = $httpServiceProvider;
    }
    /**
     * Execute the console command.
     * 
     * @return void
     */
    public function handle(): void
    {
        try {
            $this->info("Syncing Schol data from Edlink API for Kneoworld LMS DB.");
            $allDistricts = Districts::all();

            if (count($allDistricts)) {

                foreach ($allDistricts as $district) {
                    if (!empty(Integrations::where('id', $district->integration_id)->first()->access_token)) {
                        $encryptedAccessToken = Integrations::where('id', $district->integration_id)->first()->access_token;
                        $accessToken = $this->httpServiceProvider->tokendecrypt($encryptedAccessToken);

                        log::info("School accesstoken:" . $accessToken);

                        $this->httpServiceProvider->setAccessToken($accessToken);
                        $schools = $this->httpServiceProvider->getResponse('schools?$first=1000');
                        $existingSchools = Schools::Where('district_id', $district->id)->exists() ? Schools::Where('district_id', $district->id)->pluck('ext_school_id')->toArray() : [];
                        $newSchools = [];
                        if (count($schools)) {
                            foreach ($schools as $key => $school) {
                                $newSchools[$key] = $school['id'];
                                SyncSchoolJob::dispatch($school, $accessToken);
                                Log::info("Job Scheduled for District name:" . $district->name . "and ID:" . $district->ext_district_id . " | School Name:" . $school['name'] . "and ID:" . $school['id']);
                            }
                        } else {
                            Log::info("No Schools found for District name:" . $district->name);
                        }
                        //delete the schools which are not in the new list
                        $deletedSchools = array_diff($existingSchools, $newSchools);
                        if (!empty($deletedSchools)) {
                            foreach ($deletedSchools as $delSchool) {
                                Redis::del(static::REDIS_SCHOOL_FIELD_KEY . $delSchool);
                            }
                            Schools::whereIn('ext_school_id', $deletedSchools)->delete();
                            Log::debug("Deleted Schools data: " . json_encode($deletedSchools) . " for District name:" . $district->name);
                        }
                    }else{
                        Log::info("No integration access token found for District name:" . $district->name);
                    }

                }
            } else {
                Log::info("No Districts found");
            }

        } catch (\Exception $e) {
            Log::error("Error syncing for school:" . $e->getMessage() . " for LMS.");
        }

    }
}
