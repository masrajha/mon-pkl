<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeriodDeadline extends Model
{
    protected $fillable = [
        'internship_period_id',
        'deadline_type',
        'deadline_date',
        'penalty_points',
        'is_fixed_penalty',
    ];

    protected function casts(): array
    {
        return [
            'deadline_date' => 'date',
            'is_fixed_penalty' => 'boolean',
        ];
    }

    public function internshipPeriod()
    {
        return $this->belongsTo(InternshipPeriod::class);
    }
}
