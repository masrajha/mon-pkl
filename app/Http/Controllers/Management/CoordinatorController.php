<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\InteractsWithTableControls;
use App\Models\InternshipCoordinator;
use App\Models\InternshipPeriod;
use App\Models\Lecturer;
use App\Models\StudyProgram;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CoordinatorController extends Controller
{
    use InteractsWithTableControls;

    public function index(Request $request): View
    {
        $query = InternshipCoordinator::query()
            ->with(['lecturer', 'internshipPeriod.program', 'studyProgram']);

        if ($request->filled('q')) {
            $search = $request->string('q')->toString();
            $query->where(fn ($query) => $query
                ->whereHas('lecturer', fn ($query) => $query->where('name', 'like', '%'.$search.'%')->orWhere('nip', 'like', '%'.$search.'%'))
                ->orWhereHas('internshipPeriod', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
                ->orWhereHas('studyProgram', fn ($query) => $query->where('name', 'like', '%'.$search.'%')));
        }

        if ($request->filled('period_id')) {
            $query->where('internship_period_id', $request->integer('period_id'));
        }

        if ($request->filled('study_program_id')) {
            $query->where('study_program_id', $request->integer('study_program_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return view('management.coordinators.index', $this->formData() + [
            'coordinators' => $this->applyTableSort($query, $request, ['id', 'status'], 'id', 'desc')
                ->paginate($this->tablePerPage($request))
                ->withQueryString(),
            'selectedPeriod' => $request->integer('period_id') ?: null,
            'selectedStudyProgram' => $request->integer('study_program_id') ?: null,
            'selectedStatus' => $request->string('status')->toString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedForStore($request);

        DB::transaction(function () use ($data): void {
            foreach ($data['study_program_ids'] as $studyProgramId) {
                InternshipCoordinator::query()->create([
                    'lecturer_id' => $data['lecturer_id'],
                    'internship_period_id' => $data['internship_period_id'],
                    'study_program_id' => $studyProgramId,
                    'status' => $data['status'],
                ]);
            }
        });

        return back()->with('status', 'Koordinator program berhasil ditambahkan.');
    }

    public function edit(InternshipCoordinator $coordinator): View
    {
        return view('management.coordinators.edit', $this->formData() + compact('coordinator'));
    }

    public function update(Request $request, InternshipCoordinator $coordinator): RedirectResponse
    {
        $coordinator->update($this->validated($request, $coordinator));

        return redirect()->route('management.coordinators.index')->with('status', 'Koordinator program berhasil diperbarui.');
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

    private function validatedForStore(Request $request): array
    {
        $data = $request->validate([
            'lecturer_id' => ['required', Rule::exists('lecturers', 'id')->where('status', 'active')],
            'internship_period_id' => ['required', 'exists:internship_periods,id'],
            'study_program_ids' => ['required', 'array', 'min:1'],
            'study_program_ids.*' => ['integer', 'distinct', 'exists:study_programs,id'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $conflicts = InternshipCoordinator::query()
            ->with('studyProgram:id,name')
            ->where('internship_period_id', $data['internship_period_id'])
            ->whereIn('study_program_id', $data['study_program_ids'])
            ->get();

        if ($conflicts->isNotEmpty()) {
            throw ValidationException::withMessages([
                'study_program_ids' => 'Prodi berikut sudah memiliki koordinator pada periode ini: '.$conflicts->pluck('studyProgram.name')->filter()->join(', '),
            ]);
        }

        return $data;
    }

    private function formData(): array
    {
        return [
            'lecturers' => Lecturer::query()->where('status', 'active')->orderBy('name')->get(),
            'periods' => InternshipPeriod::query()->with('program')->orderByDesc('is_active')->orderByDesc('id')->get(),
            'studyPrograms' => StudyProgram::query()->where('is_active', true)->orderBy('name')->get(),
        ];
    }
}
