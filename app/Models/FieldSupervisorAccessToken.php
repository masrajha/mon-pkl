<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FieldSupervisorAccessToken extends Model
{
    protected $fillable = [
        'internship_enrollment_id',
        'email',
        'token_hash',
        'expires_at',
        'revoked_at',
        'created_by',
        'last_accessed_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'last_accessed_at' => 'datetime',
        ];
    }

    public function enrollment()
    {
        return $this->belongsTo(InternshipEnrollment::class, 'internship_enrollment_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isValid(): bool
    {
        return $this->revoked_at === null && $this->expires_at->isFuture();
    }
}
