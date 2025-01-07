<?php

namespace App\Providers;

use App\Services\EmployeeIdGenerator;
use Illuminate\Support\ServiceProvider;
use App\Services\AdmissionNumberGenerator;

class SchoolSettingsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register()
    {
        $this->app->singleton(EmployeeIdGenerator::class, function ($app) {
            return new EmployeeIdGenerator();
        });

        $this->app->singleton(AdmissionNumberGenerator::class, function ($app) {
            return new AdmissionNumberGenerator();
        });
    }
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
