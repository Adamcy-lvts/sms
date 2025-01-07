<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\School;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class SmsPanelRolesAndPermissionsSeeder extends Seeder
{
    protected $smsRoles = [
        'principal' => [
            'permissions' => '*', // All permissions
        ],
        'vice_principal' => [
            'permissions' => [
                'students.*',
                'classes.*',
                'grades.*',
                'reports.*',
                'view_any_staff',
                'view_payments',
                'view_expenses',
            ],
        ],
        'teacher' => [
            'permissions' => [
                'view_any_students',
                'view_students',
                'create_grades',
                'edit_grades',
                'view_reports',
            ],
        ],
        'class_teacher' => [
            'permissions' => [
                'students.*',
                'grades.*',
                'reports.*',
                'view_payments',
            ],
        ],
        'accountant' => [
            'permissions' => [
                'payments.*',
                'expenses.*',
                'view_reports',
            ],
        ],
    ];

    public function run()
    {
        // Get all schools
        $schools = School::all();

        foreach ($schools as $school) {
            $this->createRolesForSchool($school);
        }
    }

    protected function createRolesForSchool(School $school)
    {
        foreach ($this->smsRoles as $roleName => $roleData) {
            // Create role
            $role = Role::findOrCreate($roleName, 'web');

            // Get permissions
            if ($roleData['permissions'] === '*') {
                $permissions = Permission::where('guard_name', 'web')->get();
            } else {
                $permissions = collect($roleData['permissions'])->flatMap(function ($permission) {
                    if (str_ends_with($permission, '.*')) {
                        $module = str_replace('.*', '', $permission);
                        return Permission::where('name', 'like', "{$module}.%")
                            ->where('guard_name', 'web')
                            ->get();
                    }
                    return Permission::where('name', $permission)
                        ->where('guard_name', 'web')
                        ->get();
                });
            }

            // Sync permissions
            $role->syncPermissions($permissions);

            // Find all staff users in this school and assign roles
            $staffUsers = $school->staff()
                ->whereHas('designation', function($query) use ($roleName) {
                    $query->where('name', 'like', '%' . ucfirst($roleName) . '%');
                })
                ->with('user')
                ->get();

            foreach ($staffUsers as $staff) {
                if ($staff->user) {
                    $staff->user->assignRole($role, $school->id);
                }
            }
        }
    }
}