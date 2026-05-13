<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectUpdate extends Model
{
    protected $fillable = [
        'property_id', 'title', 'body', 'progress_percentage',
        'stage', 'youtube_url', 'estimated_completion', 'posted_by',
    ];

    protected $casts = ['estimated_completion' => 'date'];

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

    public function comments(): HasMany
    {
        return $this->hasMany(UpdateComment::class);
    }

    public function getYoutubeEmbedUrlAttribute(): ?string
    {
        if (!$this->youtube_url) return null;

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
}
