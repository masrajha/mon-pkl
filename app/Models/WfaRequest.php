<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WfaRequest extends Model
{
    protected $fillable = [
        'internship_enrollment_id',
        'starts_at',
        'ends_at',
        'planned_location',
        'planned_latitude',
        'planned_longitude',
        'planned_activity',
        'reason',
        'evidence_path',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_note',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
            'planned_latitude' => 'decimal:7',
            'planned_longitude' => 'decimal:7',
            'reviewed_at' => 'datetime',
        ];
    }

    public function enrollment()
    {
        return $this->belongsTo(InternshipEnrollment::class, 'internship_enrollment_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function checkIns()
    {
        return $this->hasMany(CheckIn::class);
    }
}
