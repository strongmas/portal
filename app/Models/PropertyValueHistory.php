<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyValueHistory extends Model
{
    protected $fillable = ['property_unit_id', 'value_kobo', 'notes', 'recorded_by', 'recorded_at'];

    protected $casts = ['recorded_at' => 'date'];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(PropertyUnit::class, 'property_unit_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
