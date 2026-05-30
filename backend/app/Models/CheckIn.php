<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckIn extends Model
{
    use HasFactory;

    protected $fillable = [
        'internship_enrollment_id',
        'type',
        'note',
        'checked_at',
        'student_latitude',
        'student_longitude',
        'office_latitude',
        'office_longitude',
        'distance_meters',
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
}
