<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\InternshipEnrollment;
use App\Models\SeminarRequest;
use App\Models\SubmissionProgress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SeminarRequestController extends Controller
{
    public function store(Request $request, InternshipEnrollment $enrollment): RedirectResponse
    {
        $this->authorizeEnrollment($request, $enrollment);
        abort_unless(in_array($enrollment->status, ['active', 'completed'], true), 403);

        $this->ensureCanRequestSeminar($enrollment);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'proposed_date' => ['nullable', 'date'],
            'proposed_time' => ['nullable', 'date_format:H:i'],
            'mode' => ['required', Rule::in(['offline', 'online', 'hybrid'])],
            'location' => ['nullable', 'string', 'max:255'],
            'meeting_url' => ['nullable', 'url', 'max:2000'],
            'approval_method' => ['required', Rule::in(['system', 'manual_upload'])],
            'student_note' => ['nullable', 'string', 'max:2000'],
            'seminar_document' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
            'manual_acc' => [
                Rule::requiredIf(fn () => $request->input('approval_method') === 'manual_upload'),
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:5120',
            ],
        ]);

        $activeExists = SeminarRequest::query()
            ->where('internship_enrollment_id', $enrollment->id)
            ->whereNotIn('status', ['completed', 'cancelled', 'rejected'])
            ->exists();

        if ($activeExists) {
            throw ValidationException::withMessages([
                'title' => 'Masih ada pengajuan seminar aktif. Selesaikan, batalkan, atau tunggu keputusan terlebih dahulu.',
            ]);
        }

        $seminarDocumentPath = $request->file('seminar_document')?->store('seminar-requests', 'public');
        $manualAccPath = $request->file('manual_acc')?->store('seminar-requests/acc', 'public');

        SeminarRequest::query()->create([
            'internship_enrollment_id' => $enrollment->id,
            'title' => $data['title'],
            'proposed_date' => $data['proposed_date'] ?? null,
            'proposed_time' => $data['proposed_time'] ?? null,
            'mode' => $data['mode'],
            'location' => $data['location'] ?? null,
            'meeting_url' => $data['meeting_url'] ?? null,
            'approval_method' => $data['approval_method'],
            'student_note' => $data['student_note'] ?? null,
            'seminar_document_path' => $seminarDocumentPath,
            'manual_acc_path' => $manualAccPath,
            'status' => $data['approval_method'] === 'manual_upload'
                ? 'waiting_manual_acc_validation'
                : 'waiting_lecturer_approval',
        ]);

        return back()->with('status', 'Pengajuan seminar berhasil dikirim.');
    }

    public function cancel(Request $request, SeminarRequest $seminarRequest): RedirectResponse
    {
        $seminarRequest->loadMissing('enrollment.student');
        $this->authorizeEnrollment($request, $seminarRequest->enrollment);

        if (! in_array($seminarRequest->status, ['waiting_lecturer_approval', 'waiting_manual_acc_validation', 'revision_required'], true)) {
            throw ValidationException::withMessages([
                'seminar' => 'Pengajuan seminar ini tidak dapat dibatalkan.',
            ]);
        }

        $seminarRequest->update([
            'status' => 'cancelled',
            'admin_note' => 'Dibatalkan oleh mahasiswa.',
        ]);

        return back()->with('status', 'Pengajuan seminar dibatalkan.');
    }

    public function submitManualAssessment(Request $request, SeminarRequest $seminarRequest): RedirectResponse
    {
        $seminarRequest->loadMissing('enrollment.student');
        $this->authorizeEnrollment($request, $seminarRequest->enrollment);
        $seminarRequest->loadMissing('enrollment.finalAssessment');

        if ($seminarRequest->enrollment?->finalAssessment) {
            throw ValidationException::withMessages([
                'assessment' => 'Nilai dosen sudah terkunci karena nilai akhir telah difinalisasi.',
            ]);
        }

        if (! in_array($seminarRequest->status, ['scheduled', 'waiting_assessment_validation', 'assessment_revision_required'], true)) {
            throw ValidationException::withMessages([
                'assessment' => 'Nilai manual baru dapat diajukan setelah seminar terjadwal.',
            ]);
        }

        $rubric = $this->seminarRubric();
        $rules = [
            'assessment_file' => [
                Rule::requiredIf(fn () => ! $seminarRequest->assessment_file_path),
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:5120',
            ],
            'seminar_score_note' => ['nullable', 'string', 'max:3000'],
        ];

        foreach (array_keys($rubric) as $key) {
            $rules['assessment_scores.'.$key] = ['required', 'numeric', 'min:0', 'max:100'];
        }

        $data = $request->validate($rules);

        $assessmentScores = collect($rubric)
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

        $assessmentFilePath = $seminarRequest->assessment_file_path;
        if ($request->hasFile('assessment_file')) {
            $assessmentFilePath = $request->file('assessment_file')->store('seminar-requests/assessments', 'public');
        }

        $seminarRequest->update([
            'status' => 'waiting_assessment_validation',
            'seminar_score' => round(collect($assessmentScores)->sum('weighted_score'), 2),
            'seminar_score_note' => $data['seminar_score_note'] ?? null,
            'assessment_method' => 'manual',
            'assessment_scores' => $assessmentScores,
            'assessment_file_path' => $assessmentFilePath,
            'assessment_validated_by' => null,
            'assessment_validated_at' => null,
        ]);

        return back()->with('status', 'Nilai seminar manual berhasil diajukan untuk validasi.');
    }

    private function authorizeEnrollment(Request $request, ?InternshipEnrollment $enrollment): void
    {
        abort_unless($enrollment && $enrollment->student?->user_id === $request->user()->id, 403);
    }

    private function ensureCanRequestSeminar(InternshipEnrollment $enrollment): void
    {
        if (! $enrollment->lecturer_supervisor_id && ! $enrollment->lecturer_supervisor_user_id) {
            throw ValidationException::withMessages([
                'title' => 'Dosen pembimbing wajib ditentukan sebelum pengajuan seminar.',
            ]);
        }

        $hasFullReport = SubmissionProgress::query()
            ->where('internship_enrollment_id', $enrollment->id)
            ->where('deadline_type', 'full_report')
            ->where('status', '!=', 'rejected')
            ->exists();

        if (! $hasFullReport) {
            throw ValidationException::withMessages([
                'title' => 'Pengajuan seminar baru dapat dilakukan setelah unggah Pelaporan Tahap 4 (Laporan Lengkap): Bab 1 s.d 5.',
            ]);
        }
    }

    private function seminarRubric(): array
    {
        return config('monpkl.seminar_assessment_rubric', []);
    }
}
