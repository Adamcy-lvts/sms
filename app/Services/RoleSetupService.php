<?php

namespace App\Services;

use App\Models\School;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Log;

class RoleSetupService
{
    public function setupSchoolRoles(School $school): void
    {
        try {
            // Define roles with their permissions
            $roles = [
                'admin' => [
                    // Academic Management
                    'view_academic::session', 'view_any_academic::session', 'create_academic::session',
                    'view_class::room', 'view_any_class::room', 'create_class::room',
                    'view_subject', 'view_any_subject', 'create_subject',
                    
                    // Student Management
                    'view_student', 'view_any_student', 'create_student',
                    'promote_student', 'change_status_student', 'bulk_promote_student',
                    'profile_student', 'import_student',
                    
                    // Staff Management
                    'view_staff', 'view_any_staff', 'create_staff',
                    'view_teacher', 'view_any_teacher', 'create_teacher',
                ],
                
                'accountant' => [
                    // Financial Management
                    'view_payment', 'view_any_payment', 'create_payment',
                    'view_payment::method', 'view_any_payment::method',
                    'record_payment_student', 'bulk_payment_student',
                    'view_expense', 'view_any_expense', 'create_expense',
                    
                    // Limited Student View
                    'view_student', 'view_any_student', 'profile_student',
                ],
                
                'teacher' => [
                    // Class & Student Management
                    'view_class::room', 'view_any_class::room',
                    'view_student', 'view_any_student', 'profile_student',
                    'can_take_attendance_class_room',
                    'create_attendance', 'view_attendance',
                    'view_subject', 'view_any_subject'
                ]
            ];

            foreach ($roles as $roleName => $permissions) {
                // Create role with team_id
                $role = Role::firstOrCreate([
                    'name' => $roleName,
                    'guard_name' => 'web',
                    'team_id' => $school->id // Add team_id for multi-tenancy
                ]);

                // Get existing permissions and give them to role
                $permissionModels = Permission::whereIn('name', $permissions)
                    ->get(); // Remove the team_id where clause

                $role->givePermissionTo($permissionModels);
                
                Log::info("Created role for school", [
                    'school_id' => $school->id,
                    'role' => $roleName,
                    'permissions_count' => count($permissions)
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error creating school roles', [
                'school_id' => $school->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
