<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\InteractsWithTableControls;
use App\Models\InternshipEnrollment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FieldSupervisorController extends Controller
{
    use InteractsWithTableControls;

    public function index(Request $request): View
    {
        $query = InternshipEnrollment::query()
            ->with([
                'student',
                'studyProgram',
                'internshipPeriod.program',
                'internshipPlace',
                'fieldSupervisorAccessTokens',
            ])
            ->whereNotNull('field_supervisor_email')
            ->where('field_supervisor_email', '!=', '')
            ->whereNotIn('status', ['cancelled', 'rejected']);

        $this->scopeQuery($query, $request);

        if ($request->filled('q')) {
            $search = $request->string('q')->toString();
            $query->where(function ($query) use ($search): void {
                $query->where('field_supervisor', 'like', '%'.$search.'%')
                    ->orWhere('field_supervisor_email', 'like', '%'.$search.'%')
                    ->orWhereHas('student', fn ($student) => $student
                        ->where('full_name', 'like', '%'.$search.'%')
                        ->orWhere('npm', 'like', '%'.$search.'%'))
                    ->orWhereHas('internshipPlace', fn ($place) => $place->where('name', 'like', '%'.$search.'%'));
            });
        }

        $enrollments = $query
            ->orderBy('field_supervisor_email')
            ->latest('id')
            ->get();

        $emails = $enrollments
            ->map(fn (InternshipEnrollment $enrollment) => Str::lower(trim($enrollment->field_supervisor_email)))
            ->filter()
            ->unique()
            ->values();

        $usersByEmail = User::query()
            ->whereIn(DB::raw('LOWER(email)'), $emails)
            ->get()
            ->keyBy(fn (User $user) => Str::lower(trim($user->email)));

        $fieldSupervisors = $enrollments
            ->groupBy(fn (InternshipEnrollment $enrollment) => Str::lower(trim($enrollment->field_supervisor_email)))
            ->map(function ($items, string $email) use ($usersByEmail) {
                $first = $items->first();

                return [
                    'email' => $email,
                    'name' => $first?->field_supervisor ?: $usersByEmail->get($email)?->name ?: $email,
                    'phone' => $first?->field_supervisor_phone,
                    'user' => $usersByEmail->get($email),
                    'enrollments' => $items->values(),
                    'active_token_count' => $items->sum(fn (InternshipEnrollment $enrollment) => $enrollment->fieldSupervisorAccessTokens
                        ->filter(fn ($token) => $token->revoked_at === null && $token->expires_at->isFuture())
                        ->count()),
                ];
            })
            ->values();

        return view('management.field-supervisors.index', [
            'fieldSupervisors' => $fieldSupervisors,
            'selectedSearch' => $request->string('q')->toString(),
        ]);
    }

    public function createAccount(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('admin'), 403);

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $email = Str::lower(trim($data['email']));

        $enrollment = InternshipEnrollment::query()
            ->whereRaw('LOWER(field_supervisor_email) = ?', [$email])
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->latest('id')
            ->first();

        if (! $enrollment) {
            throw ValidationException::withMessages([
                'email' => 'Email ini belum tercatat sebagai pembimbing lapangan pada enrollment aktif.',
            ]);
        }

        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if ($user && $user->role !== 'pembimbing_lapangan') {
            throw ValidationException::withMessages([
                'email' => 'Email ini sudah digunakan oleh user dengan role '.$user->role.'. Gunakan email lain atau ubah kebijakan multi-role terlebih dahulu.',
            ]);
        }

        User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $enrollment->field_supervisor ?: $email,
                'password' => null,
                'role' => 'pembimbing_lapangan',
            ],
        );

        return back()->with('status', 'Akun login pembimbing lapangan berhasil dibuat atau sudah tersedia.');
    }

    private function scopeQuery($query, Request $request): void
    {
        $user = $request->user();

        if ($user?->hasRole('admin')) {
            return;
        }

        $assignments = $user?->lecturer?->coordinatorAssignments()
            ->where('status', 'active')
            ->get(['internship_period_id', 'study_program_id']) ?? collect();

        if ($assignments->isEmpty()) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where(function ($query) use ($assignments): void {
            foreach ($assignments as $assignment) {
                $query->orWhere(function ($query) use ($assignment): void {
                    $query->where('internship_period_id', $assignment->internship_period_id)
                        ->where('study_program_id', $assignment->study_program_id);
                });
            }
        });
    }
}
