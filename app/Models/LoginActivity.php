<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginActivity extends Model
{
    protected $fillable = [
        'user_id', 'ip_address', 'country', 'city',
        'device_type', 'browser', 'os', 'user_agent', 'logged_in_at',
    ];

    protected $casts = ['logged_in_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getLocationAttribute(): string
    {
        $parts = array_filter([$this->city, $this->country]);
        return implode(', ', $parts) ?: 'Unknown location';
    }

    public function getDeviceStringAttribute(): string
    {
        $parts = array_filter([$this->browser, $this->os]);
        return implode(' · ', $parts) ?: 'Unknown device';
    }
}
