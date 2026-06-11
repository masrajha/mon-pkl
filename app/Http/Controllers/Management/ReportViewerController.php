<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Concerns\InteractsWithTableControls;
use App\Http\Controllers\Controller;
use App\Models\Lecturer;
use App\Models\Organization;
use App\Models\ReportViewerAssignment;
use App\Models\StudyProgram;
use App\Services\EmailNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ReportViewerController extends Controller
{
    use InteractsWithTableControls;

    public function __construct(private readonly EmailNotificationService $emails)
    {
    }

    public function index(Request $request): View
    {
        $query = ReportViewerAssignment::query()
            ->with(['lecturer', 'organization.parent', 'studyProgram']);

        if ($request->filled('q')) {
            $search = $request->string('q')->toString();
            $query->where(fn ($query) => $query
                ->whereHas('lecturer', fn ($query) => $query
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('nip', 'like', '%'.$search.'%'))
                ->orWhereHas('organization', fn ($query) => $query
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('code', 'like', '%'.$search.'%'))
                ->orWhereHas('studyProgram', fn ($query) => $query
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('code', 'like', '%'.$search.'%')));
        }

        if ($request->filled('level')) {
            $query->where('level', $request->string('level')->toString());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        return view('management.report-viewers.index', $this->formData() + [
            'assignments' => $this->applyTableSort($query, $request, ['id', 'level', 'status', 'starts_at', 'ends_at'], 'id', 'desc')
                ->paginate($this->tablePerPage($request))
                ->withQueryString(),
            'selectedLevel' => $request->string('level')->toString(),
            'selectedStatus' => $request->string('status')->toString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $assignment = ReportViewerAssignment::query()->create($this->validated($request));
        $this->queueAssignmentEmail($assignment->loadMissing(['lecturer.user', 'organization.parent', 'studyProgram']));

        return back()->with('status', 'Viewer laporan berhasil ditambahkan.');
    }

    public function edit(ReportViewerAssignment $reportViewer): View
    {
        return view('management.report-viewers.edit', $this->formData() + [
            'assignment' => $reportViewer,
        ]);
    }

    public function update(Request $request, ReportViewerAssignment $reportViewer): RedirectResponse
    {
        $previous = $reportViewer->only(['lecturer_id', 'organization_id', 'study_program_id', 'level', 'status', 'starts_at', 'ends_at']);
        $reportViewer->update($this->validated($request, $reportViewer));
        $reportViewer->loadMissing(['lecturer.user', 'organization.parent', 'studyProgram']);

        if ($reportViewer->only(['lecturer_id', 'organization_id', 'study_program_id', 'level', 'status', 'starts_at', 'ends_at']) !== $previous) {
            $this->queueAssignmentEmail($reportViewer, 'updated');
        }

        return redirect()->route('management.report-viewers.index')->with('status', 'Viewer laporan berhasil diperbarui.');
    }

    public function destroy(ReportViewerAssignment $reportViewer): RedirectResponse
    {
        $reportViewer->delete();

        return back()->with('status', 'Viewer laporan berhasil dihapus.');
    }

    private function validated(Request $request, ?ReportViewerAssignment $assignment = null): array
    {
        $data = $request->validate([
            'lecturer_id' => ['required', Rule::exists('lecturers', 'id')->where('status', 'active')],
            'level' => ['required', Rule::in(['university', 'faculty', 'department', 'study_program'])],
            'organization_id' => ['nullable', 'exists:organizations,id'],
            'study_program_id' => ['nullable', 'exists:study_programs,id'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        if ($data['level'] === 'study_program') {
            $data['organization_id'] = null;

            if (blank($data['study_program_id'] ?? null)) {
                throw ValidationException::withMessages(['study_program_id' => 'Pilih prodi untuk viewer tingkat prodi.']);
            }
        } else {
            $data['study_program_id'] = null;

            if (blank($data['organization_id'] ?? null)) {
                throw ValidationException::withMessages(['organization_id' => 'Pilih organisasi untuk viewer tingkat universitas/fakultas/jurusan.']);
            }

            $organization = Organization::query()->find($data['organization_id']);

            if ($organization && $organization->type !== $data['level']) {
                throw ValidationException::withMessages(['organization_id' => 'Jenis organisasi tidak sesuai dengan level akses yang dipilih.']);
            }
        }

        $duplicate = ReportViewerAssignment::query()
            ->when($assignment, fn ($query) => $query->whereKeyNot($assignment->id))
            ->where('lecturer_id', $data['lecturer_id'])
            ->where('level', $data['level'])
            ->where(fn ($query) => blank($data['organization_id'] ?? null) ? $query->whereNull('organization_id') : $query->where('organization_id', $data['organization_id']))
            ->where(fn ($query) => blank($data['study_program_id'] ?? null) ? $query->whereNull('study_program_id') : $query->where('study_program_id', $data['study_program_id']))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages(['lecturer_id' => 'Dosen ini sudah memiliki assignment viewer pada scope yang sama.']);
        }

        return $data;
    }

    private function formData(): array
    {
        return [
            'lecturers' => Lecturer::query()->where('status', 'active')->orderBy('name')->get(),
            'organizations' => Organization::query()->where('is_active', true)->orderBy('type')->orderBy('name')->get(),
            'studyPrograms' => StudyProgram::query()->where('is_active', true)->orderBy('name')->get(),
            'levelLabels' => [
                'university' => 'Universitas',
                'faculty' => 'Fakultas',
                'department' => 'Jurusan',
                'study_program' => 'Program Studi',
            ],
        ];
    }

    private function queueAssignmentEmail(ReportViewerAssignment $assignment, string $mode = 'created'): void
    {
        $lecturer = $assignment->lecturer;
        $email = $lecturer?->email ?: $lecturer?->user?->email;

        if (! $email) {
            return;
        }

        $this->emails->queue(
            type: 'operational.report_viewer.assigned',
            recipientEmail: $email,
            subject: '[SiLAT] Akses Viewer Laporan',
            bodyLines: [
                $mode === 'updated'
                    ? 'Hak akses Anda sebagai Viewer Laporan telah diperbarui.'
                    : 'Anda ditetapkan sebagai Viewer Laporan di SiLAT.',
                'Scope akses: '.$this->scopeLabel($assignment).'.',
                'Status: '.($assignment->status === 'active' ? 'Aktif' : 'Nonaktif').'.',
                'Masa berlaku: '.($assignment->starts_at?->format('d/m/Y') ?: 'sekarang').' - '.($assignment->ends_at?->format('d/m/Y') ?: 'tidak dibatasi').'.',
                'Akses ini hanya untuk melihat Analisis & Laporan sesuai scope, tanpa aksi operasional workflow.',
            ],
            recipientName: $lecturer?->name ?: $assignment->lecturer?->user?->name,
            actionText: 'Buka Analisis & Laporan',
            actionUrl: route('reports.progress-funnel'),
            notifiable: $assignment,
            eventKey: 'report-viewer-'.$mode.'-'.$assignment->id.'-'.$assignment->updated_at?->timestamp,
        );
    }

    private function scopeLabel(ReportViewerAssignment $assignment): string
    {
        if ($assignment->level === 'study_program') {
            return 'Program Studi - '.($assignment->studyProgram?->name ?: '-');
        }

        $label = $this->formData()['levelLabels'][$assignment->level] ?? $assignment->level;
        $name = $assignment->organization?->name ?: '-';

        return $label.' - '.$name;
    }
}
