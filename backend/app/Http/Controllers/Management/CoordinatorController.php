<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\InternshipCoordinator;
use App\Models\InternshipPeriod;
use App\Models\Lecturer;
use App\Models\StudyProgram;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CoordinatorController extends Controller
{
    public function index(): View
    {
        return view('management.coordinators.index', $this->formData() + [
            'coordinators' => InternshipCoordinator::query()
                ->with(['lecturer', 'internshipPeriod', 'studyProgram'])
                ->latest('id')
                ->paginate(20),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        InternshipCoordinator::query()->create($this->validated($request));

        return back()->with('status', 'Koordinator PKL berhasil ditambahkan.');
    }

    public function edit(InternshipCoordinator $coordinator): View
    {
        return view('management.coordinators.edit', $this->formData() + compact('coordinator'));
    }

    public function update(Request $request, InternshipCoordinator $coordinator): RedirectResponse
    {
        $coordinator->update($this->validated($request, $coordinator));

        return redirect()->route('management.coordinators.index')->with('status', 'Koordinator PKL berhasil diperbarui.');
    }

    private function validated(Request $request, ?InternshipCoordinator $coordinator = null): array
    {
        $data = $request->validate([
            'lecturer_id' => ['required', Rule::exists('lecturers', 'id')->where('status', 'active')],
            'internship_period_id' => ['required', 'exists:internship_periods,id'],
            'study_program_id' => ['required', 'exists:study_programs,id'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $request->validate([
            'study_program_id' => [
                Rule::unique('internship_coordinators')
                    ->where('internship_period_id', $data['internship_period_id'])
                    ->ignore($coordinator?->id),
            ],
        ]);

        return $data;
    }

    private function formData(): array
    {
        return [
            'lecturers' => Lecturer::query()->where('status', 'active')->orderBy('name')->get(),
            'periods' => InternshipPeriod::query()->orderByDesc('is_active')->orderByDesc('id')->get(),
            'studyPrograms' => StudyProgram::query()->where('is_active', true)->orderBy('name')->get(),
        ];
    }
}
