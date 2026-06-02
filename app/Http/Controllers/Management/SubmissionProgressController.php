<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\InteractsWithTableControls;
use App\Models\SubmissionProgress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SubmissionProgressController extends Controller
{
    use InteractsWithTableControls;

    public function index(Request $request): View
    {
        $query = SubmissionProgress::query()
            ->with(['enrollment.student', 'enrollment.studyProgram', 'enrollment.internshipPeriod.program', 'enrollment.internshipPlace', 'enrollment.lecturer', 'reviewer']);

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

        return view('management.submission-progress.index', [
            'progressItems' => $this->applyTableSort($query, $request, ['uploaded_at', 'status', 'id'], 'uploaded_at', 'desc')
                ->paginate($this->tablePerPage($request))
                ->withQueryString(),
            'selectedStatus' => $request->string('status')->toString(),
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
            'reviewed_at' => now(),
        ]);

        if ($data['status'] === 'approved' && $progress->deadline_type === 'full_report') {
            $progress->enrollment?->update(['final_report_path' => $progress->file_path]);
        }

        return back()->with('status', 'Review progres laporan berhasil disimpan.');
    }

    private function scopeQuery($query, Request $request): void
    {
        $user = $request->user();

        if ($user?->hasRole('admin')) {
            return;
        }

        $assignments = $user->lecturer?->coordinatorAssignments()
            ->where('status', 'active')
            ->get(['internship_period_id', 'study_program_id']) ?? collect();

        if ($user?->role !== 'dosen' && $assignments->isEmpty()) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where(function ($query) use ($user, $assignments): void {
            if ($user?->role === 'dosen') {
                $query->whereHas('enrollment', fn ($enrollment) => $enrollment->where('lecturer_supervisor_user_id', $user->id));
            }

            if ($assignments->isEmpty()) {
                return;
            }

            $query->orWhereHas('enrollment', function ($enrollment) use ($assignments): void {
                foreach ($assignments as $assignment) {
                    $enrollment->orWhere(function ($enrollment) use ($assignment): void {
                        $enrollment->where('internship_period_id', $assignment->internship_period_id)
                            ->where('study_program_id', $assignment->study_program_id);
                    });
                }
            });
        });
    }

    private function authorizeProgress(SubmissionProgress $progress, Request $request): void
    {
        $query = SubmissionProgress::query()->whereKey($progress->id);
        $this->scopeQuery($query, $request);

        abort_unless($query->exists(), 403);
    }

    private function deadlineLabels(): array
    {
        return [
            'proposal' => 'Proposal Rencana Kerja',
            'bab1' => 'Bab I',
            'bab2' => 'Bab II',
            'bab3' => 'Bab III',
            'bab4' => 'Bab IV',
            'bab5' => 'Bab V',
            'full_report' => 'Laporan Lengkap',
            'seminar' => 'Seminar',
            'hardcopy' => 'Hardcover',
        ];
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
}
