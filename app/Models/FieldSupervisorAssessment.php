<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FieldSupervisorAssessment extends Model
{
    protected $fillable = [
        'internship_enrollment_id',
        'scores',
        'rubric_snapshot',
        'discipline_score',
        'teamwork_score',
        'performance_score',
        'final_score',
        'note',
        'student_general_note',
        'student_recommendation',
        'institution_feedback',
        'survey_snapshot',
        'institution_note',
        'assessed_by_name',
        'assessed_by_email',
        'assessment_mode',
        'assessed_at',
    ];

    protected function casts(): array
    {
        return [
            'scores' => 'array',
            'rubric_snapshot' => 'array',
            'institution_feedback' => 'array',
            'survey_snapshot' => 'array',
            'discipline_score' => 'decimal:2',
            'teamwork_score' => 'decimal:2',
            'performance_score' => 'decimal:2',
            'final_score' => 'decimal:2',
            'assessed_at' => 'datetime',
        ];
    }

    public function enrollment()
    {
        return $this->belongsTo(InternshipEnrollment::class, 'internship_enrollment_id');
    }
}
