<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BrowserNotification extends Model
{
    protected $fillable = [
        'user_id',
        'event_key',
        'type',
        'title',
        'body',
        'action_url',
        'data',
        'scheduled_for',
        'shown_at',
        'pushed_at',
        'push_attempts',
        'push_last_error',
        'read_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'scheduled_for' => 'datetime',
            'shown_at' => 'datetime',
            'pushed_at' => 'datetime',
            'read_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
