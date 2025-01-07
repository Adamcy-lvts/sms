<?php

namespace App\Policies;

use App\Models\User;
use App\Models\BehavioralTrait;
use Illuminate\Auth\Access\HandlesAuthorization;

class BehavioralTraitPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_behavioral::trait');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, BehavioralTrait $behavioralTrait): bool
    {
        return $user->can('view_behavioral::trait');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_behavioral::trait');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, BehavioralTrait $behavioralTrait): bool
    {
        return $user->can('update_behavioral::trait');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, BehavioralTrait $behavioralTrait): bool
    {
        return $user->can('delete_behavioral::trait');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_behavioral::trait');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, BehavioralTrait $behavioralTrait): bool
    {
        return $user->can('force_delete_behavioral::trait');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_behavioral::trait');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, BehavioralTrait $behavioralTrait): bool
    {
        return $user->can('restore_behavioral::trait');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_behavioral::trait');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, BehavioralTrait $behavioralTrait): bool
    {
        return $user->can('replicate_behavioral::trait');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_behavioral::trait');
    }
}
