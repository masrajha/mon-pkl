<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CheckInLocationSample extends Model
{
    protected $fillable = [
        'user_id',
        'internship_enrollment_id',
        'gps_latitude',
        'gps_longitude',
        'gps_accuracy_meters',
        'source',
        'captured_at',
        'used_at',
        'device_info',
    ];

    protected function casts(): array
    {
        return [
            'gps_latitude' => 'decimal:7',
            'gps_longitude' => 'decimal:7',
            'gps_accuracy_meters' => 'integer',
            'captured_at' => 'datetime',
            'used_at' => 'datetime',
            'device_info' => 'array',
        ];
    }

    public function enrollment()
    {
        return $this->belongsTo(InternshipEnrollment::class, 'internship_enrollment_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
