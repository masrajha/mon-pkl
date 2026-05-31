<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lecturer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'study_program_id',
        'name',
        'email',
        'nip',
        'nidn',
        'status',
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
        return $this->hasMany(InternshipEnrollment::class, 'lecturer_supervisor_id');
    }

    public function coordinatorAssignments()
    {
        return $this->hasMany(InternshipCoordinator::class);
    }
}
