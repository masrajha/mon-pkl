<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'study_program_id',
        'npm',
        'full_name',
        'student_email',
        'phone',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function studyProgram()
    {
        return $this->belongsTo(StudyProgram::class);
    }

    public function enrollments()
    {
        return $this->hasMany(InternshipEnrollment::class);
    }

    public function orientationAttendances()
    {
        return $this->hasMany(OrientationAttendance::class);
    }

    public function placeProposals()
    {
        return $this->hasMany(InternshipPlaceProposal::class);
    }
}
