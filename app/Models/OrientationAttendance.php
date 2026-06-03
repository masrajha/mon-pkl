<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrientationAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'orientation_event_id',
        'internship_enrollment_id',
        'student_id',
        'checked_at',
        'student_latitude',
        'student_longitude',
        'event_latitude',
        'event_longitude',
        'distance_meters',
        'device_info',
        'source_url',
        'photo_path',
    ];

    protected function casts(): array
    {
        return [
            'checked_at' => 'datetime',
            'student_latitude' => 'decimal:7',
            'student_longitude' => 'decimal:7',
            'event_latitude' => 'decimal:7',
            'event_longitude' => 'decimal:7',
            'device_info' => 'array',
        ];
    }

    public function event()
    {
        return $this->belongsTo(OrientationEvent::class, 'orientation_event_id');
    }

    public function enrollment()
    {
        return $this->belongsTo(InternshipEnrollment::class, 'internship_enrollment_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
