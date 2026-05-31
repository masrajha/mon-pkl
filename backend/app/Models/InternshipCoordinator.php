<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InternshipCoordinator extends Model
{
    use HasFactory;

    protected $fillable = [
        'lecturer_id',
        'internship_period_id',
        'study_program_id',
        'status',
    ];

    public function lecturer()
    {
        return $this->belongsTo(Lecturer::class);
    }

    public function internshipPeriod()
    {
        return $this->belongsTo(InternshipPeriod::class);
    }

    public function studyProgram()
    {
        return $this->belongsTo(StudyProgram::class);
    }
}
