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
        'attendance_starts_at',
        'attendance_ends_at',
        'lecturer_supervisor_id',
        'lecturer_supervisor_user_id',
        'lecturer_supervisor',
        'field_supervisor',
        'field_supervisor_phone',
        'field_supervisor_email',
        'contact_student_phone',
        'has_krs_pkl',
        'total_sks',
        'current_semester',
        'gpa',
        'status',
        'admin_note',
        'registration_document_path',
        'final_report_path',
        'total_sanctions_points',
        'legacy_source_file',
        'legacy_period_label',
    ];

    protected function casts(): array
    {
        return [
            'attendance_starts_at' => 'date',
            'attendance_ends_at' => 'date',
            'has_krs_pkl' => 'boolean',
            'gpa' => 'decimal:2',
        ];
    }

    public function effectiveAttendanceStartsAt()
    {
        return $this->attendance_starts_at ?? $this->internshipPeriod?->starts_at;
    }

    public function effectiveAttendanceEndsAt()
    {
        return $this->attendance_ends_at ?? $this->internshipPeriod?->ends_at;
    }

    public function hasAttendanceOverride(): bool
    {
        return filled($this->attendance_starts_at) || filled($this->attendance_ends_at);
    }

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

    public function orientationAttendances()
    {
        return $this->hasMany(OrientationAttendance::class);
    }

    public function submissionProgress()
    {
        return $this->hasMany(SubmissionProgress::class);
    }

    public function seminarRequests()
    {
        return $this->hasMany(SeminarRequest::class, 'internship_enrollment_id');
    }

    public function fieldSupervisorAccessTokens()
    {
        return $this->hasMany(FieldSupervisorAccessToken::class, 'internship_enrollment_id');
    }

    public function fieldSupervisorAssessment()
    {
        return $this->hasOne(FieldSupervisorAssessment::class, 'internship_enrollment_id');
    }

    public function finalAssessment()
    {
        return $this->hasOne(FinalAssessment::class, 'internship_enrollment_id');
    }

    public function sanctions()
    {
        return $this->hasMany(Sanction::class);
    }

    public function relocationRequests()
    {
        return $this->hasMany(RelocationRequest::class, 'internship_enrollment_id');
    }

    public function supervisorChangeRequests()
    {
        return $this->hasMany(SupervisorChangeRequest::class, 'internship_enrollment_id');
    }
}
