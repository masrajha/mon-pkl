<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InternshipEnrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'study_program_id',
        'internship_period_id',
        'internship_place_id',
        'lecturer_supervisor_id',
        'lecturer_supervisor_user_id',
        'lecturer_supervisor',
        'field_supervisor',
        'field_supervisor_phone',
        'contact_student_phone',
        'status',
        'legacy_source_file',
        'legacy_period_label',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function studyProgram()
    {
        return $this->belongsTo(StudyProgram::class);
    }

    public function internshipPeriod()
    {
        return $this->belongsTo(InternshipPeriod::class);
    }

    public function internshipPlace()
    {
        return $this->belongsTo(InternshipPlace::class);
    }

    public function lecturer()
    {
        return $this->belongsTo(Lecturer::class, 'lecturer_supervisor_id');
    }

    public function lecturerSupervisor()
    {
        return $this->belongsTo(User::class, 'lecturer_supervisor_user_id');
    }

    public function checkIns()
    {
        return $this->hasMany(CheckIn::class);
    }
}
