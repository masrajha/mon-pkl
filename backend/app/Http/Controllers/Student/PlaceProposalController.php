<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\InternshipPeriod;
use App\Models\InternshipPlaceProposal;
use App\Models\StudyProgram;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlaceProposalController extends Controller
{
    public function create(Request $request): View
    {
        abort_if(! $request->user()->student()->exists(), 403, 'Lengkapi profil mahasiswa terlebih dahulu.');

        return view('student.proposals.create', [
            'periods' => InternshipPeriod::query()->where('is_locked', false)->orderByDesc('is_active')->orderByDesc('id')->get(),
            'studyPrograms' => StudyProgram::query()->where('is_active', true)->orderBy('name')->get(),
            'cities' => City::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $student = $request->user()->student()->first();
        abort_if(! $student, 403, 'Lengkapi profil mahasiswa terlebih dahulu.');

        $data = $request->validate([
            'internship_period_id' => ['required', 'exists:internship_periods,id'],
            'study_program_id' => ['required', 'exists:study_programs,id'],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:2000'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'city_name' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'field_supervisor_name' => ['nullable', 'string', 'max:255'],
            'field_supervisor_phone' => ['nullable', 'string', 'max:50'],
        ]);

        InternshipPlaceProposal::query()->create($data + [
            'student_id' => $student->id,
            'proposed_by' => $request->user()->id,
            'status' => 'pending',
        ]);

        return redirect()->route('student.dashboard')->with('status', 'Usulan tempat PKL dikirim dan menunggu validasi admin.');
    }
}
