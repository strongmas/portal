<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    protected $fillable = [
        'user_id', 'property_id', 'sales_rep_id',
        'preferred_date', 'preferred_time', 'notes',
        'status', 'outcome_notes', 'google_event_id',
    ];

    protected $casts = ['preferred_date' => 'date'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function salesRep(): BelongsTo
    {
        return $this->belongsTo(SalesRep::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending'      => 'Pending',
            'confirmed'    => 'Confirmed',
            'inspected'    => 'Inspected',
            'cancelled'    => 'Cancelled',
            'rescheduled'  => 'Rescheduled',
            default        => ucfirst($this->status),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'confirmed'   => 'green',
            'inspected'   => 'blue',
            'cancelled'   => 'red',
            'rescheduled' => 'orange',
            default       => 'yellow',
        };
    }

    public function getDateTimeAttribute(): string
    {
        return $this->preferred_date->format('D, d M Y') . ' at ' . date('g:i A', strtotime($this->preferred_time));
    }
}
