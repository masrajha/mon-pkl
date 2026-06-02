<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\InternshipPeriod;
use App\Models\InternshipPlaceProposal;
use App\Services\PeriodConfigurationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlaceProposalController extends Controller
{
    public function __construct(private readonly PeriodConfigurationService $configurations)
    {
    }

    public function create(Request $request): View
    {
        $student = $request->user()->student()->with('studyProgram')->first();
        abort_if(! $student || ! $student->study_program_id, 403, 'Lengkapi profil mahasiswa terlebih dahulu.');

        return view('student.proposals.create', [
            'student' => $student,
            'periods' => InternshipPeriod::query()->with('program')->where('is_locked', false)->orderByDesc('is_active')->orderByDesc('id')->get(),
            'cities' => City::query()->orderBy('name')->get(),
            'mapConfig' => $this->configurations->frontendMapConfig(null),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $student = $request->user()->student()->first();
        abort_if(! $student || ! $student->study_program_id, 403, 'Lengkapi profil mahasiswa terlebih dahulu.');

        $data = $request->validate([
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

        InternshipPlaceProposal::query()->create($data + [
            'student_id' => $student->id,
            'study_program_id' => $student->study_program_id,
            'proposed_by' => $request->user()->id,
            'status' => 'pending',
        ]);

        return redirect()->route('student.dashboard')->with('status', 'Usulan mitra dikirim dan menunggu validasi admin.');
    }
}
