<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubmissionProgress extends Model
{
    protected $table = 'submission_progress';

    protected $fillable = [
        'internship_enrollment_id',
        'deadline_type',
        'file_path',
        'uploaded_at',
        'status',
        'lecturer_note',
        'reviewed_by',
        'reviewed_at',
        'sanction_points',
    ];

    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
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

    public function sanctions()
    {
        return $this->hasMany(Sanction::class);
    }
}
