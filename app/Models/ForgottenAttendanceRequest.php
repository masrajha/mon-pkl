<?php

namespace App\Models;

use App\Models\Concerns\HasLocalDateTimes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class ForgottenAttendanceRequest extends Model
{
    use HasLocalDateTimes;

    protected $fillable = [
        'internship_enrollment_id',
        'action',
        'requested_date',
        'requested_time',
        'requested_checked_at',
        'note',
        'reason',
        'student_latitude',
        'student_longitude',
        'office_latitude',
        'office_longitude',
        'distance_meters',
        'photo_path',
        'device_info',
        'status',
        'reviewed_by',
        'reviewed_by_name',
        'reviewed_by_email',
        'reviewed_by_role',
        'reviewed_at',
        'review_note',
        'created_check_in_id',
    ];

    protected function casts(): array
    {
        return [
            'requested_date' => 'date',
            'student_latitude' => 'decimal:7',
            'student_longitude' => 'decimal:7',
            'office_latitude' => 'decimal:7',
            'office_longitude' => 'decimal:7',
            'device_info' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    protected function requestedCheckedAt(): Attribute
    {
        return $this->localDateTimeAttribute();
    }

    public function enrollment()
    {
        return $this->belongsTo(InternshipEnrollment::class, 'internship_enrollment_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function createdCheckIn()
    {
        return $this->belongsTo(CheckIn::class, 'created_check_in_id');
    }
}
