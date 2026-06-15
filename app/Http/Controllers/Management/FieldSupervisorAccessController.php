<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\FieldSupervisorAccessToken;
use App\Models\InternshipEnrollment;
use App\Services\FieldSupervisorEmailNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FieldSupervisorAccessController extends Controller
{
    public function sendPortalAccess(Request $request, FieldSupervisorEmailNotificationService $fieldSupervisorEmails): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $email = Str::lower(trim($data['email']));
        $query = InternshipEnrollment::query()
            ->with(['student', 'studyProgram', 'internshipPeriod.program', 'internshipPlace'])
            ->whereRaw('LOWER(field_supervisor_email) = ?', [$email])
            ->whereNotIn('status', ['cancelled', 'rejected']);

        $this->scopeEnrollmentQuery($request, $query);

        $enrollments = $query->latest('id')->get();

        if ($enrollments->isEmpty()) {
            throw ValidationException::withMessages([
                'email' => 'Email ini tidak ditemukan pada peserta periode yang dapat Anda kelola.',
            ]);
        }

        $fieldSupervisorEmails->queuePortalAccess($email, $enrollments);

        return back()->with('status', 'Akses portal pembimbing lapangan masuk antrean pengiriman email.');
    }

    public function store(Request $request, InternshipEnrollment $enrollment, FieldSupervisorEmailNotificationService $fieldSupervisorEmails): RedirectResponse
    {
        $this->authorizeAccessManagement($request, $enrollment);
        $enrollment->loadMissing(['student', 'studyProgram', 'internshipPeriod.program', 'internshipPlace']);

        if (blank($enrollment->field_supervisor_email)) {
            throw ValidationException::withMessages([
                'field_supervisor_email' => 'Email pembimbing lapangan wajib diisi sebelum mengirim akses.',
            ]);
        }

        $fieldSupervisorEmails->createTokenAndQueueAccess($enrollment, $request->user()?->id);

        return back()->with('status', 'Token akses pembimbing lapangan dibuat dan email masuk antrean pengiriman.');
    }

    public function destroy(Request $request, InternshipEnrollment $enrollment): RedirectResponse
    {
        $this->authorizeAccessManagement($request, $enrollment);

        FieldSupervisorAccessToken::query()
            ->where('internship_enrollment_id', $enrollment->id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);

        return back()->with('status', 'Token aktif pembimbing lapangan berhasil dicabut.');
    }

    private function authorizeAccessManagement(Request $request, InternshipEnrollment $enrollment): void
    {
        $user = $request->user();

        if ($user?->hasRole('admin')) {
            return;
        }

        $allowed = $user?->lecturer?->coordinatorAssignments()
            ->where('status', 'active')
            ->where('internship_period_id', $enrollment->internship_period_id)
            ->where('study_program_id', $enrollment->study_program_id)
            ->exists();

        abort_unless($allowed, 403);
    }

    private function scopeEnrollmentQuery(Request $request, $query): void
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
