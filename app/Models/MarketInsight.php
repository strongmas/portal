<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketInsight extends Model
{
    protected $fillable = [
        'title', 'region', 'category', 'description',
        'value', 'period', 'is_published', 'created_by', 'property_id',
        'change_type', 'change_percent',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    protected $casts = [
        'is_published'   => 'boolean',
        'change_percent' => 'float',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getCategoryLabelAttribute(): string
    {
        return match($this->category) {
            'pricing'  => 'Pricing Trends',
            'demand'   => 'Market Demand',
            'supply'   => 'Supply Analysis',
            'rental'   => 'Rental Yields',
            default    => 'General',
        };
    }

    public function getCategoryColorAttribute(): string
    {
        return match($this->category) {
            'pricing'  => 'blue',
            'demand'   => 'green',
            'supply'   => 'orange',
            'rental'   => 'purple',
            default    => 'gray',
        };
    }

    public function getChangeArrowAttribute(): string
    {
        return match($this->change_type) {
            'increase' => '↑',
            'decrease' => '↓',
            'stable'   => '→',
            default    => '',
        };
    }

    public function getChangeCssColorAttribute(): string
    {
        return match($this->change_type) {
            'increase' => 'var(--ok)',
            'decrease' => 'var(--danger)',
            'stable'   => 'var(--fg-dim)',
            default    => 'var(--fg-dim)',
        };
    }

    public function getChangeLabelAttribute(): string
    {
        return match($this->change_type) {
            'increase' => 'Price Increased',
            'decrease' => 'Price Decreased',
            'stable'   => 'Price Stable',
            default    => '',
        };
    }
}
