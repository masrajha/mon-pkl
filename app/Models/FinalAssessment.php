<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinalAssessment extends Model
{
    protected $fillable = [
        'internship_enrollment_id',
        'lecturer_score',
        'field_supervisor_score',
        'lecturer_weight',
        'field_supervisor_weight',
        'base_score',
        'suggested_deduction',
        'final_deduction',
        'final_score',
        'note',
        'document_number',
        'document_city',
        'chair_name',
        'chair_identifier',
        'coordinator_name',
        'coordinator_identifier',
        'document_header_snapshot',
        'verification_token',
        'finalized_by',
        'finalized_at',
    ];

    protected function casts(): array
    {
        return [
            'lecturer_score' => 'decimal:2',
            'field_supervisor_score' => 'decimal:2',
            'lecturer_weight' => 'decimal:2',
            'field_supervisor_weight' => 'decimal:2',
            'base_score' => 'decimal:2',
            'suggested_deduction' => 'decimal:2',
            'final_deduction' => 'decimal:2',
            'final_score' => 'decimal:2',
            'document_header_snapshot' => 'array',
            'finalized_at' => 'datetime',
        ];
    }

    public function enrollment()
    {
        return $this->belongsTo(InternshipEnrollment::class, 'internship_enrollment_id');
    }

    public function finalizer()
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }
}
