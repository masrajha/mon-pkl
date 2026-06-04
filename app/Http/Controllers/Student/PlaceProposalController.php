<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\InternshipPeriod;
use App\Models\InternshipPlaceProposal;
use App\Services\PeriodConfigurationService;
use App\Services\PlaceProposalEmailNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PlaceProposalController extends Controller
{
    public function __construct(
        private readonly PeriodConfigurationService $configurations,
        private readonly PlaceProposalEmailNotificationService $proposalEmails,
    ) {
    }

    public function index(Request $request): View
    {
        return view('student.proposals.index', [
            'proposals' => $this->studentProposals($request),
        ]);
    }

    public function create(Request $request): View
    {
        $student = $this->studentWithStudyProgram($request);

        return view('student.proposals.create', [
            'student' => $student,
        ] + $this->formOptions());
    }

    public function store(Request $request): RedirectResponse
    {
        $student = $this->studentWithStudyProgram($request);
        $data = $this->validatedProposalData($request);

        $proposal = InternshipPlaceProposal::query()->create($data + [
            'student_id' => $student->id,
            'study_program_id' => $student->study_program_id,
            'proposed_by' => $request->user()->id,
            'status' => 'pending',
        ]);
        $this->proposalEmails->submitted($proposal);

        return redirect()->route('student.dashboard')->with('status', 'Usulan mitra dikirim dan menunggu validasi admin/koordinator.');
    }

    public function edit(Request $request, InternshipPlaceProposal $proposal): View
    {
        $this->authorizeStudentProposal($request, $proposal);

        if ($proposal->status !== 'pending') {
            throw ValidationException::withMessages(['status' => 'Usulan hanya dapat diedit saat masih Menunggu.']);
        }

        return view('student.proposals.create', [
            'student' => $this->studentWithStudyProgram($request),
            'proposal' => $proposal,
        ] + $this->formOptions($proposal));
    }

    public function update(Request $request, InternshipPlaceProposal $proposal): RedirectResponse
    {
        $this->authorizeStudentProposal($request, $proposal);

        if ($proposal->status !== 'pending') {
            throw ValidationException::withMessages(['status' => 'Usulan hanya dapat diperbarui saat masih Menunggu.']);
        }

        $student = $this->studentWithStudyProgram($request);

        $proposal->update($this->validatedProposalData($request) + [
            'study_program_id' => $student->study_program_id,
            'proposed_by' => $request->user()->id,
        ]);

        return redirect()->route('student.proposals.index')->with('status', 'Usulan mitra diperbarui.');
    }

    public function cancel(Request $request, InternshipPlaceProposal $proposal): RedirectResponse
    {
        $this->authorizeStudentProposal($request, $proposal);

        if ($proposal->status !== 'pending') {
            throw ValidationException::withMessages(['status' => 'Usulan hanya dapat dibatalkan saat masih Menunggu.']);
        }

        $proposal->update([
            'status' => 'cancelled',
            'admin_note' => 'Dibatalkan oleh mahasiswa.',
        ]);

        return redirect()->route('student.proposals.index')->with('status', 'Usulan mitra dibatalkan.');
    }

    private function studentProposals(Request $request)
    {
        return InternshipPlaceProposal::query()
            ->with(['internshipPeriod.program', 'studyProgram', 'city', 'approvedPlace', 'reviewer'])
            ->whereHas('student', fn ($query) => $query->where('user_id', $request->user()?->id))
            ->latest('id')
            ->get();
    }

    private function studentWithStudyProgram(Request $request)
    {
        $student = $request->user()->student()->with('studyProgram')->first();
        abort_if(! $student || ! $student->study_program_id, 403, 'Lengkapi profil mahasiswa terlebih dahulu.');

        return $student;
    }

    private function formOptions(?InternshipPlaceProposal $proposal = null): array
    {
        $periods = InternshipPeriod::query()
            ->with('program')
            ->where('is_locked', false)
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->get();

        if ($proposal && ! $periods->contains('id', $proposal->internship_period_id)) {
            $proposalPeriod = InternshipPeriod::query()->with('program')->find($proposal->internship_period_id);

            if ($proposalPeriod) {
                $periods->push($proposalPeriod);
            }
        }

        return [
            'periods' => $periods,
            'cities' => City::query()->orderBy('name')->get(),
            'mapConfig' => $this->configurations->frontendMapConfig(null),
            'internalLocationSearchUrl' => route('locations.search'),
            'externalLocationSearchUrl' => $this->externalLocationSearchUrl(),
            'initialLatitude' => old('latitude', $proposal?->latitude),
            'initialLongitude' => old('longitude', $proposal?->longitude),
        ];
    }

    private function validatedProposalData(Request $request): array
    {
        return $request->validate([
            'internship_period_id' => ['required', 'exists:internship_periods,id'],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:2000'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'city_name' => ['nullable', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'field_supervisor_name' => ['nullable', 'string', 'max:255'],
            'field_supervisor_phone' => ['nullable', 'string', 'max:50'],
        ]);
    }

    private function authorizeStudentProposal(Request $request, InternshipPlaceProposal $proposal): void
    {
        abort_unless(
            $proposal->student()
                ->where('user_id', $request->user()?->id)
                ->exists(),
            403,
        );
    }

    private function externalLocationSearchUrl(): string
    {
        return 'https://nominatim.openstreetmap.org/search?format=jsonv2&limit=5&addressdetails=1&countrycodes=id&q={query}';
    }
}
