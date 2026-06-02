<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InternshipPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'program_id',
        'academic_year',
        'semester',
        'batch',
        'starts_at',
        'ends_at',
        'is_active',
        'is_locked',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
            'is_active' => 'boolean',
            'is_locked' => 'boolean',
        ];
    }

    public function enrollments()
    {
        return $this->hasMany(InternshipEnrollment::class);
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function getDisplayNameAttribute(): string
    {
        return trim(($this->program?->name ? $this->program->name.' - ' : '').$this->name);
    }

    public function setting()
    {
        return $this->hasOne(InternshipPeriodSetting::class);
    }

    public function deadlines()
    {
        return $this->hasMany(PeriodDeadline::class);
    }
}
