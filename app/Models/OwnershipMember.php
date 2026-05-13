<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OwnershipMember extends Model
{
    protected $fillable = ['ownership_id', 'user_id', 'role', 'invited_by', 'accepted_at'];

    protected $casts = ['accepted_at' => 'datetime'];

    public function ownership(): BelongsTo
    {
        return $this->belongsTo(Ownership::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function getRoleLabelAttribute(): string
    {
        return match($this->role) {
            'owner'     => 'Owner',
            'financial' => 'Financial',
            default     => 'Viewer',
        };
    }
}
