<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AdminRole extends Model
{
    protected $fillable = ['name', 'slug', 'permissions'];

    protected $casts = ['permissions' => 'array'];

    public const ALL_PERMISSIONS = [
        'buyers'                  => 'View Buyers',
        'support'                 => 'Chat Support',
        'tickets'                 => 'Support Tickets',
        'kyc'                     => 'KYC Review',
        'payments'                => 'Payment Verification',
        'appointments'            => 'Appointments',
        'interests'               => 'Interest Registrations',
        'project_update_comments' => 'Project Update Comments',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $role) {
            if (empty($role->slug)) {
                $role->slug = Str::slug($role->name);
            }
        });
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? []);
    }
}
