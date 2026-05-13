<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PropertyDocument extends Model
{
    protected $fillable = [
        'property_id', 'ownership_id', 'title', 'description',
        'file_path', 'original_name', 'document_type', 'version',
        'is_buyer_visible', 'uploaded_by', 'status', 'flag_reason',
    ];

    protected $casts = [
        'is_buyer_visible' => 'boolean',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function ownership(): BelongsTo
    {
        return $this->belongsTo(Ownership::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function accessLogs(): HasMany
    {
        return $this->hasMany(DocumentAccessLog::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->document_type) {
            'contract'     => 'Contract / Agreement',
            'deed'         => 'Title Deed',
            'survey'       => 'Survey Plan',
            'approval'     => 'Approval / Permit',
            'report'       => 'Report',
            'receipt'      => 'Receipt',
            'offer_letter' => 'Offer Letter',
            default        => 'Document',
        };
    }

    public function getIconAttribute(): string
    {
        return match($this->document_type) {
            'contract', 'deed', 'offer_letter' => 'scroll',
            'survey'                            => 'map',
            'approval'                          => 'check-badge',
            'report'                            => 'chart-bar',
            'receipt'                           => 'receipt-percent',
            default                             => 'document',
        };
    }

    public function isFlagged(): bool
    {
        return $this->status === 'flagged';
    }

    public function isPendingReview(): bool
    {
        return $this->status === 'pending_review';
    }
}
