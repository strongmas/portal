<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class Property extends Model
{
    protected $fillable = [
        'code', 'development_name', 'address', 'cover_image_path',
        'lat', 'lng', 'is_off_plan', 'project_status', 'description',
    ];

    protected $casts = [
        'is_off_plan' => 'boolean',
        'lat'         => 'decimal:7',
        'lng'         => 'decimal:7',
    ];

    // ── Existing relationships ─────────────────────────────────────────────

    public function units(): HasMany
    {
        return $this->hasMany(PropertyUnit::class);
    }

    public function ownerships(): HasMany
    {
        return $this->hasMany(Ownership::class);
    }

    public function buyers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'ownerships')
            ->withPivot(['agreed_price_kobo', 'start_date', 'expected_completion_date', 'payment_plan', 'total_instalments', 'status'])
            ->withTimestamps();
    }

    // ── New relationships ──────────────────────────────────────────────────

    public function images(): HasMany
    {
        return $this->hasMany(PropertyImage::class)->orderBy('sort_order');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(PropertyDocument::class)->latest();
    }

    public function projectUpdates(): HasMany
    {
        return $this->hasMany(ProjectUpdate::class)->latest();
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(ProjectMilestone::class)->orderBy('sort_order');
    }

    public function interestRegistrations(): HasMany
    {
        return $this->hasMany(InterestRegistration::class);
    }

    // ── Computed attributes ────────────────────────────────────────────────

    public function getFullNameAttribute(): string
    {
        return $this->development_name;
    }

    public function getCoverUrlAttribute(): ?string
    {
        $images = $this->relationLoaded('images') ? $this->images : $this->images()->get();
        $cover  = $images->firstWhere('is_cover', true) ?? $images->first();
        if ($cover) return Storage::disk('public')->url($cover->path);

        if ($this->cover_image_path) {
            return Storage::disk('public')->url($this->cover_image_path);
        }

        return null;
    }
}
