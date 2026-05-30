<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'legacy_index',
        'legacy_key',
    ];

    public function internshipPlaces()
    {
        return $this->hasMany(InternshipPlace::class);
    }
}
