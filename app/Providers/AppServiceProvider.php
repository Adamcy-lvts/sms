<?php

namespace App\Providers;


use App\Models\Student;
use App\Models\Permission;
use App\Models\Role;

use App\Models\StudentGrade;
use Filament\Facades\Filament;
use App\Models\AttendanceRecord;
use App\Observers\StudentObserver;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Foundation\Http\Kernel;
use App\Observers\StudentGradeObserver;
use Illuminate\Support\ServiceProvider;
use App\Http\Middleware\SchoolPermission;
use Filament\Support\Facades\FilamentView;
use App\Observers\AttendanceRecordObserver;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Routing\Middleware\SubstituteBindings;
use App\Models\Staff;
use App\Observers\StaffObserver;
use App\Models\School;
use App\Observers\SchoolObserver;

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
        app(\Spatie\Permission\PermissionRegistrar::class)
            ->setPermissionClass(Permission::class)
            ->setRoleClass(Role::class);

        StudentGrade::observe(StudentGradeObserver::class);
        Student::observe(StudentObserver::class);
        AttendanceRecord::observe(AttendanceRecordObserver::class);
        Staff::observe(StaffObserver::class);
        School::observe(SchoolObserver::class);
    }
}
