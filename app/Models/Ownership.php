<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ownership extends Model
{
    protected $fillable = [
        'user_id', 'property_id', 'property_unit_id', 'agreed_price_kobo',
        'start_date', 'expected_completion_date',
        'payment_plan', 'total_instalments', 'instalment_frequency', 'status',
        'purchase_date', 'current_value_kobo',
    ];

    protected $casts = [
        'start_date'               => 'date',
        'expected_completion_date' => 'date',
        'purchase_date'            => 'date',
    ];

    // ── Existing relationships ─────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(PropertyUnit::class, 'property_unit_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function instalments(): HasMany
    {
        return $this->hasMany(Instalment::class);
    }

    // ── New relationships ──────────────────────────────────────────────────

    public function members(): HasMany
    {
        return $this->hasMany(OwnershipMember::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    // ── Computed attributes ────────────────────────────────────────────────

    public function getDisplayNameAttribute(): string
    {
        $name = $this->property->development_name ?? 'Property';
        if ($this->unit) {
            $name .= ' · ' . $this->unit->label;
        }
        return $name;
    }

    public function getPaidKoboAttribute(): int
    {
        if ($this->relationLoaded('payments')) {
            return (int) $this->payments->sum('amount_kobo');
        }
        return (int) $this->payments()->sum('amount_kobo');
    }

    public function getOutstandingKoboAttribute(): int
    {
        return max(0, $this->agreed_price_kobo - $this->paid_kobo);
    }

    public function getPercentPaidAttribute(): float
    {
        if (!$this->agreed_price_kobo) return 0;
        return round(($this->paid_kobo / $this->agreed_price_kobo) * 100, 1);
    }

    public function getInstalmentsPaidCountAttribute(): int
    {
        return $this->payments()->count();
    }

    public function getNextDueInstalmentAttribute(): ?Instalment
    {
        return $this->instalments()
            ->whereIn('status', ['due', 'upcoming'])
            ->orderBy('due_date')
            ->first();
    }
}
