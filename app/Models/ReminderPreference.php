<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReminderPreference extends Model
{
    protected $fillable = ['user_id', 'in_app', 'email', 'sms', 'days_before_due'];

    protected $casts = [
        'in_app' => 'boolean',
        'email'  => 'boolean',
        'sms'    => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
