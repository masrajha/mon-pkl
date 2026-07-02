<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\InteractsWithTableControls;
use App\Models\InternshipEnrollment;
use App\Models\InternshipPeriod;
use App\Models\SubmissionProgress;
use App\Services\ReportScopeService;
use App\Services\SubmissionProgressEmailNotificationService;
use App\Support\LocalClock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SubmissionProgressController extends Controller
{
    use InteractsWithTableControls;

    public function __construct(
        private readonly SubmissionProgressEmailNotificationService $submissionEmails,
        private readonly ReportScopeService $reportScope,
    )
    {
    }

    public function index(Request $request): View
    {
        $query = SubmissionProgress::query()
            ->with([
                'enrollment.student',
                'enrollment.studyProgram',
                'enrollment.internshipPeriod.program',
                'enrollment.internshipPlace',
                'enrollment.lecturer',
                'enrollment.finalAssessment',
                'enrollment.seminarRequests',
                'reviewer',
            ]);

        $this->scopeQuery($query, $request);

        if ($request->filled('q')) {
            $search = $request->string('q')->toString();
            $query->where(fn ($query) => $query
                ->whereHas('enrollment.student', fn ($student) => $student->where('full_name', 'like', '%'.$search.'%')->orWhere('npm', 'like', '%'.$search.'%'))
                ->orWhereHas('enrollment.internshipPlace', fn ($place) => $place->where('name', 'like', '%'.$search.'%')));
        }

        if ($request->filled('status')) {
            $status = $request->string('status')->toString();
            $query->where('status', $status === 'revision' ? 'revision_required' : $status);
        }
        $selectedPeriodId = $request->integer('period_id') ?: null;

        if ($selectedPeriodId) {
            $query->whereHas('enrollment', fn ($enrollment) => $enrollment->where('internship_period_id', $selectedPeriodId));
        }

        return view('management.submission-progress.index', [
            'progressItems' => $this->applyTableSort($query, $request, ['uploaded_at', 'status', 'id'], 'uploaded_at', 'desc')
                ->paginate($this->tablePerPage($request))
                ->withQueryString(),
            'selectedStatus' => $request->string('status')->toString(),
            'selectedPeriodId' => $selectedPeriodId,
            'periodOptions' => $this->periodOptions($request),
            'deadlineLabels' => $this->deadlineLabels(),
            'statusLabels' => $this->statusLabels(),
        ]);
    }

    public function update(Request $request, SubmissionProgress $progress): RedirectResponse
    {
        $this->authorizeProgress($progress, $request);
        abort_if($progress->status === 'approved', 403, 'Dokumen yang sudah disetujui sudah terkunci.');

        $data = $request->validate([
            'status' => ['required', Rule::in(['approved', 'revision_required', 'rejected'])],
            'lecturer_note' => [
                Rule::requiredIf(fn () => in_array($request->input('status'), ['revision_required', 'rejected'], true)),
                'nullable',
                'string',
                'max:3000',
            ],
        ]);

        $progress->update($data + [
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => LocalClock::now(),
        ]);

        if ($data['status'] === 'approved' && $progress->deadline_type === 'full_report') {
            $progress->enrollment?->update(['final_report_path' => $progress->file_path]);
        }

        $this->submissionEmails->reviewed($progress->refresh());

        return back()->with('status', 'Review progres laporan berhasil disimpan.');
    }

    public function reopen(Request $request, SubmissionProgress $progress): RedirectResponse
    {
        $this->authorizeProgress($progress, $request);
        $progress->loadMissing(['enrollment.finalAssessment', 'enrollment.seminarRequests']);

        abort_unless($progress->status === 'approved', 403, 'Hanya dokumen yang sudah disetujui yang dapat dibuka ulang.');
        abort_if($progress->enrollment?->finalAssessment, 403, 'Review tidak dapat dibuka ulang karena nilai akhir sudah difinalisasi.');
        abort_if($this->hasCompletedSeminar($progress), 403, 'Review tidak dapat dibuka ulang karena seminar sudah selesai atau nilai dosen sudah masuk.');

        $data = $request->validate([
            'reopen_status' => ['required', Rule::in(['pending', 'revision_required'])],
            'reopen_reason' => ['required', 'string', 'max:3000'],
        ], [
            'reopen_reason.required' => 'Alasan buka ulang review wajib diisi.',
        ]);

        DB::transaction(function () use ($progress, $request, $data): void {
            $progress->update([
                'status' => $data['reopen_status'],
                'lecturer_note' => $data['reopen_reason'],
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => LocalClock::now(),
            ]);

            $enrollment = $progress->enrollment;

            if (
                $enrollment
                && $progress->deadline_type === 'full_report'
                && $enrollment->final_report_path === $progress->file_path
            ) {
                $enrollment->update(['final_report_path' => null]);
            }
        });

        $this->submissionEmails->reviewed($progress->refresh());

        return back()->with('status', 'Review laporan berhasil dibuka ulang.');
    }

    private function scopeQuery($query, Request $request): void
    {
        $user = $request->user();

        if ($user?->hasRole('admin')) {
            return;
        }

        if (! $user?->hasRole(['dosen', 'koordinator'])) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereHas('enrollment', fn ($enrollment) => $this->reportScope->applyEnrollmentScope($enrollment, $user));
    }

    private function authorizeProgress(SubmissionProgress $progress, Request $request): void
    {
        $query = SubmissionProgress::query()->whereKey($progress->id);
        $this->scopeQuery($query, $request);

        abort_unless($query->exists(), 403);
    }

    private function deadlineLabels(): array
    {
        return config('monpkl.report_submission_types');
    }

    private function statusLabels(): array
    {
        return [
            'pending' => 'Menunggu Review',
            'approved' => 'Disetujui',
            'revision_required' => 'Perlu Revisi',
            'revision' => 'Perlu Revisi',
            'rejected' => 'Ditolak',
        ];
    }

    private function hasCompletedSeminar(SubmissionProgress $progress): bool
    {
        return (bool) $progress->enrollment?->seminarRequests
            ?->contains(fn ($seminarRequest): bool => $seminarRequest->status === 'completed'
                || filled($seminarRequest->completed_at)
                || filled($seminarRequest->seminar_score));
    }

    private function periodOptions(Request $request)
    {
        $query = InternshipPeriod::query()->with('program')->orderByDesc('starts_at')->orderByDesc('id');
        $user = $request->user();

        if ($user?->hasRole('admin')) {
            return $query->get();
        }

        $periodIds = $this->reportScope
            ->applyEnrollmentScope(InternshipEnrollment::query(), $user)
            ->pluck('internship_period_id');

        return $query->whereIn('id', $periodIds->filter()->unique()->values())->get();
    }
}
