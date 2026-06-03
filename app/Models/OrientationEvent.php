<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrientationEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'internship_period_id',
        'program_id',
        'study_program_id',
        'name',
        'location_name',
        'latitude',
        'longitude',
        'starts_at',
        'ends_at',
        'max_distance_meters',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function internshipPeriod()
    {
        return $this->belongsTo(InternshipPeriod::class);
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function studyProgram()
    {
        return $this->belongsTo(StudyProgram::class);
    }

    public function attendances()
    {
        return $this->hasMany(OrientationAttendance::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForEnrollment(Builder $query, InternshipEnrollment $enrollment): Builder
    {
        return $query
            ->where('internship_period_id', $enrollment->internship_period_id)
            ->where(function (Builder $query) use ($enrollment): void {
                $query
                    ->whereNull('study_program_id')
                    ->orWhere('study_program_id', $enrollment->study_program_id);
            });
    }
}
