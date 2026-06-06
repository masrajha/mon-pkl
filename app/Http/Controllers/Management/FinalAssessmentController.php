<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\InteractsWithTableControls;
use App\Models\FinalAssessment;
use App\Models\InternshipEnrollment;
use App\Models\InternshipPeriod;
use App\Models\SeminarRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FinalAssessmentController extends Controller
{
    use InteractsWithTableControls;

    public function index(Request $request): View
    {
        $query = InternshipEnrollment::query()
            ->with([
                'student',
                'studyProgram',
                'internshipPeriod.program',
                'internshipPlace',
                'lecturer',
                'fieldSupervisorAssessment',
                'finalAssessment.finalizer',
                'seminarRequests' => fn ($query) => $query
                    ->whereNotNull('seminar_score')
                    ->latest('scored_at')
                    ->latest('id'),
            ])
            ->whereNotIn('status', ['cancelled', 'rejected']);

        $this->scopeQuery($query, $request);

        if ($request->filled('q')) {
            $search = $request->string('q')->toString();
            $query->where(fn ($query) => $query
                ->whereHas('student', fn ($student) => $student->where('full_name', 'like', '%'.$search.'%')->orWhere('npm', 'like', '%'.$search.'%'))
                ->orWhereHas('internshipPlace', fn ($place) => $place->where('name', 'like', '%'.$search.'%'))
                ->orWhereHas('internshipPeriod', fn ($period) => $period->where('name', 'like', '%'.$search.'%')));
        }
        $selectedPeriodId = $request->integer('period_id') ?: null;

        if ($selectedPeriodId) {
            $query->where('internship_period_id', $selectedPeriodId);
        }

        $filter = $request->string('status')->toString();

        if ($filter !== '') {
            match ($filter) {
                'ready' => $query
                    ->whereDoesntHave('finalAssessment')
                    ->whereHas('fieldSupervisorAssessment')
                    ->whereHas('seminarRequests', fn ($seminar) => $seminar->whereNotNull('seminar_score')),
                'finalized' => $query->whereHas('finalAssessment'),
                'not_ready' => $query->where(fn ($query) => $query
                    ->whereDoesntHave('fieldSupervisorAssessment')
                    ->orWhereDoesntHave('seminarRequests', fn ($seminar) => $seminar->whereNotNull('seminar_score'))),
                default => null,
            };
        }

        $enrollments = $this->applyTableSort($query, $request, ['id', 'status'], 'id', 'desc')
            ->paginate($this->tablePerPage($request))
            ->withQueryString();

        $summaries = $enrollments->getCollection()
            ->map(fn (InternshipEnrollment $enrollment): array => $this->assessmentSummary($enrollment))
            ->values();

        return view('management.final-assessments.index', [
            'enrollments' => $enrollments,
            'summaries' => $summaries,
            'selectedStatus' => $filter,
            'selectedPeriodId' => $selectedPeriodId,
            'periodOptions' => $this->periodOptions($request),
        ]);
    }

    public function store(Request $request, InternshipEnrollment $enrollment): RedirectResponse
    {
        $enrollment->loadMissing([
            'fieldSupervisorAssessment',
            'seminarRequests' => fn ($query) => $query
                ->whereNotNull('seminar_score')
                ->latest('scored_at')
                ->latest('id'),
        ]);

        $this->authorizeEnrollment($enrollment, $request);

        $summary = $this->assessmentSummary($enrollment);

        if (! $summary['ready']) {
            throw ValidationException::withMessages([
                'final_assessment' => 'Finalisasi belum dapat disimpan. Nilai dosen dan pembimbing lapangan wajib lengkap.',
            ]);
        }

        $data = $request->validate([
            'final_deduction' => ['required', 'numeric', 'min:0', 'max:100'],
            'note' => ['nullable', 'string', 'max:3000'],
        ]);

        $finalDeduction = round((float) $data['final_deduction'], 2);
        $finalScore = round(max(0, min(100, $summary['baseScore'] - $finalDeduction)), 2);

        DB::transaction(function () use ($enrollment, $request, $summary, $data, $finalDeduction, $finalScore): void {
            FinalAssessment::query()->updateOrCreate(
                ['internship_enrollment_id' => $enrollment->id],
                [
                    'lecturer_score' => $summary['lecturerScore'],
                    'field_supervisor_score' => $summary['fieldSupervisorScore'],
                    'lecturer_weight' => 50,
                    'field_supervisor_weight' => 50,
                    'base_score' => $summary['baseScore'],
                    'suggested_deduction' => $summary['suggestedDeduction'],
                    'final_deduction' => $finalDeduction,
                    'final_score' => $finalScore,
                    'note' => $data['note'] ?? null,
                    'finalized_by' => $request->user()->id,
                    'finalized_at' => now(),
                ]
            );
        });

        return back()->with('status', 'Nilai akhir mahasiswa berhasil difinalisasi.');
    }

    private function assessmentSummary(InternshipEnrollment $enrollment): array
    {
        $seminarRequest = $enrollment->seminarRequests
            ->first(fn (SeminarRequest $seminarRequest): bool => $seminarRequest->seminar_score !== null);
        $lecturerScore = $seminarRequest?->seminar_score !== null ? round((float) $seminarRequest->seminar_score, 2) : null;
        $fieldSupervisorScore = $enrollment->fieldSupervisorAssessment?->final_score !== null
            ? round((float) $enrollment->fieldSupervisorAssessment->final_score, 2)
            : null;
        $baseScore = $lecturerScore !== null && $fieldSupervisorScore !== null
            ? round(($lecturerScore * 0.5) + ($fieldSupervisorScore * 0.5), 2)
            : null;
        $suggestedDeduction = round((float) ($enrollment->total_sanctions_points ?? 0), 2);

        return [
            'enrollment' => $enrollment,
            'seminarRequest' => $seminarRequest,
            'lecturerScore' => $lecturerScore,
            'fieldSupervisorScore' => $fieldSupervisorScore,
            'baseScore' => $baseScore,
            'suggestedDeduction' => $suggestedDeduction,
            'suggestedFinalScore' => $baseScore !== null ? round(max(0, min(100, $baseScore - $suggestedDeduction)), 2) : null,
            'finalAssessment' => $enrollment->finalAssessment,
            'ready' => $lecturerScore !== null && $fieldSupervisorScore !== null,
        ];
    }

    private function scopeQuery($query, Request $request): void
    {
        $user = $request->user();

        if ($user?->hasRole('admin')) {
            return;
        }

        $assignments = $user?->lecturer?->coordinatorAssignments()
            ->where('status', 'active')
            ->get(['internship_period_id', 'study_program_id']) ?? collect();

        if ($assignments->isEmpty()) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where(function ($query) use ($assignments): void {
            foreach ($assignments as $assignment) {
                $query->orWhere(function ($query) use ($assignment): void {
                    $query->where('internship_period_id', $assignment->internship_period_id)
                        ->where('study_program_id', $assignment->study_program_id);
                });
            }
        });
    }

    private function authorizeEnrollment(InternshipEnrollment $enrollment, Request $request): void
    {
        if ($request->user()?->hasRole('admin')) {
            return;
        }

        $allowed = $request->user()?->lecturer?->coordinatorAssignments()
            ->where('status', 'active')
            ->where('internship_period_id', $enrollment->internship_period_id)
            ->where('study_program_id', $enrollment->study_program_id)
            ->exists();

        abort_unless($allowed, 403);
    }

    private function periodOptions(Request $request)
    {
        $query = InternshipPeriod::query()->with('program')->orderByDesc('starts_at')->orderByDesc('id');
        $user = $request->user();

        if (! $user?->hasRole('admin')) {
            $periodIds = $user?->lecturer?->coordinatorAssignments()
                ->where('status', 'active')
                ->pluck('internship_period_id')
                ->filter()
                ->unique()
                ->values() ?? collect();

            $query->whereIn('id', $periodIds);
        }

        return $query->get();
    }
}
