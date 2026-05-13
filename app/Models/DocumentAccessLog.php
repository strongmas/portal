<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentAccessLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['property_document_id', 'user_id', 'ip_address', 'accessed_at'];

    protected $casts = ['accessed_at' => 'datetime'];

    public function document(): BelongsTo
    {
        return $this->belongsTo(PropertyDocument::class, 'property_document_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
