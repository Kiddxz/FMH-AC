<?php

namespace App\Policies;

use App\Models\Pet;
use App\Models\Role;
use App\Models\User;

/**
 * Who may see or change a pet (capstone NFR-REQ012).
 * A customer only reaches THEIR OWN pets; clinic accounts need the permission.
 * Used from Phase 5:  $this->authorize('view', $pet)  or  @can('update', $pet)
 */
class PetPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::CUSTOMER) || $user->hasPermission('pets.view');
    }

    public function view(User $user, Pet $pet): bool
    {
        if ($user->hasRole(Role::CUSTOMER)) {
            return $this->owns($user, $pet);
        }

        return $user->hasPermission('pets.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('portal.access') || $user->hasPermission('pets.manage');
    }

    public function update(User $user, Pet $pet): bool
    {
        if ($user->hasRole(Role::CUSTOMER)) {
            return $this->owns($user, $pet);
        }

        return $user->hasPermission('pets.manage');
    }

    private function owns(User $user, Pet $pet): bool
    {
        return $user->customer !== null && $pet->customer_id === $user->customer->id;
    }
}
