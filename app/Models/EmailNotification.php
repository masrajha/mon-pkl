<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class EmailNotification extends Model
{
    protected $fillable = [
        'event_key',
        'type',
        'recipient_email',
        'recipient_name',
        'subject',
        'body_lines',
        'action_text',
        'action_url',
        'notifiable_type',
        'notifiable_id',
        'payload',
        'status',
        'attempts',
        'scheduled_for',
        'sent_at',
        'failed_at',
        'error_message',
    ];

    protected $casts = [
        'body_lines' => 'array',
        'payload' => 'array',
        'scheduled_for' => 'datetime',
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeDue(Builder $query): Builder
    {
        return $query
            ->where('status', 'pending')
            ->where(fn (Builder $query) => $query
                ->whereNull('scheduled_for')
                ->orWhere('scheduled_for', '<=', now()));
    }
}
