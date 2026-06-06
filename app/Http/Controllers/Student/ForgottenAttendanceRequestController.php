<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ForgottenAttendanceRequest;
use App\Models\InternshipEnrollment;
use App\Services\DistanceService;
use App\Services\PeriodConfigurationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ForgottenAttendanceRequestController extends Controller
{
    public function __construct(private readonly PeriodConfigurationService $configurations)
    {
    }

    public function store(Request $request, InternshipEnrollment $enrollment, DistanceService $distanceService): RedirectResponse
    {
        $this->authorizeEnrollment($request, $enrollment);
        $enrollment->loadMissing(['internshipPeriod.setting', 'internshipPlace', 'forgottenAttendanceRequests']);
        $settings = $this->configurations->forPeriod($enrollment->internshipPeriod);
        $maxRequests = (int) data_get($settings, 'report.max_forgotten_attendance_requests', 3);

        if ($maxRequests <= 0) {
            throw ValidationException::withMessages([
                'forgotten_attendance' => 'Fitur lupa presensi tidak aktif untuk periode ini.',
            ]);
        }

        $usedRequests = $enrollment->forgottenAttendanceRequests()
            ->whereIn('status', ['pending', 'approved'])
            ->count();

        if ($usedRequests >= $maxRequests) {
            throw ValidationException::withMessages([
                'forgotten_attendance' => 'Batas maksimal pengajuan lupa presensi periode ini sudah tercapai.',
            ]);
        }

        $data = $request->validate([
            'requested_date' => ['required', 'date'],
            'requested_time' => ['required', 'date_format:H:i'],
            'action' => ['required', 'in:check_in,check_out'],
            'note' => ['required', 'string', 'max:1000'],
            'reason' => ['required', 'string', 'max:1000'],
            'student_latitude' => ['required', 'numeric', 'between:-90,90'],
            'student_longitude' => ['required', 'numeric', 'between:-180,180'],
            'photo_capture' => ['required', 'string'],
        ]);

        if ($this->wordCount($data['note']) < 5) {
            throw ValidationException::withMessages(['note' => 'Catatan aktivitas wajib berisi minimal 5 kata.']);
        }

        if ($this->wordCount($data['reason']) < 3) {
            throw ValidationException::withMessages(['reason' => 'Alasan lupa presensi wajib berisi minimal 3 kata.']);
        }

        $requestedAt = Carbon::parse($data['requested_date'].' '.$data['requested_time'], $settings['timezone']);
        $this->validateRequestedAttendance($enrollment, $requestedAt, $data['action'], $settings);

        $place = $enrollment->internshipPlace;

        if (! $place || $place->latitude === null || $place->longitude === null) {
            throw ValidationException::withMessages(['student_latitude' => 'Lokasi mitra belum lengkap.']);
        }

        $distanceMeters = $distanceService->meters(
            (float) $data['student_latitude'],
            (float) $data['student_longitude'],
            (float) $place->latitude,
            (float) $place->longitude,
            (int) $settings['distance']['earth_radius_meters'],
        );
        $maxDistance = (int) ($settings['check_in']['max_distance_meters'] ?? 0);

        if ($maxDistance > 0 && $distanceMeters > $maxDistance) {
            throw ValidationException::withMessages([
                'student_latitude' => 'Lokasi pengajuan berada di luar radius presensi. Jarak terhitung '.number_format($distanceMeters, 0, ',', '.').' meter.',
            ]);
        }

        $photoPath = $this->storeCapturedPhoto(
            $data['photo_capture'],
            $settings['check_in']['photo_directory'],
            $settings['check_in']['photo_disk'],
            (int) $settings['check_in']['photo_max_kb'],
        );

        ForgottenAttendanceRequest::query()->create([
            'internship_enrollment_id' => $enrollment->id,
            'action' => $data['action'],
            'requested_date' => $requestedAt->toDateString(),
            'requested_time' => $requestedAt->format('H:i:s'),
            'requested_checked_at' => $requestedAt,
            'note' => $data['note'],
            'reason' => $data['reason'],
            'student_latitude' => $data['student_latitude'],
            'student_longitude' => $data['student_longitude'],
            'office_latitude' => $place->latitude,
            'office_longitude' => $place->longitude,
            'distance_meters' => $distanceMeters,
            'photo_path' => $photoPath,
            'device_info' => [
                'user_agent' => $request->userAgent(),
                'ip' => $request->ip(),
                'submitted_at' => now($settings['timezone'])->toDateTimeString(),
            ],
            'status' => 'pending',
        ]);

        return back()->with('status', 'Pengajuan lupa presensi berhasil dikirim dan menunggu persetujuan.');
    }

    private function authorizeEnrollment(Request $request, InternshipEnrollment $enrollment): void
    {
        abort_unless(
            (int) $enrollment->student?->user_id === (int) $request->user()?->id,
            403
        );
    }

    private function validateRequestedAttendance(InternshipEnrollment $enrollment, Carbon $requestedAt, string $action, array $settings): void
    {
        $timezone = (string) ($settings['timezone'] ?? config('monpkl.timezone', 'Asia/Jakarta'));
        $today = Carbon::today($timezone);

        if ($requestedAt->copy()->startOfDay()->gt($today)) {
            throw ValidationException::withMessages(['requested_date' => 'Tanggal lupa presensi tidak boleh tanggal masa depan.']);
        }

        $startsAt = $enrollment->effectiveAttendanceStartsAt();
        $endsAt = $enrollment->effectiveAttendanceEndsAt();

        if ($startsAt && $requestedAt->lt(Carbon::parse($startsAt, $timezone)->startOfDay())) {
            throw ValidationException::withMessages(['requested_date' => 'Tanggal berada di luar rentang presensi.']);
        }

        if ($endsAt && $requestedAt->gt(Carbon::parse($endsAt, $timezone)->endOfDay())) {
            throw ValidationException::withMessages(['requested_date' => 'Tanggal berada di luar rentang presensi.']);
        }

        $holidays = collect($settings['calendar']['holidays'] ?? [])->filter()->flip()->all();

        if ($requestedAt->isWeekend() || isset($holidays[$requestedAt->toDateString()])) {
            throw ValidationException::withMessages(['requested_date' => 'Pengajuan lupa presensi hanya dapat diajukan untuk hari kerja.']);
        }

        $dayStart = $requestedAt->copy()->startOfDay();
        $dayEnd = $requestedAt->copy()->endOfDay();
        $dailyCheckIns = $enrollment->checkIns()
            ->whereBetween('checked_at', [$dayStart, $dayEnd])
            ->orderBy('checked_at')
            ->get();

        if ($dailyCheckIns->contains('action', $action)) {
            throw ValidationException::withMessages(['action' => 'Presensi '.($action === 'check_in' ? 'masuk' : 'pulang').' pada tanggal tersebut sudah tercatat.']);
        }

        $duplicateRequestExists = $enrollment->forgottenAttendanceRequests()
            ->where('requested_date', $requestedAt->toDateString())
            ->where('action', $action)
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        if ($duplicateRequestExists) {
            throw ValidationException::withMessages(['action' => 'Pengajuan untuk jenis presensi dan tanggal tersebut sudah ada.']);
        }

        $counterpart = $dailyCheckIns->firstWhere('action', $action === 'check_in' ? 'check_out' : 'check_in');

        if ($action === 'check_out' && ! $counterpart) {
            throw ValidationException::withMessages(['action' => 'Pengajuan pulang membutuhkan presensi masuk pada tanggal yang sama.']);
        }

        if ($counterpart && $action === 'check_in' && ! $requestedAt->lt($counterpart->checked_at)) {
            throw ValidationException::withMessages(['requested_time' => 'Jam masuk harus lebih awal dari jam pulang.']);
        }

        if ($counterpart && $action === 'check_out' && ! $requestedAt->gt($counterpart->checked_at)) {
            throw ValidationException::withMessages(['requested_time' => 'Jam pulang harus lebih akhir dari jam masuk.']);
        }
    }

    private function wordCount(string $value): int
    {
        return count(preg_split('/\s+/', trim($value), -1, PREG_SPLIT_NO_EMPTY));
    }

    private function storeCapturedPhoto(string $dataUrl, string $directory, string $disk, int $maxKb): string
    {
        if (! preg_match('/^data:image\/(png|jpeg|jpg);base64,([A-Za-z0-9+\/=]+)$/', $dataUrl, $matches)) {
            throw ValidationException::withMessages(['photo_capture' => 'Foto wajib diambil langsung dari kamera.']);
        }

        $binary = base64_decode($matches[2], true);

        if ($binary === false) {
            throw ValidationException::withMessages(['photo_capture' => 'Foto kamera tidak valid.']);
        }

        if (strlen($binary) > ($maxKb * 1024)) {
            throw ValidationException::withMessages(['photo_capture' => "Ukuran foto maksimal {$maxKb} KB."]);
        }

        $extension = $matches[1] === 'png' ? 'png' : 'jpg';
        $path = trim($directory, '/').'/forgotten_'.now()->format('Ymd_His').'_'.Str::uuid().'.'.$extension;

        Storage::disk($disk)->put($path, $binary);

        return $path;
    }
}
