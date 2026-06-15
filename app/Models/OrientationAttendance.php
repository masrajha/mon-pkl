<?php

namespace App\Models;

use App\Models\Concerns\HasLocalDateTimes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrientationAttendance extends Model
{
    use HasFactory;
    use HasLocalDateTimes;

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
            'student_latitude' => 'decimal:7',
            'student_longitude' => 'decimal:7',
            'event_latitude' => 'decimal:7',
            'event_longitude' => 'decimal:7',
            'device_info' => 'array',
        ];
    }

    protected function checkedAt(): Attribute
    {
        return $this->localDateTimeAttribute();
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
