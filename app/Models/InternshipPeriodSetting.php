<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InternshipPeriodSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'internship_period_id',
        'settings',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }

    public function internshipPeriod()
    {
        return $this->belongsTo(InternshipPeriod::class);
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
