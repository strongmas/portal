<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectMilestone extends Model
{
    protected $fillable = [
        'property_id', 'title', 'description',
        'planned_date', 'actual_date', 'status', 'sort_order', 'image_path',
    ];

    protected $casts = [
        'planned_date' => 'date',
        'actual_date'  => 'date',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'in_progress' => 'In Progress',
            'completed'   => 'Completed',
            'delayed'     => 'Delayed',
            default       => 'Pending',
        };
    }

    public function getIsOnTrackAttribute(): bool
    {
        if ($this->status === 'completed') {
            return is_null($this->actual_date) || $this->actual_date->lte($this->planned_date);
        }
        return !now()->gt($this->planned_date);
    }
}
