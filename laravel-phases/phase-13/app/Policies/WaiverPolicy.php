<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;
use App\Models\Waiver;

/**
 * Who may see, sign, review or cancel a waiver (capstone FR-REQ022 - FR-REQ024).
 * A customer only reaches waivers of THEIR OWN pets.
 */
class WaiverPolicy
{
    public function view(User $user, Waiver $waiver): bool
    {
        if ($user->hasRole(Role::CUSTOMER)) {
            return $this->owns($user, $waiver);
        }

        return $user->hasPermission('waivers.view');
    }

    // The owner signs in the portal, or signs at the clinic with the staff (decision P5)
    public function sign(User $user, Waiver $waiver): bool
    {
        if (! $waiver->isPending()) {
            return false;
        }

        return $user->hasRole(Role::CUSTOMER) ? $this->owns($user, $waiver) : $user->hasPermission('waivers.prepare');
    }

    public function review(User $user, Waiver $waiver): bool
    {
        return $waiver->status === 'signed' && $user->hasPermission('waivers.review');
    }

    // Only a waiver that was not signed yet can be cancelled
    public function delete(User $user, Waiver $waiver): bool
    {
        return $waiver->isPending() && $user->hasPermission('waivers.prepare');
    }

    private function owns(User $user, Waiver $waiver): bool
    {
        return $user->customer !== null && $waiver->customer_id === $user->customer->id;
    }
}
