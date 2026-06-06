<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\FieldSupervisorAccessToken;
use App\Models\InternshipEnrollment;
use App\Services\FieldSupervisorEmailNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FieldSupervisorAccessController extends Controller
{
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
}
