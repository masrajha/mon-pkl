<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Support\LocalClock;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'firebase_uid',
        'avatar_url',
        'role',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => 'string',
        ];
    }

    public function student()
    {
        return $this->hasOne(Student::class);
    }

    public function lecturer()
    {
        return $this->hasOne(Lecturer::class);
    }

    public function isCoordinator(): bool
    {
        return $this->lecturer()
            ->whereHas('coordinatorAssignments', fn ($query) => $query->where('status', 'active'))
            ->exists();
    }

    public function isReportViewer(): bool
    {
        $today = LocalClock::today()->toDateString();

        return $this->lecturer()
            ->whereHas('reportViewerAssignments', fn ($query) => $query
                ->where('status', 'active')
                ->where(fn ($query) => $query->whereNull('starts_at')->orWhereDate('starts_at', '<=', $today))
                ->where(fn ($query) => $query->whereNull('ends_at')->orWhereDate('ends_at', '>=', $today)))
            ->exists();
    }

    public function isActive(): bool
    {
        return ($this->status ?: 'active') === 'active';
    }

    public function hasRole(string|array $roles): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        if (in_array('koordinator', (array) $roles, true) && $this->isCoordinator()) {
            return true;
        }

        if (in_array('report_viewer', (array) $roles, true) && $this->isReportViewer()) {
            return true;
        }

        return in_array($this->role, (array) $roles, true);
    }
}
