<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InternshipPlaceProposal extends Model
{
    use HasFactory;

    protected $fillable = [
        'internship_period_id',
        'study_program_id',
        'student_id',
        'proposed_by',
        'name',
        'address',
        'city_id',
        'city_name',
        'latitude',
        'longitude',
        'field_supervisor_name',
        'field_supervisor_phone',
        'status',
        'admin_note',
        'approved_internship_place_id',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'reviewed_at' => 'datetime',
        ];
    }

    public function internshipPeriod()
    {
        return $this->belongsTo(InternshipPeriod::class);
    }

    public function studyProgram()
    {
        return $this->belongsTo(StudyProgram::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function proposer()
    {
        return $this->belongsTo(User::class, 'proposed_by');
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function approvedPlace()
    {
        return $this->belongsTo(InternshipPlace::class, 'approved_internship_place_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
