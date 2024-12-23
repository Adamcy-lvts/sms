<?php

namespace App\Providers;

use App\Models\Student;
use App\Models\StudentGrade;
use Filament\Facades\Filament;
use App\Models\AttendanceRecord;
use App\Observers\StudentObserver;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use App\Observers\StudentGradeObserver;
use Illuminate\Support\ServiceProvider;
use Filament\Support\Facades\FilamentView;
use App\Observers\AttendanceRecordObserver;
use Filament\Support\Facades\FilamentColor;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        StudentGrade::observe(StudentGradeObserver::class);
        Student::observe(StudentObserver::class);
        AttendanceRecord::observe(AttendanceRecordObserver::class);
    }
}
