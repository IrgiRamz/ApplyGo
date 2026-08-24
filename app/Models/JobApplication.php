<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobApplication extends Model
{
    protected $fillable = [
        'user_id',
        'attachment_document_id',
        'recipient_email',
        'subject',
        'company_name',
        'position',
        'status',
        'scheduled_at',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attachmentDocument(): BelongsTo
    {
        return $this->belongsTo(AttachmentDocument::class);
    }

    // Status helpers
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isSent(): bool
    {
        return $this->status === 'sent';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    // Status badge color for UI
    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            'sent' => 'bg-success',
            'failed' => 'bg-danger',
            default => 'bg-warning',
        };
    }
}
