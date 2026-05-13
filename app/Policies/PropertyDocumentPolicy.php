<?php

namespace App\Policies;

use App\Models\PropertyDocument;
use App\Models\User;

class PropertyDocumentPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->is_admin) return true;
        return null;
    }

    public function download(User $user, PropertyDocument $document): bool
    {
        if ((int) $document->uploaded_by === (int) $user->id) {
            return true;
        }

        if (!$document->is_buyer_visible) return false;

        if ($user->ownerships()->where('property_id', $document->property_id)->exists()) {
            return true;
        }

        return \App\Models\Ownership::where('property_id', $document->property_id)
            ->whereHas('members', fn($q) => $q
                ->where('user_id', $user->id)
                ->whereNotNull('accepted_at')
            )->exists();
    }
}
