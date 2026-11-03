<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class ProjectUpdate extends Model
{
    protected $fillable = [
        'property_id', 'title', 'body', 'progress_percentage',
        'stage', 'youtube_url', 'estimated_completion', 'posted_by',
        'video_path', 'video_expires_at',
    ];

    protected $casts = [
        'estimated_completion' => 'date',
        'video_expires_at' => 'datetime',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ProjectUpdateDocument::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProjectUpdateImage::class)->orderBy('sort_order');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(UpdateComment::class);
    }

    public function getYoutubeEmbedUrlAttribute(): ?string
    {
        if (!$this->youtube_url) return null;

        if (preg_match('#^https://player\.cloudinary\.com/embed/\?#', $this->youtube_url)) {
            return $this->youtube_url;
        }

        $id = null;
        if (preg_match('/[?&]v=([^&#]+)/', $this->youtube_url, $m)) {
            $id = $m[1];
        } elseif (preg_match('/youtu\.be\/([^?&#]+)/', $this->youtube_url, $m)) {
            $id = $m[1];
        } elseif (preg_match('/youtube\.com\/embed\/([^?&#]+)/', $this->youtube_url, $m)) {
            $id = $m[1];
        }

        return $id ? "https://www.youtube.com/embed/{$id}" : null;
    }

    public function getVideoUrlAttribute(): ?string
    {
        if (!$this->video_path || $this->isVideoExpired()) {
            return null;
        }
        return Storage::disk('public')->url($this->video_path);
    }

    public function hasVideo(): bool
    {
        return !is_null($this->video_path) && !$this->isVideoExpired();
    }

    public function isVideoExpired(): bool
    {
        return $this->video_expires_at && now()->gt($this->video_expires_at);
    }
}
