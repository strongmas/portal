<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnitTypeImage extends Model
{
    protected $fillable = [
        'property_unit_id', 'room_type', 'path', 'caption', 'sort_order',
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(PropertyUnit::class, 'property_unit_id');
    }

    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->path);
    }

    public static array $roomTypes = [
        'exterior'    => 'Exterior',
        'bedroom'     => 'Bedroom',
        'kitchen'     => 'Kitchen',
        'bathroom'    => 'Bathroom',
        'living_area' => 'Living Area',
    ];
}
