<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\InternshipEnrollment;
use App\Models\InternshipPlace;
use App\Models\RelocationRequest;
use App\Services\RelocationEmailNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RelocationRequestController extends Controller
{
    public function __construct(private readonly RelocationEmailNotificationService $relocationEmails)
    {
    }

    public function create(Request $request): View
    {
        return view('student.relocations.create', [
            'enrollments' => $this->activeEnrollments($request),
            'places' => InternshipPlace::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $enrollmentIds = $this->activeEnrollments($request)->pluck('id');

        $data = $request->validate([
            'internship_enrollment_id' => ['required', Rule::in($enrollmentIds)],
            'new_internship_place_id' => ['required', Rule::exists('internship_places', 'id')->where('is_active', true)],
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $enrollment = InternshipEnrollment::query()->findOrFail($data['internship_enrollment_id']);

        if ((int) $enrollment->internship_place_id === (int) $data['new_internship_place_id']) {
            throw ValidationException::withMessages(['new_internship_place_id' => 'Tempat tujuan harus berbeda dari tempat saat ini.']);
        }

        $relocation = RelocationRequest::query()->create($data + [
            'current_internship_place_id' => $enrollment->internship_place_id,
            'status' => 'pending',
        ]);
        $this->relocationEmails->submitted($relocation);

        return redirect()->route('student.dashboard')->with('status', 'Permohonan pindah mitra berhasil dikirim.');
    }

    private function activeEnrollments(Request $request)
    {
        return InternshipEnrollment::query()
            ->with(['internshipPeriod.program', 'studyProgram', 'internshipPlace'])
            ->where('status', 'active')
            ->whereHas('student', fn ($query) => $query->where('user_id', $request->user()?->id))
            ->latest('id')
            ->get();
    }
}
