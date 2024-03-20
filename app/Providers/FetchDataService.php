<?php

namespace App\Providers;

use App\Models\Districts;
use Illuminate\Support\ServiceProvider;
use App\Providers\HttpServiceProvider;


class FetchDataService extends ServiceProvider
{
    private $httpServiceProvider;
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }

    public function __construct(HttpServiceProvider $httpServiceProvider)
    {
        $this->httpServiceProvider = $httpServiceProvider;
    }
    public function getIntegrations(){
    }

    public function getDistricts()
    {
        $response = $this->httpServiceProvider->getResponse('districts');

    }
    public function getSchools()
    {
        $response = $this->httpServiceProvider->getResponse('schools');
    }
    public function getTeachers()
    {
        $response = $this->httpServiceProvider->getResponse('districts');
    }
    public function getClasses()
    {
        $response = $this->httpServiceProvider->getResponse('classes');
    }
    public function getGrades()
    {
        $response = $this->httpServiceProvider->getResponse('districts');
    }
}
