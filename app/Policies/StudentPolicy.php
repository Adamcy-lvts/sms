<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Student;
use Illuminate\Auth\Access\HandlesAuthorization;

class StudentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_student');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Student $student): bool
    {
        return $user->can('view_student');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_student');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Student $student): bool
    {
        return $user->can('update_student');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Student $student): bool
    {
        return $user->can('delete_student');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_student');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, Student $student): bool
    {
        return $user->can('force_delete_student');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_student');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, Student $student): bool
    {
        return $user->can('restore_student');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_student');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, Student $student): bool
    {
        return $user->can('replicate_student');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_student');
    }

    /**
     * Determine whether the user can promote students.
     */
    public function promote(User $user): bool
    {
        return $user->can('promote_student');
    }

    /**
     * Determine whether the user can change student status.
     */
    public function changeStatus(User $user): bool
    {
        return $user->can('change_status_student');
    }

    /**
     * Determine whether the user can record payments.
     */
    public function recordPayment(User $user): bool
    {
        return $user->can('record_payment_student');
    }

    /**
     * Determine whether the user can perform bulk promotions.
     */
    public function bulkPromote(User $user): bool
    {
        return $user->can('bulk_promote_student');
    }

    /**
     * Determine whether the user can perform bulk status changes.
     */
    public function bulkStatusChange(User $user): bool
    {
        return $user->can('bulk_status_change_student');
    }

    /**
     * Determine whether the user can perform bulk payments.
     */
    public function bulkPayment(User $user): bool
    {
        return $user->can('bulk_payment_student');
    }

    /**
     * Determine whether the user can import students.
     */
    public function import(User $user): bool
    {
        return $user->can('import_student');
    }

    /**
     * Determine whether the user can view student profile.
     */
    public function profile(User $user): bool // Changed from student_profile to just profile
    {
        return $user->can('profile_student');
    }
}
