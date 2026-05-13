<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class PropertyUnit extends Model
{
    protected $fillable = [
        'property_id', 'bedrooms', 'unit_type', 'list_price_kobo', 'total_units', 'notes',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function ownerships(): HasMany
    {
        return $this->hasMany(Ownership::class);
    }

    public function valueHistories(): HasMany
    {
        return $this->hasMany(PropertyValueHistory::class)->orderBy('recorded_at');
    }

    public function images(): HasMany
    {
        return $this->hasMany(UnitTypeImage::class)->orderBy('sort_order');
    }

    public function interestRegistrations(): HasMany
    {
        return $this->hasMany(InterestRegistration::class);
    }

    public function getLabelAttribute(): string
    {
        $type = ucfirst($this->unit_type);
        return $this->bedrooms ? "{$this->bedrooms} Bedroom {$type}" : $type;
    }
}
