<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupervisorChangeRequest extends Model
{
    protected $fillable = [
        'internship_enrollment_id',
        'current_lecturer_supervisor_id',
        'requested_lecturer_supervisor_id',
        'current_field_supervisor',
        'requested_field_supervisor',
        'current_field_supervisor_phone',
        'requested_field_supervisor_phone',
        'reason',
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

    public function currentLecturer()
    {
        return $this->belongsTo(Lecturer::class, 'current_lecturer_supervisor_id');
    }

    public function requestedLecturer()
    {
        return $this->belongsTo(Lecturer::class, 'requested_lecturer_supervisor_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
