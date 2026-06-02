<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InternshipPlace extends Model
{
    use HasFactory;

    protected $fillable = [
        'city_id',
        'legacy_firebase_key',
        'legacy_source_file',
        'name',
        'address',
        'field_supervisor_name',
        'field_supervisor_phone',
        'contact_student_phone',
        'latitude',
        'longitude',
        'visited',
        'is_active',
        'legacy_created_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'visited' => 'boolean',
            'is_active' => 'boolean',
            'legacy_created_at' => 'datetime',
        ];
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function enrollments()
    {
        return $this->hasMany(InternshipEnrollment::class);
    }
}
