<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\FieldSupervisorAccessToken;
use App\Models\InternshipEnrollment;
use App\Services\EmailNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FieldSupervisorAccessController extends Controller
{
    public function store(Request $request, InternshipEnrollment $enrollment, EmailNotificationService $emails): RedirectResponse
    {
        $this->authorizeAccessManagement($request, $enrollment);
        $enrollment->loadMissing(['student', 'studyProgram', 'internshipPeriod.program', 'internshipPlace']);

        if (blank($enrollment->field_supervisor_email)) {
            throw ValidationException::withMessages([
                'field_supervisor_email' => 'Email pembimbing lapangan wajib diisi sebelum mengirim akses.',
            ]);
        }

        $rawToken = Str::random(64);
        $email = Str::lower(trim($enrollment->field_supervisor_email));

        $accessToken = FieldSupervisorAccessToken::query()->create([
            'internship_enrollment_id' => $enrollment->id,
            'email' => $email,
            'token_hash' => hash('sha256', $rawToken),
            'expires_at' => now()->addDays(30),
            'created_by' => $request->user()?->id,
        ]);

        $url = route('field-supervisor.token', $rawToken);

        $emails->queue(
            type: 'field_supervisor.access_token',
            recipientEmail: $email,
            subject: '[SiLAT] Akses Pembimbing Lapangan',
            bodyLines: [
                'Anda mendapatkan akses sebagai Pembimbing Lapangan pada SiLAT.',
                'Mahasiswa: '.($enrollment->student?->full_name ?: '-').' ('.($enrollment->student?->npm ?: '-').').',
                'Program/periode: '.($enrollment->internshipPeriod?->display_name ?: '-').'.',
                'Mitra: '.($enrollment->internshipPlace?->name ?: '-').'.',
                'Tautan ini berlaku sampai '.$accessToken->expires_at->format('d/m/Y H:i').' dan hanya membuka data mahasiswa terkait.',
            ],
            recipientName: $enrollment->field_supervisor ?: $email,
            actionText: 'Buka Portal Pembimbing',
            actionUrl: $url,
            notifiable: $enrollment,
            eventKey: 'field-supervisor-access-'.$accessToken->id,
        );

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
