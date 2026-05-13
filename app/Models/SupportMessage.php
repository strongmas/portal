<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class SupportMessage extends Model
{
    protected $fillable = [
        'user_id', 'sender', 'body', 'attachment_path',
        'attachment_name', 'attachment_size', 'attachment_mime', 'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getHasAttachmentAttribute(): bool
    {
        return !is_null($this->attachment_path);
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        return null; // Served via protected route only
    }

    public function getIsImageAttribute(): bool
    {
        return in_array($this->attachment_mime, ['image/jpeg', 'image/png', 'image/jpg']);
    }

    public function getFileSizeHumanAttribute(): string
    {
        if (!$this->attachment_size) return '';
        $bytes = $this->attachment_size;
        if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024) return round($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }
}
