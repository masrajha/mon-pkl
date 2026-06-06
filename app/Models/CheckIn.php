<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckIn extends Model
{
    use HasFactory;

    protected $fillable = [
        'internship_enrollment_id',
        'legacy_firebase_key',
        'legacy_source_file',
        'type',
        'action',
        'source_type',
        'forgotten_attendance_request_id',
        'note',
        'checked_at',
        'pair_id',
        'student_latitude',
        'student_longitude',
        'office_latitude',
        'office_longitude',
        'distance_meters',
        'duration_minutes',
        'sanction_points',
        'daily_log_validated_at',
        'daily_log_validated_by_name',
        'daily_log_validated_by_email',
        'daily_log_validation_mode',
        'daily_log_validation_note',
        'device_info',
        'source_url',
        'photo_path',
        'source_photo_url',
        'legacy_geojson_type',
    ];

    protected function casts(): array
    {
        return [
            'checked_at' => 'datetime',
            'daily_log_validated_at' => 'datetime',
            'student_latitude' => 'decimal:7',
            'student_longitude' => 'decimal:7',
            'office_latitude' => 'decimal:7',
            'office_longitude' => 'decimal:7',
            'device_info' => 'array',
        ];
    }

    public function enrollment()
    {
        return $this->belongsTo(InternshipEnrollment::class, 'internship_enrollment_id');
    }

    public function forgottenAttendanceRequest()
    {
        return $this->belongsTo(ForgottenAttendanceRequest::class);
    }

    public function pair()
    {
        return $this->belongsTo(CheckIn::class, 'pair_id');
    }

    public function pairedCheckOuts()
    {
        return $this->hasMany(CheckIn::class, 'pair_id');
    }
}
