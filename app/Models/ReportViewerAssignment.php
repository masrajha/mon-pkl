<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportViewerAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'lecturer_id',
        'organization_id',
        'study_program_id',
        'level',
        'status',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
        ];
    }

    public function lecturer()
    {
        return $this->belongsTo(Lecturer::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function studyProgram()
    {
        return $this->belongsTo(StudyProgram::class);
    }
}
