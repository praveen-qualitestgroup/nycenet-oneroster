<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Providers\HttpServiceProvider;
use Illuminate\Support\Facades\Log;
use App\Services\DistrictService;
use Illuminate\Support\Facades\Redis;
use App\Jobs\SyncDistrictJob;
use App\Models\Districts;
use App\Models\Schools;
use App\Models\User;
use App\Jobs\SyncSchoolJob;
use App\Jobs\SyncUserJob;
function p($data){
    echo '<pre>';
    die(print_r($data));
}
class SyncDistricts extends Command
{
     const REDIS_DISTRICT_FIELD_KEY = 'district:';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:lms-districts';
    protected $accessToken = NULL;
    public $httpServiceProvider = NULL;
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync district data from Edlink API';
    
    public function __construct(HttpServiceProvider $httpServiceProvider)
    {
        parent::__construct();
        $this->httpServiceProvider = $httpServiceProvider;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            ini_set('max_execution_time', 0);
            $this->httpServiceProvider->generateNewToken();
            
            $this->info("Syncing Districts from OneRoster API");
            $districtResponse = $this->httpServiceProvider->getResponse('orgs');
            
            if($districtResponse['success'] === true && $districtResponse['status'] === 200){
                $newDistricts = $this->setDistrictJobs($districtResponse);
                $this->checkDeletedDistricts($newDistricts);
            }
    
            $this->info("Syncing School data from OneRoster API");
            $schoolsResponse = $this->httpServiceProvider->getResponse('schools');
            if($schoolsResponse['success'] === true  && $schoolsResponse['status'] === 200){
                $this->setSchoolJobs($schoolsResponse);
            }
            
            $this->info("Syncing Users(Teachers) data from OneRoster API");
            $teacherResponse = $this->httpServiceProvider->getResponse('teachers');
            if($teacherResponse['success'] === true && $teacherResponse['status'] === 200){
                $newUsers = $this->setUsersJobs($teacherResponse);
                $this->checkDeletedUsers($newUsers);
            }
            
            $this->info("Syncing Students data from OneRoster API");
            $studentsResponse = $this->httpServiceProvider->getResponse('students');
            if($studentsResponse['success'] === true  && $studentsResponse['status'] === 200){
                $newStudents = $this->setUsersJobs($studentsResponse);
                $this->checkDeletedUsers($newStudents);
            }
        } catch (Exception $ex) {
            Log::debug("failed in syncDistrict with error: ". $ex->getMessage());
        }
    }
    
    public function setDistrictJobs($districtResponse) : array
    {
        $dataKey = $districtResponse['message'];
        $newDistricts = [];
        foreach ($districtResponse['data'][$dataKey] as $district){
            if(strtolower($district['type']) === "district"){
                SyncDistrictJob::dispatch($district);
                $newDistricts[] = trim($district['sourcedId']);
            }
        }
        return $newDistricts;
    }
    
    public function setSchoolJobs($schoolsResponse) : bool
    {    
        $dataKey = $schoolsResponse['message'];
        foreach ($schoolsResponse['data'][$dataKey] as $school) {
            if(strtolower($school['type']) === "school"){
                SyncSchoolJob::dispatch($school);
                Log::info("Job Scheduled for School name:" . $school['name'] . "and ID:" . $school['sourcedId']);
            }
        }
        return true;
    }
    
    public function checkDeletedDistricts($newDistricts){
        //since new district are not saved yet, we will fetch all saved sourcedIds  
        $allDistrictIds = Districts::withTrashed()->pluck('ext_district_id')->toArray();
        $deletedDistricts = array_diff($allDistrictIds, $newDistricts);
        if (!empty($deletedDistricts)) {
            foreach ($deletedDistricts as $delDistrict) {
                Schools::where('code', $delDistrict)->delete();
                Redis::del(static::REDIS_DISTRICT_FIELD_KEY . $delDistrict);
            }
            Log::debug("Deleted District data: " . json_encode($deletedDistricts) . " for District name:" . $district->name);
        }else{
            Log::info("No districts deleted");
        }
    }
    
    public function setUsersJobs(array $users) : array
    {
        $dataKey = $users['message'];
        $newUsers = [];
        foreach($users['data'][$dataKey] as $user){
            SyncUserJob::dispatch($user);
            if(!empty($user['orgs'])){
                //get teachers for all the organizations the user has access to
                foreach ($user['orgs'] as $org){
                    if($org['type'] === 'org'){
                        $newUsers[trim($org['sourcedId'])][] = trim($user['sourcedId']);
                    }
                }
            }
        }
        return $newUsers;
    }
    
    public function checkDeletedUsers($users) : void
    {
        foreach ($users as $org => $userGroup){
            $schoolId = Redis::get(DistrictService::REDIS_SCHOOL_ID_KEY.trim($org));
            $dbUsers = User::where('school_id',$schoolId)->pluck('ext_user_id')->toArray();
            $usersToBeDeleted = array_diff($dbUsers,$userGroup);
            if(!empty($usersToBeDeleted)){
                User::whereIn('ext_student_id',$usersToBeDeleted)->delete();
            }
        }
    }
    
}
