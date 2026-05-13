<?php

namespace App\Policies;

use App\Models\Ownership;
use App\Models\User;

class OwnershipPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        // Admins can do anything with any ownership
        if ($user->is_admin) return true;
        return null;
    }

    public function view(User $user, Ownership $ownership): bool
    {
        if ((int) $ownership->user_id === (int) $user->id) return true;

        return $ownership->members()->where('user_id', $user->id)->exists();
    }

    public function manage(User $user, Ownership $ownership): bool
    {
        if ((int) $ownership->user_id === (int) $user->id) return true;

        $member = $ownership->members()->where('user_id', $user->id)->first();
        return $member && $member->role === 'owner';
    }

    public function viewFinancials(User $user, Ownership $ownership): bool
    {
        if ((int) $ownership->user_id === (int) $user->id) return true;

        $member = $ownership->members()->where('user_id', $user->id)->first();
        return $member && in_array($member->role, ['owner', 'financial']);
    }
}
