<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'ownership_id', 'user_id', 'logged_by', 'payment_group_id', 'amount_kobo',
        'paid_on', 'reference', 'bank', 'notes', 'proof_path',
        'flagged_at', 'flag_reason', 'confirmed_at', 'confirmed_by',
    ];

    protected $casts = [
        'paid_on'      => 'date',
        'flagged_at'   => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    public function getIsFlaggedAttribute(): bool
    {
        return !is_null($this->flagged_at);
    }

    public function getIsConfirmedAttribute(): bool
    {
        return !is_null($this->confirmed_at);
    }

    public function loggedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'logged_by');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function getIsSplitAttribute(): bool
    {
        return !is_null($this->payment_group_id);
    }

    /**
     * The other legs of the same transfer (empty for an ordinary payment).
     */
    public function groupSiblings()
    {
        if (!$this->payment_group_id) {
            return collect();
        }

        return static::where('payment_group_id', $this->payment_group_id)
            ->where('id', '!=', $this->id)
            ->with(['ownership.property', 'ownership.unit'])
            ->get();
    }

    /**
     * Full value of the transfer this payment belongs to — its own amount for
     * an ordinary payment, the sum of all legs for a split one.
     */
    public function getGroupTotalKoboAttribute(): int
    {
        if (!$this->payment_group_id) {
            return (int) $this->amount_kobo;
        }

        return (int) static::where('payment_group_id', $this->payment_group_id)->sum('amount_kobo');
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
        // Month paid, not month logged — buyers must not be able to tell when
        // a payment was entered into the portal.
        $ym = $this->paid_on->format('ym');
        $seq = str_pad($this->id, 3, '0', STR_PAD_LEFT);
        return "RCP-{$ym}-{$seq}";
    }

    /**
     * Which instalment this payment covers. Must use the same ordering as
     * PaymentController::recalculateSchedule (paid_on, then id) — ordering by id
     * alone put back-dated payments on a different instalment in the receipt
     * than in the schedule.
     */
    public function getInstalmentNumberAttribute(): int
    {
        return Payment::where('ownership_id', $this->ownership_id)
            ->where(function ($query) {
                $query->where('paid_on', '<', $this->paid_on)
                    ->orWhere(function ($q) {
                        $q->where('paid_on', $this->paid_on)->where('id', '<=', $this->id);
                    });
            })
            ->count();
    }
}
