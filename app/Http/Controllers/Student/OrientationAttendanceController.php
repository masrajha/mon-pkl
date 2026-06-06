<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\InternshipEnrollment;
use App\Models\OrientationAttendance;
use App\Models\OrientationEvent;
use App\Services\DistanceService;
use App\Services\OrientationEmailNotificationService;
use App\Services\PeriodConfigurationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrientationAttendanceController extends Controller
{
    public function __construct(private readonly PeriodConfigurationService $configurations)
    {
    }

    public function create(Request $request, OrientationEvent $orientationEvent): View
    {
        $enrollment = $this->eligibleEnrollment($request, $orientationEvent);
        $settings = $this->configurations->forPeriod($orientationEvent->internshipPeriod);

        return view('student.orientation-attendances.create', [
            'event' => $orientationEvent->loadMissing(['internshipPeriod.program', 'studyProgram']),
            'enrollment' => $enrollment,
            'attendance' => OrientationAttendance::query()
                ->where('orientation_event_id', $orientationEvent->id)
                ->where('student_id', $enrollment->student_id)
                ->first(),
            'mapConfig' => $this->configurations->frontendMapConfig($orientationEvent->internshipPeriod),
            'maxDistance' => $orientationEvent->max_distance_meters ?? (int) ($settings['check_in']['max_distance_meters'] ?? 0),
        ]);
    }

    public function store(
        Request $request,
        OrientationEvent $orientationEvent,
        DistanceService $distanceService,
        OrientationEmailNotificationService $orientationEmails,
    ): RedirectResponse {
        $enrollment = $this->eligibleEnrollment($request, $orientationEvent);
        $settings = $this->configurations->forPeriod($orientationEvent->internshipPeriod);

        if (OrientationAttendance::query()->where('orientation_event_id', $orientationEvent->id)->where('student_id', $enrollment->student_id)->exists()) {
            throw ValidationException::withMessages([
                'student_latitude' => 'Presensi pembekalan untuk kegiatan ini sudah tercatat.',
            ]);
        }

        $validated = $request->validate([
            'student_latitude' => ['required', 'numeric', 'between:-90,90'],
            'student_longitude' => ['required', 'numeric', 'between:-180,180'],
            'photo_capture' => ['required', 'string'],
        ]);

        $checkedAt = now($settings['timezone']);

        if ($orientationEvent->starts_at && $checkedAt->lt($orientationEvent->starts_at)) {
            throw ValidationException::withMessages([
                'student_latitude' => 'Presensi pembekalan belum dibuka.',
            ]);
        }

        if ($orientationEvent->ends_at && $checkedAt->gt($orientationEvent->ends_at)) {
            throw ValidationException::withMessages([
                'student_latitude' => 'Presensi pembekalan sudah ditutup.',
            ]);
        }

        $distanceMeters = $distanceService->meters(
            (float) $validated['student_latitude'],
            (float) $validated['student_longitude'],
            (float) $orientationEvent->latitude,
            (float) $orientationEvent->longitude,
            (int) $settings['distance']['earth_radius_meters'],
        );

        $maxDistance = $orientationEvent->max_distance_meters ?? (int) ($settings['check_in']['max_distance_meters'] ?? 0);

        if ($maxDistance > 0 && $distanceMeters > $maxDistance) {
            throw ValidationException::withMessages([
                'student_latitude' => 'Lokasi Anda berada di luar radius presensi pembekalan. Jarak terhitung '.number_format($distanceMeters, 0, ',', '.').' meter.',
            ]);
        }

        $photoPath = $this->storeCapturedPhoto(
            $validated['photo_capture'],
            'orientation-attendance-photos',
            $settings['check_in']['photo_disk'],
            (int) $settings['check_in']['photo_max_kb'],
        );

        $attendance = OrientationAttendance::query()->create([
            'orientation_event_id' => $orientationEvent->id,
            'internship_enrollment_id' => $enrollment->id,
            'student_id' => $enrollment->student_id,
            'checked_at' => $checkedAt,
            'student_latitude' => $validated['student_latitude'],
            'student_longitude' => $validated['student_longitude'],
            'event_latitude' => $orientationEvent->latitude,
            'event_longitude' => $orientationEvent->longitude,
            'distance_meters' => $distanceMeters,
            'device_info' => [
                'user_agent' => $request->userAgent(),
                'ip' => $request->ip(),
            ],
            'source_url' => $request->fullUrl(),
            'photo_path' => $photoPath,
        ]);

        $orientationEmails->attendanceRecorded($attendance);

        return redirect()
            ->route('student.dashboard')
            ->with('status', 'Presensi pembekalan berhasil disimpan.');
    }

    private function eligibleEnrollment(Request $request, OrientationEvent $event): InternshipEnrollment
    {
        $event->loadMissing('internshipPeriod');
        $student = $request->user()?->student;

        abort_unless($student, 403);

        $enrollment = InternshipEnrollment::query()
            ->with(['student.studyProgram', 'studyProgram', 'internshipPeriod.program'])
            ->where('student_id', $student->id)
            ->where('internship_period_id', $event->internship_period_id)
            ->where('status', 'active')
            ->when($event->study_program_id, fn ($query) => $query->where('study_program_id', $event->study_program_id))
            ->first();

        abort_unless($event->is_active && $enrollment, 403, 'Event pembekalan tidak tersedia untuk akun ini.');

        return $enrollment;
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
}
