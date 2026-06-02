<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sanction extends Model
{
    protected $fillable = [
        'internship_enrollment_id',
        'submission_progress_id',
        'sanction_type',
        'points_deducted',
        'reason',
        'date',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function enrollment()
    {
        return $this->belongsTo(InternshipEnrollment::class, 'internship_enrollment_id');
    }

    public function submissionProgress()
    {
        return $this->belongsTo(SubmissionProgress::class);
    }
}
