<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class AttachmentDocument extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'file_path',
        'file_name',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Get full storage path
    public function getFullPathAttribute(): string
    {
        return Storage::path($this->file_path);
    }

    // Check if file exists
    public function fileExists(): bool
    {
        return Storage::exists($this->file_path);
    }

    // Delete file from storage
    public function deleteFile(): bool
    {
        if ($this->fileExists()) {
            return Storage::delete($this->file_path);
        }
        return true;
    }
}
