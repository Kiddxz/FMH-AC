<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\Role;
use App\Models\User;

/**
 * Who may see or change an appointment (capstone NFR-REQ012).
 * A customer only reaches THEIR OWN appointments; clinic accounts need the permission.
 * Used from Phase 9:  $this->authorize('view', $appointment)
 */
class AppointmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::CUSTOMER) || $user->hasPermission('appointments.view');
    }

    public function view(User $user, Appointment $appointment): bool
    {
        if ($user->hasRole(Role::CUSTOMER)) {
            return $this->owns($user, $appointment);
        }

        return $user->hasPermission('appointments.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('appointments.book') || $user->hasPermission('appointments.manage');
    }

    // Confirm, reschedule, change status: clinic staff only
    public function update(User $user, Appointment $appointment): bool
    {
        return $user->hasPermission('appointments.manage');
    }

    // A customer may cancel their own appointment while it is still pending or confirmed
    public function cancel(User $user, Appointment $appointment): bool
    {
        if ($user->hasRole(Role::CUSTOMER)) {
            return $this->owns($user, $appointment)
                && in_array($appointment->status, ['pending', 'confirmed'], true);
        }

        return $user->hasPermission('appointments.manage');
    }

    private function owns(User $user, Appointment $appointment): bool
    {
        return $user->customer !== null && $appointment->customer_id === $user->customer->id;
    }
}
