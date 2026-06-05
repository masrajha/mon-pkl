<?php

namespace App\Http\Controllers;

use App\Models\CheckIn;
use App\Models\InternshipEnrollment;
use App\Services\CheckInStatusService;
use App\Services\DistanceService;
use App\Services\PeriodConfigurationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CheckInController extends Controller
{
    public function __construct(
        private readonly CheckInStatusService $checkInStatus,
        private readonly PeriodConfigurationService $configurations,
    )
    {
    }

    public function create(Request $request): View
    {
        $enrollment = $this->currentEnrollment($request);

        abort_if(! $enrollment, 403, 'Akun ini belum terhubung dengan enrollment program aktif.');
        $settings = $this->configurations->forPeriod($enrollment->internshipPeriod);

        return view('check-ins.create', [
            'enrollment' => $enrollment,
            'statusPreview' => $this->checkInStatus->statusFor(now($settings['timezone']), $settings),
            'mapConfig' => $this->configurations->frontendMapConfig($enrollment->internshipPeriod),
            'recentCheckIns' => $enrollment->checkIns()
                ->latest('checked_at')
                ->limit((int) $settings['check_in']['recent_limit'])
                ->get(),
        ]);
    }

    public function store(Request $request, DistanceService $distanceService): RedirectResponse
    {
        $enrollment = $this->currentEnrollment($request);

        abort_if(! $enrollment, 403, 'Akun ini belum terhubung dengan enrollment program aktif.');
        $settings = $this->configurations->forPeriod($enrollment->internshipPeriod);

        $validated = $request->validate([
            'student_latitude' => ['required', 'numeric', 'between:-90,90'],
            'student_longitude' => ['required', 'numeric', 'between:-180,180'],
            'action' => ['required', 'in:check_in,check_out'],
            'note' => ['required', 'string', 'max:1000'],
            'photo_capture' => ['required', 'string'],
        ]);

        if ($this->wordCount($validated['note']) < 5) {
            throw ValidationException::withMessages([
                'note' => 'Catatan aktivitas wajib berisi minimal 5 kata.',
            ]);
        }

        $place = $enrollment->internshipPlace;

        if (! $place || $place->latitude === null || $place->longitude === null) {
            throw ValidationException::withMessages([
                'student_latitude' => 'Lokasi mitra belum lengkap.',
            ]);
        }

        $checkedAt = now($settings['timezone']);
        $period = $enrollment->internshipPeriod;

        if ($period?->starts_at && $checkedAt->lt($period->starts_at->copy()->startOfDay())) {
            throw ValidationException::withMessages([
                'student_latitude' => 'Periode program belum dimulai.',
            ]);
        }

        if ($period?->ends_at && $checkedAt->gt($period->ends_at->copy()->endOfDay())) {
            throw ValidationException::withMessages([
                'student_latitude' => 'Periode program sudah selesai.',
            ]);
        }

        $type = $this->checkInStatus->statusFor($checkedAt, $settings);

        if ($type === 'Tidak Aktif') {
            throw ValidationException::withMessages([
                'student_latitude' => $settings['check_in']['inactive_message'],
            ]);
        }

        $this->validateActionForStatus($validated['action'], $type);

        $distanceMeters = $distanceService->meters(
            (float) $validated['student_latitude'],
            (float) $validated['student_longitude'],
            (float) $place->latitude,
            (float) $place->longitude,
            (int) $settings['distance']['earth_radius_meters'],
        );

        $maxDistance = (int) ($settings['check_in']['max_distance_meters'] ?? 0);

        if ($maxDistance > 0 && $distanceMeters > $maxDistance) {
            throw ValidationException::withMessages([
                'student_latitude' => 'Lokasi Anda berada di luar radius presensi yang diizinkan. Jarak terhitung '.number_format($distanceMeters, 0, ',', '.').' meter.',
            ]);
        }

        $dayStart = $checkedAt->copy()->startOfDay();
        $dayEnd = $checkedAt->copy()->endOfDay();
        $dailyCheckIns = $enrollment->checkIns()
            ->whereBetween('checked_at', [$dayStart, $dayEnd])
            ->orderBy('checked_at')
            ->get();

        $pairedCheckIn = null;

        if ($validated['action'] === 'check_in') {
            if ($dailyCheckIns->contains('action', 'check_in')) {
                throw ValidationException::withMessages([
                    'action' => 'Check-in masuk hari ini sudah tercatat.',
                ]);
            }

            if ($dailyCheckIns->contains('action', 'check_out')) {
                throw ValidationException::withMessages([
                    'action' => 'Pasangan presensi hari ini sudah lengkap.',
                ]);
            }
        } else {
            $pairedCheckIn = $dailyCheckIns->firstWhere('action', 'check_in');

            if (! $pairedCheckIn) {
                throw ValidationException::withMessages([
                    'action' => 'Check-in masuk harus dilakukan sebelum check-out pulang.',
                ]);
            }

            if ($dailyCheckIns->contains('action', 'check_out')) {
                throw ValidationException::withMessages([
                    'action' => 'Check-out pulang hari ini sudah tercatat.',
                ]);
            }
        }

        $durationMinutes = $pairedCheckIn
            ? max(0, (int) $pairedCheckIn->checked_at->diffInMinutes($checkedAt))
            : null;
        $sanctionPoints = $this->durationSanctionPoints($durationMinutes, $settings);

        $photoPath = $this->storeCapturedPhoto(
            $validated['photo_capture'],
            $settings['check_in']['photo_directory'],
            $settings['check_in']['photo_disk'],
            (int) $settings['check_in']['photo_max_kb'],
        );

        DB::transaction(function () use ($enrollment, $type, $validated, $checkedAt, $place, $distanceMeters, $request, $photoPath, $pairedCheckIn, $durationMinutes, $sanctionPoints): void {
            CheckIn::query()->create([
                'internship_enrollment_id' => $enrollment->id,
                'type' => $type,
                'action' => $validated['action'],
                'note' => $validated['note'] ?? null,
                'checked_at' => $checkedAt,
                'pair_id' => $pairedCheckIn?->id,
                'student_latitude' => $validated['student_latitude'],
                'student_longitude' => $validated['student_longitude'],
                'office_latitude' => $place->latitude,
                'office_longitude' => $place->longitude,
                'distance_meters' => $distanceMeters,
                'duration_minutes' => $durationMinutes,
                'sanction_points' => $sanctionPoints,
                'device_info' => [
                    'user_agent' => $request->userAgent(),
                    'ip' => $request->ip(),
                ],
                'source_url' => $request->fullUrl(),
                'photo_path' => $photoPath,
            ]);

            if ($sanctionPoints > 0) {
                $enrollment->increment('total_sanctions_points', $sanctionPoints);
            }
        });

        $message = $validated['action'] === 'check_in'
            ? 'Check-in masuk berhasil disimpan sebagai '.$type.'.'
            : 'Check-out pulang berhasil disimpan sebagai '.$type.'.';

        if ($sanctionPoints > 0) {
            $message .= ' Durasi harian kurang dari batas minimal, sanksi '.$sanctionPoints.' poin dicatat.';
        }

        return redirect()
            ->route('check-ins.create')
            ->with('status', $message);
    }

    private function validateActionForStatus(string $action, string $status): void
    {
        $allowed = [
            'check_in' => ['Masuk', 'Datang Terlambat'],
            'check_out' => ['Pulang Cepat', 'Pulang'],
        ];

        if (! in_array($status, $allowed[$action], true)) {
            throw ValidationException::withMessages([
                'action' => $action === 'check_in'
                    ? 'Check-in masuk hanya dapat dilakukan pada rentang Masuk atau Datang Terlambat.'
                    : 'Check-out pulang hanya dapat dilakukan pada rentang Pulang Cepat atau Pulang.',
            ]);
        }
    }

    private function wordCount(string $value): int
    {
        return count(preg_split('/\s+/', trim($value), -1, PREG_SPLIT_NO_EMPTY));
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

    private function storeCapturedPhoto(string $dataUrl, string $directory, string $disk, int $maxKb): string
    {
        if (! preg_match('/^data:image\/(png|jpeg|jpg);base64,([A-Za-z0-9+\/=]+)$/', $dataUrl, $matches)) {
            throw ValidationException::withMessages([
                'photo_capture' => 'Foto wajib diambil langsung dari kamera.',
            ]);
        }

        $binary = base64_decode($matches[2], true);

        if ($binary === false) {
            throw ValidationException::withMessages([
                'photo_capture' => 'Foto kamera tidak valid.',
            ]);
        }

        if (strlen($binary) > ($maxKb * 1024)) {
            throw ValidationException::withMessages([
                'photo_capture' => "Ukuran foto maksimal {$maxKb} KB.",
            ]);
        }

        $extension = $matches[1] === 'png' ? 'png' : 'jpg';
        $path = trim($directory, '/').'/'.now()->format('Ymd_His').'_'.Str::uuid().'.'.$extension;

        Storage::disk($disk)->put($path, $binary);

        return $path;
    }

    private function currentEnrollment(Request $request): ?InternshipEnrollment
    {
        return InternshipEnrollment::query()
            ->with(['student.user', 'studyProgram', 'internshipPeriod.program', 'internshipPlace'])
            ->where('status', 'active')
            ->whereHas('internshipPeriod', fn ($query) => $query->where('is_active', true))
            ->whereHas('student', fn ($query) => $query->where('user_id', $request->user()?->id))
            ->latest('id')
            ->first();
    }
}
