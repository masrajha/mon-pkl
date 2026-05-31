<?php

namespace App\Http\Controllers;

use App\Models\CheckIn;
use App\Models\InternshipEnrollment;
use App\Services\CheckInStatusService;
use App\Services\DistanceService;
use App\Services\PeriodConfigurationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        abort_if(! $enrollment, 403, 'Akun ini belum terhubung dengan enrollment PKL aktif.');
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

        abort_if(! $enrollment, 403, 'Akun ini belum terhubung dengan enrollment PKL aktif.');
        $settings = $this->configurations->forPeriod($enrollment->internshipPeriod);

        $validated = $request->validate([
            'student_latitude' => ['required', 'numeric', 'between:-90,90'],
            'student_longitude' => ['required', 'numeric', 'between:-180,180'],
            'note' => ['nullable', 'string', 'max:1000'],
            'photo' => ['nullable', 'image', 'max:'.$settings['check_in']['photo_max_kb']],
        ]);

        $place = $enrollment->internshipPlace;

        if (! $place || $place->latitude === null || $place->longitude === null) {
            throw ValidationException::withMessages([
                'student_latitude' => 'Lokasi instansi PKL belum lengkap.',
            ]);
        }

        $checkedAt = now($settings['timezone']);
        $type = $this->checkInStatus->statusFor($checkedAt, $settings);

        if ($type === 'Tidak Aktif') {
            throw ValidationException::withMessages([
                'student_latitude' => $settings['check_in']['inactive_message'],
            ]);
        }

        $photoPath = $request->file('photo')?->store(
            $settings['check_in']['photo_directory'],
            $settings['check_in']['photo_disk'],
        );

        CheckIn::query()->create([
            'internship_enrollment_id' => $enrollment->id,
            'type' => $type,
            'note' => $validated['note'] ?? null,
            'checked_at' => $checkedAt,
            'student_latitude' => $validated['student_latitude'],
            'student_longitude' => $validated['student_longitude'],
            'office_latitude' => $place->latitude,
            'office_longitude' => $place->longitude,
            'distance_meters' => $distanceService->meters(
                (float) $validated['student_latitude'],
                (float) $validated['student_longitude'],
                (float) $place->latitude,
                (float) $place->longitude,
                (int) $settings['distance']['earth_radius_meters'],
            ),
            'device_info' => [
                'user_agent' => $request->userAgent(),
                'ip' => $request->ip(),
            ],
            'source_url' => $request->fullUrl(),
            'photo_path' => $photoPath,
        ]);

        return redirect()
            ->route('check-ins.create')
            ->with('status', 'Check-in berhasil disimpan sebagai '.$type.'.');
    }

    private function currentEnrollment(Request $request): ?InternshipEnrollment
    {
        return InternshipEnrollment::query()
            ->with(['student.user', 'studyProgram', 'internshipPeriod', 'internshipPlace'])
            ->where('status', 'active')
            ->whereHas('student', fn ($query) => $query->where('user_id', $request->user()?->id))
            ->orderByDesc(
                \App\Models\InternshipPeriod::query()
                    ->select('is_active')
                    ->whereColumn('internship_periods.id', 'internship_enrollments.internship_period_id')
                    ->limit(1)
            )
            ->latest('id')
            ->first();
    }
}
