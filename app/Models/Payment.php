<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'ownership_id', 'user_id', 'amount_kobo',
        'paid_on', 'reference', 'bank', 'notes', 'proof_path',
        'flagged_at', 'flag_reason',
    ];

    protected $casts = [
        'paid_on'    => 'date',
        'flagged_at' => 'datetime',
    ];

    public function getIsFlaggedAttribute(): bool
    {
        return !is_null($this->flagged_at);
    }

    public function ownership(): BelongsTo
    {
        return $this->belongsTo(Ownership::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getReceiptNumberAttribute(): string
    {
        $ym = $this->created_at->format('ym');
        $seq = str_pad($this->id, 3, '0', STR_PAD_LEFT);
        return "RCP-{$ym}-{$seq}";
    }

    public function getInstalmentNumberAttribute(): int
    {
        return Payment::where('ownership_id', $this->ownership_id)
            ->where('id', '<=', $this->id)
            ->count();
    }
}
