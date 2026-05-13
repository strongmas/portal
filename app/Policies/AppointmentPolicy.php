<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->is_admin) return true;
        return null;
    }

    public function view(User $user, Appointment $appointment): bool
    {
        return (int) $appointment->user_id === (int) $user->id;
    }

    public function cancel(User $user, Appointment $appointment): bool
    {
        return (int) $appointment->user_id === (int) $user->id
            && in_array($appointment->status, ['pending', 'confirmed']);
    }
}
