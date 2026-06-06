<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\InteractsWithTableControls;
use App\Models\InternshipEnrollment;
use App\Models\InternshipPeriod;
use App\Models\SeminarRequest;
use App\Services\AssessmentEmailNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SeminarRequestController extends Controller
{
    use InteractsWithTableControls;

    public function __construct(private readonly AssessmentEmailNotificationService $assessmentEmails)
    {
    }

    public function index(Request $request): View
    {
        $query = SeminarRequest::query()
            ->with([
                'enrollment.student',
                'enrollment.studyProgram',
                'enrollment.internshipPeriod.program',
                'enrollment.internshipPlace',
                'enrollment.lecturer',
                'enrollment.finalAssessment',
                'lecturerApprover',
                'manualAccValidator',
                'assessmentValidator',
                'scheduler',
                'scorer',
            ]);

        $this->scopeQuery($query, $request);

        if ($request->filled('q')) {
            $search = $request->string('q')->toString();
            $query->where(fn ($query) => $query
                ->where('title', 'like', '%'.$search.'%')
                ->orWhereHas('enrollment.student', fn ($student) => $student->where('full_name', 'like', '%'.$search.'%')->orWhere('npm', 'like', '%'.$search.'%'))
                ->orWhereHas('enrollment.internshipPlace', fn ($place) => $place->where('name', 'like', '%'.$search.'%')));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }
        $selectedPeriodId = $request->integer('period_id') ?: null;

        if ($selectedPeriodId) {
            $query->whereHas('enrollment', fn ($enrollment) => $enrollment->where('internship_period_id', $selectedPeriodId));
        }

        return view('management.seminar-requests.index', [
            'seminarRequests' => $this->applyTableSort($query, $request, ['id', 'status', 'scheduled_at'], 'id', 'desc')
                ->paginate($this->tablePerPage($request))
                ->withQueryString(),
            'selectedStatus' => $request->string('status')->toString(),
            'selectedPeriodId' => $selectedPeriodId,
            'periodOptions' => $this->periodOptions($request),
            'statusLabels' => $this->statusLabels(),
            'seminarRubric' => $this->seminarRubric(),
        ]);
    }

    public function lecturerDecision(Request $request, SeminarRequest $seminarRequest): RedirectResponse
    {
        $seminarRequest->loadMissing('enrollment');
        $this->authorizeSeminar($seminarRequest, $request, lecturerOnly: true);

        if ($seminarRequest->status !== 'waiting_lecturer_approval') {
            throw ValidationException::withMessages(['status' => 'Pengajuan ini tidak sedang menunggu ACC dosen.']);
        }

        $data = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'revision_required', 'rejected'])],
            'lecturer_note' => [
                Rule::requiredIf(fn () => in_array($request->input('decision'), ['revision_required', 'rejected'], true)),
                'nullable',
                'string',
                'max:3000',
            ],
        ]);

        $status = match ($data['decision']) {
            'approve' => 'lecturer_approved',
            'revision_required' => 'revision_required',
            'rejected' => 'rejected',
        };

        $seminarRequest->update([
            'status' => $status,
            'lecturer_note' => $data['lecturer_note'] ?? null,
            'lecturer_approved_by' => $data['decision'] === 'approve' ? $request->user()->id : null,
            'lecturer_approved_at' => $data['decision'] === 'approve' ? now() : null,
        ]);

        return back()->with('status', 'Keputusan ACC seminar berhasil disimpan.');
    }

    public function validateManualAcc(Request $request, SeminarRequest $seminarRequest): RedirectResponse
    {
        $seminarRequest->loadMissing('enrollment');
        $this->authorizeSeminar($seminarRequest, $request, coordinatorOrAdminOnly: true);

        if ($seminarRequest->status !== 'waiting_manual_acc_validation') {
            throw ValidationException::withMessages(['status' => 'Pengajuan ini tidak sedang menunggu validasi ACC manual.']);
        }

        $data = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'revision_required', 'rejected'])],
            'admin_note' => [
                Rule::requiredIf(fn () => in_array($request->input('decision'), ['revision_required', 'rejected'], true)),
                'nullable',
                'string',
                'max:3000',
            ],
        ]);

        $status = match ($data['decision']) {
            'approve' => 'manual_acc_approved',
            'revision_required' => 'revision_required',
            'rejected' => 'rejected',
        };

        $seminarRequest->update([
            'status' => $status,
            'admin_note' => $data['admin_note'] ?? null,
            'manual_acc_validated_by' => $data['decision'] === 'approve' ? $request->user()->id : null,
            'manual_acc_validated_at' => $data['decision'] === 'approve' ? now() : null,
        ]);

        return back()->with('status', 'Validasi ACC seminar berhasil disimpan.');
    }

    public function schedule(Request $request, SeminarRequest $seminarRequest): RedirectResponse
    {
        $seminarRequest->loadMissing('enrollment');
        $this->authorizeSeminar($seminarRequest, $request, coordinatorOrAdminOnly: true);

        if (! in_array($seminarRequest->status, ['lecturer_approved', 'manual_acc_approved', 'scheduled'], true)) {
            throw ValidationException::withMessages(['status' => 'Pengajuan seminar belum siap dijadwalkan.']);
        }

        $data = $request->validate([
            'scheduled_at' => ['required', 'date'],
            'mode' => ['required', Rule::in(['offline', 'online', 'hybrid'])],
            'location' => ['nullable', 'string', 'max:255'],
            'meeting_url' => ['nullable', 'url', 'max:2000'],
            'admin_note' => ['nullable', 'string', 'max:3000'],
        ]);

        $seminarRequest->update([
            'status' => 'scheduled',
            'scheduled_at' => $data['scheduled_at'],
            'mode' => $data['mode'],
            'location' => $data['location'] ?? null,
            'meeting_url' => $data['meeting_url'] ?? null,
            'admin_note' => $data['admin_note'] ?? $seminarRequest->admin_note,
            'scheduled_by' => $request->user()->id,
        ]);

        $this->assessmentEmails->seminarScheduled($seminarRequest->refresh());

        return back()->with('status', 'Jadwal seminar berhasil disimpan.');
    }

    public function score(Request $request, SeminarRequest $seminarRequest): RedirectResponse
    {
        $seminarRequest->loadMissing('enrollment');
        $this->authorizeSeminar($seminarRequest, $request, lecturerOnly: true);
        $this->ensureNotFinalized($seminarRequest);

        if (! in_array($seminarRequest->status, ['scheduled', 'completed'], true)) {
            throw ValidationException::withMessages(['status' => 'Seminar belum terjadwal.']);
        }

        $data = $request->validate([
            'seminar_score_note' => ['nullable', 'string', 'max:3000'],
            'assessment_method' => ['required', Rule::in(['system'])],
            'assessment_scores' => ['required', 'array'],
        ] + collect($this->seminarRubric())
            ->mapWithKeys(fn (array $item, string $key) => ['assessment_scores.'.$key => ['required', 'numeric', 'min:0', 'max:100']])
            ->all());

        $assessmentScores = collect($this->seminarRubric())
            ->mapWithKeys(function (array $item, string $key) use ($data) {
                $score = (float) data_get($data, 'assessment_scores.'.$key);

                return [$key => [
                    'label' => $item['label'],
                    'group' => $item['group'],
                    'weight' => $item['weight'],
                    'score' => $score,
                    'weighted_score' => round($score * $item['weight'] / 100, 2),
                ]];
            })
            ->all();

        $seminarRequest->update([
            'status' => 'completed',
            'completed_at' => $seminarRequest->completed_at ?: now(),
            'seminar_score' => round(collect($assessmentScores)->sum('weighted_score'), 2),
            'seminar_score_note' => $data['seminar_score_note'] ?? null,
            'assessment_method' => 'system',
            'assessment_scores' => $assessmentScores,
            'assessment_file_path' => null,
            'assessment_validated_by' => null,
            'assessment_validated_at' => null,
            'scored_by' => $request->user()->id,
            'scored_at' => now(),
        ]);

        $this->assessmentEmails->lecturerScoreStored($seminarRequest->refresh());

        return back()->with('status', 'Nilai seminar via sistem berhasil disimpan.');
    }

    public function validateManualAssessment(Request $request, SeminarRequest $seminarRequest): RedirectResponse
    {
        $seminarRequest->loadMissing('enrollment');
        $this->authorizeSeminar($seminarRequest, $request, coordinatorOrAdminOnly: true);
        $this->ensureNotFinalized($seminarRequest);

        if ($seminarRequest->status !== 'waiting_assessment_validation' || $seminarRequest->assessment_method !== 'manual') {
            throw ValidationException::withMessages(['status' => 'Pengajuan nilai manual ini tidak sedang menunggu validasi.']);
        }

        $data = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'revision_required', 'rejected'])],
            'admin_note' => [
                Rule::requiredIf(fn () => in_array($request->input('decision'), ['revision_required', 'rejected'], true)),
                'nullable',
                'string',
                'max:3000',
            ],
        ]);

        $status = match ($data['decision']) {
            'approve' => 'completed',
            'revision_required' => 'assessment_revision_required',
            'rejected' => 'rejected',
        };

        $seminarRequest->update([
            'status' => $status,
            'completed_at' => $data['decision'] === 'approve' ? ($seminarRequest->completed_at ?: now()) : null,
            'admin_note' => $data['admin_note'] ?? null,
            'assessment_validated_by' => $data['decision'] === 'approve' ? $request->user()->id : null,
            'assessment_validated_at' => $data['decision'] === 'approve' ? now() : null,
            'scored_by' => $data['decision'] === 'approve' ? $request->user()->id : null,
            'scored_at' => $data['decision'] === 'approve' ? now() : null,
        ]);

        if ($data['decision'] === 'approve') {
            $this->assessmentEmails->lecturerScoreStored($seminarRequest->refresh());
        }

        return back()->with('status', 'Validasi nilai manual seminar berhasil disimpan.');
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

        if ($user?->role !== 'dosen' && $assignments->isEmpty()) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereHas('enrollment', function ($enrollment) use ($user, $assignments): void {
            $enrollment->where(function ($enrollment) use ($user, $assignments): void {
                if ($user?->role === 'dosen') {
                    $enrollment->where('lecturer_supervisor_user_id', $user->id);
                }

                foreach ($assignments as $assignment) {
                    $enrollment->orWhere(function ($enrollment) use ($assignment): void {
                        $enrollment->where('internship_period_id', $assignment->internship_period_id)
                            ->where('study_program_id', $assignment->study_program_id);
                    });
                }
            });
        });
    }

    private function authorizeSeminar(SeminarRequest $seminarRequest, Request $request, bool $lecturerOnly = false, bool $coordinatorOrAdminOnly = false): void
    {
        $user = $request->user();
        $enrollment = $seminarRequest->enrollment;

        if ($lecturerOnly) {
            abort_unless($user?->hasRole('dosen') && (int) $enrollment?->lecturer_supervisor_user_id === (int) $user->id, 403);

            return;
        }

        if ($coordinatorOrAdminOnly && $user?->hasRole('admin')) {
            return;
        }

        if ($coordinatorOrAdminOnly) {
            $allowed = $user?->lecturer?->coordinatorAssignments()
                ->where('status', 'active')
                ->where('internship_period_id', $enrollment?->internship_period_id)
                ->where('study_program_id', $enrollment?->study_program_id)
                ->exists();

            abort_unless($allowed, 403);
        }
    }

    private function ensureNotFinalized(SeminarRequest $seminarRequest): void
    {
        $seminarRequest->loadMissing('enrollment.finalAssessment');

        if ($seminarRequest->enrollment?->finalAssessment) {
            throw ValidationException::withMessages([
                'score' => 'Nilai dosen sudah terkunci karena nilai akhir telah difinalisasi.',
            ]);
        }
    }

    private function statusLabels(): array
    {
        return [
            'waiting_lecturer_approval' => 'Menunggu ACC Dosen',
            'waiting_manual_acc_validation' => 'Menunggu Validasi ACC',
            'waiting_assessment_validation' => 'Menunggu Validasi Nilai Manual',
            'assessment_revision_required' => 'Revisi Nilai Manual',
            'lecturer_approved' => 'ACC Dosen',
            'manual_acc_approved' => 'ACC Manual Valid',
            'revision_required' => 'Perlu Revisi',
            'scheduled' => 'Terjadwal',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
            'rejected' => 'Ditolak',
        ];
    }

    private function seminarRubric(): array
    {
        return config('monpkl.seminar_assessment_rubric', []);
    }

    private function periodOptions(Request $request)
    {
        $query = InternshipPeriod::query()->with('program')->orderByDesc('starts_at')->orderByDesc('id');
        $user = $request->user();

        if ($user?->hasRole('admin')) {
            return $query->get();
        }

        $periodIds = collect();

        if ($user?->role === 'dosen') {
            $periodIds = $periodIds->merge(
                InternshipEnrollment::query()
                    ->where('lecturer_supervisor_user_id', $user->id)
                    ->pluck('internship_period_id')
            );
        }

        $periodIds = $periodIds->merge($user?->lecturer?->coordinatorAssignments()
            ->where('status', 'active')
            ->pluck('internship_period_id') ?? collect());

        return $query->whereIn('id', $periodIds->filter()->unique()->values())->get();
    }
}
