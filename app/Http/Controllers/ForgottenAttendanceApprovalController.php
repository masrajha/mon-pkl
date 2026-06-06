<?php

namespace App\Http\Controllers;

use App\Models\CheckIn;
use App\Models\ForgottenAttendanceRequest;
use App\Models\InternshipEnrollment;
use App\Services\PeriodConfigurationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ForgottenAttendanceApprovalController extends Controller
{
    public function __construct(private readonly PeriodConfigurationService $configurations)
    {
    }

    public function approveAsFieldSupervisor(Request $request, ForgottenAttendanceRequest $forgottenAttendanceRequest): RedirectResponse
    {
        $email = Str::lower(trim((string) $request->user()?->email));
        $forgottenAttendanceRequest->loadMissing('enrollment');

        abort_unless(
            InternshipEnrollment::query()
                ->whereKey($forgottenAttendanceRequest->internship_enrollment_id)
                ->whereRaw('LOWER(field_supervisor_email) = ?', [$email])
                ->whereNotIn('status', ['cancelled', 'rejected'])
                ->exists(),
            403
        );

        $this->approve($request, $forgottenAttendanceRequest, 'pembimbing_lapangan');

        return back()->with('status', 'Pengajuan lupa presensi berhasil disetujui.');
    }

    public function rejectAsFieldSupervisor(Request $request, ForgottenAttendanceRequest $forgottenAttendanceRequest): RedirectResponse
    {
        $email = Str::lower(trim((string) $request->user()?->email));

        abort_unless(
            InternshipEnrollment::query()
                ->whereKey($forgottenAttendanceRequest->internship_enrollment_id)
                ->whereRaw('LOWER(field_supervisor_email) = ?', [$email])
                ->whereNotIn('status', ['cancelled', 'rejected'])
                ->exists(),
            403
        );

        $this->reject($request, $forgottenAttendanceRequest, 'pembimbing_lapangan');

        return back()->with('status', 'Pengajuan lupa presensi berhasil ditolak.');
    }

    public function approveAsManagement(Request $request, ForgottenAttendanceRequest $forgottenAttendanceRequest): RedirectResponse
    {
        $this->authorizeManagement($request, $forgottenAttendanceRequest);
        $this->approve($request, $forgottenAttendanceRequest, $request->user()?->role ?: 'management');

        return back()->with('status', 'Pengajuan lupa presensi berhasil disetujui.');
    }

    public function rejectAsManagement(Request $request, ForgottenAttendanceRequest $forgottenAttendanceRequest): RedirectResponse
    {
        $this->authorizeManagement($request, $forgottenAttendanceRequest);
        $this->reject($request, $forgottenAttendanceRequest, $request->user()?->role ?: 'management');

        return back()->with('status', 'Pengajuan lupa presensi berhasil ditolak.');
    }

    private function approve(Request $request, ForgottenAttendanceRequest $forgottenAttendanceRequest, string $role): void
    {
        $data = $request->validate([
            'review_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $forgottenAttendanceRequest->loadMissing(['enrollment.internshipPeriod.setting', 'enrollment.internshipPlace']);

        DB::transaction(function () use ($request, $forgottenAttendanceRequest, $data, $role): void {
            $freshRequest = ForgottenAttendanceRequest::query()
                ->lockForUpdate()
                ->with(['enrollment.internshipPeriod.setting', 'enrollment.internshipPlace'])
                ->findOrFail($forgottenAttendanceRequest->id);

            if ($freshRequest->status !== 'pending') {
                throw ValidationException::withMessages(['forgotten_attendance' => 'Pengajuan ini sudah diproses.']);
            }

            $checkIn = $this->createCorrectionCheckIn($freshRequest);

            $freshRequest->forceFill([
                'status' => 'approved',
                'reviewed_by' => $request->user()?->id,
                'reviewed_by_name' => $request->user()?->name,
                'reviewed_by_email' => $request->user()?->email,
                'reviewed_by_role' => $role,
                'reviewed_at' => now(),
                'review_note' => $data['review_note'] ?? null,
                'created_check_in_id' => $checkIn->id,
            ])->save();
        });
    }

    private function reject(Request $request, ForgottenAttendanceRequest $forgottenAttendanceRequest, string $role): void
    {
        $data = $request->validate([
            'review_note' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($forgottenAttendanceRequest->status !== 'pending') {
            throw ValidationException::withMessages(['forgotten_attendance' => 'Pengajuan ini sudah diproses.']);
        }

        $forgottenAttendanceRequest->forceFill([
            'status' => 'rejected',
            'reviewed_by' => $request->user()?->id,
            'reviewed_by_name' => $request->user()?->name,
            'reviewed_by_email' => $request->user()?->email,
            'reviewed_by_role' => $role,
            'reviewed_at' => now(),
            'review_note' => $data['review_note'] ?? null,
        ])->save();
    }

    private function createCorrectionCheckIn(ForgottenAttendanceRequest $request): CheckIn
    {
        $enrollment = $request->enrollment;
        $settings = $this->configurations->forPeriod($enrollment->internshipPeriod);
        $requestedAt = Carbon::parse($request->requested_checked_at, $settings['timezone']);
        $dayStart = $requestedAt->copy()->startOfDay();
        $dayEnd = $requestedAt->copy()->endOfDay();
        $dailyCheckIns = $enrollment->checkIns()
            ->whereBetween('checked_at', [$dayStart, $dayEnd])
            ->orderBy('checked_at')
            ->lockForUpdate()
            ->get();

        if ($dailyCheckIns->contains('action', $request->action)) {
            throw ValidationException::withMessages(['forgotten_attendance' => 'Presensi pada tanggal dan jenis yang sama sudah tercatat.']);
        }

        $counterpart = $dailyCheckIns->firstWhere('action', $request->action === 'check_in' ? 'check_out' : 'check_in');

        if ($request->action === 'check_out' && ! $counterpart) {
            throw ValidationException::withMessages(['forgotten_attendance' => 'Presensi pulang membutuhkan presensi masuk pada tanggal yang sama.']);
        }

        if ($counterpart && $request->action === 'check_in' && ! $requestedAt->lt($counterpart->checked_at)) {
            throw ValidationException::withMessages(['forgotten_attendance' => 'Jam masuk harus lebih awal dari jam pulang.']);
        }

        if ($counterpart && $request->action === 'check_out' && ! $requestedAt->gt($counterpart->checked_at)) {
            throw ValidationException::withMessages(['forgotten_attendance' => 'Jam pulang harus lebih akhir dari jam masuk.']);
        }

        $durationMinutes = $request->action === 'check_out' && $counterpart
            ? max(0, (int) $counterpart->checked_at->diffInMinutes($requestedAt))
            : null;
        $sanctionPoints = $this->durationSanctionPoints($durationMinutes, $settings);

        $checkIn = CheckIn::query()->create([
            'internship_enrollment_id' => $enrollment->id,
            'type' => $request->action === 'check_in' ? 'Koreksi Masuk' : 'Koreksi Pulang',
            'action' => $request->action,
            'source_type' => 'forgotten_request',
            'forgotten_attendance_request_id' => $request->id,
            'note' => $request->note,
            'checked_at' => $requestedAt,
            'pair_id' => $request->action === 'check_out' ? $counterpart?->id : null,
            'student_latitude' => $request->student_latitude,
            'student_longitude' => $request->student_longitude,
            'office_latitude' => $request->office_latitude,
            'office_longitude' => $request->office_longitude,
            'distance_meters' => $request->distance_meters,
            'duration_minutes' => $durationMinutes,
            'sanction_points' => $sanctionPoints,
            'device_info' => $request->device_info,
            'source_url' => 'forgotten-attendance-request:'.$request->id,
            'photo_path' => $request->photo_path,
        ]);

        if ($request->action === 'check_in' && $counterpart) {
            $newDuration = max(0, (int) $requestedAt->diffInMinutes($counterpart->checked_at));
            $newSanction = $this->durationSanctionPoints($newDuration, $settings);
            $oldSanction = (int) ($counterpart->sanction_points ?? 0);

            $counterpart->forceFill([
                'pair_id' => $checkIn->id,
                'duration_minutes' => $newDuration,
                'sanction_points' => $newSanction,
            ])->save();

            $delta = $newSanction - $oldSanction;
            if ($delta !== 0) {
                $enrollment->increment('total_sanctions_points', $delta);
            }
        } elseif ($sanctionPoints > 0) {
            $enrollment->increment('total_sanctions_points', $sanctionPoints);
        }

        return $checkIn;
    }

    private function durationSanctionPoints(?int $durationMinutes, array $settings): int
    {
        if ($durationMinutes === null) {
            return 0;
        }

        $minimum = (int) ($settings['check_in']['min_daily_duration_minutes'] ?? 360);

        if ($durationMinutes >= $minimum) {
            return 0;
        }

        $penaltyPerHour = (int) ($settings['check_in']['insufficient_duration_penalty_per_hour'] ?? 1);

        return (int) ceil(($minimum - $durationMinutes) / 60) * $penaltyPerHour;
    }

    private function authorizeManagement(Request $request, ForgottenAttendanceRequest $forgottenAttendanceRequest): void
    {
        if ($request->user()?->hasRole('admin')) {
            return;
        }

        $forgottenAttendanceRequest->loadMissing('enrollment');
        $allowed = $request->user()?->lecturer?->coordinatorAssignments()
            ->where('status', 'active')
            ->where('internship_period_id', $forgottenAttendanceRequest->enrollment?->internship_period_id)
            ->where('study_program_id', $forgottenAttendanceRequest->enrollment?->study_program_id)
            ->exists();

        abort_unless($allowed, 403);
    }
}
