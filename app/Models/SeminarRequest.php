<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeminarRequest extends Model
{
    protected $fillable = [
        'internship_enrollment_id',
        'title',
        'proposed_date',
        'proposed_time',
        'mode',
        'location',
        'meeting_url',
        'approval_method',
        'seminar_document_path',
        'manual_acc_path',
        'status',
        'student_note',
        'lecturer_note',
        'admin_note',
        'scheduled_at',
        'lecturer_approved_by',
        'lecturer_approved_at',
        'manual_acc_validated_by',
        'manual_acc_validated_at',
        'scheduled_by',
        'completed_at',
        'seminar_score',
        'seminar_score_note',
        'assessment_method',
        'assessment_scores',
        'assessment_rubric_snapshot',
        'assessment_file_path',
        'assessment_validated_by',
        'assessment_validated_at',
        'scored_by',
        'scored_at',
    ];

    protected function casts(): array
    {
        return [
            'proposed_date' => 'date',
            'scheduled_at' => 'datetime',
            'lecturer_approved_at' => 'datetime',
            'manual_acc_validated_at' => 'datetime',
            'completed_at' => 'datetime',
            'seminar_score' => 'decimal:2',
            'assessment_scores' => 'array',
            'assessment_rubric_snapshot' => 'array',
            'assessment_validated_at' => 'datetime',
            'scored_at' => 'datetime',
        ];
    }

    public function enrollment()
    {
        return $this->belongsTo(InternshipEnrollment::class, 'internship_enrollment_id');
    }

    public function lecturerApprover()
    {
        return $this->belongsTo(User::class, 'lecturer_approved_by');
    }

    public function manualAccValidator()
    {
        return $this->belongsTo(User::class, 'manual_acc_validated_by');
    }

    public function scheduler()
    {
        return $this->belongsTo(User::class, 'scheduled_by');
    }

    public function scorer()
    {
        return $this->belongsTo(User::class, 'scored_by');
    }

    public function assessmentValidator()
    {
        return $this->belongsTo(User::class, 'assessment_validated_by');
    }
}
