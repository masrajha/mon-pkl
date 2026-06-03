<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RelocationRequest extends Model
{
    protected $fillable = [
        'internship_enrollment_id',
        'current_internship_place_id',
        'new_internship_place_id',
        'reason',
        'attachment_path',
        'status',
        'reviewed_by',
        'reviewed_at',
        'admin_note',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function enrollment()
    {
        return $this->belongsTo(InternshipEnrollment::class, 'internship_enrollment_id');
    }

    public function currentPlace()
    {
        return $this->belongsTo(InternshipPlace::class, 'current_internship_place_id');
    }

    public function newPlace()
    {
        return $this->belongsTo(InternshipPlace::class, 'new_internship_place_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function emailNotifications()
    {
        return $this->morphMany(EmailNotification::class, 'notifiable');
    }
}
