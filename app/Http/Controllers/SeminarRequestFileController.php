<?php

namespace App\Http\Controllers;

use App\Models\SeminarRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SeminarRequestFileController extends Controller
{
    public function __invoke(Request $request, SeminarRequest $seminarRequest, string $type): StreamedResponse
    {
        $seminarRequest->loadMissing(['enrollment.student']);
        abort_unless(in_array($type, ['document', 'manual-acc', 'assessment'], true), 404);
        abort_unless($this->canOpen($request, $seminarRequest), 403);

        $path = match ($type) {
            'document' => $seminarRequest->seminar_document_path,
            'manual-acc' => $seminarRequest->manual_acc_path,
            'assessment' => $seminarRequest->assessment_file_path,
        };

        abort_unless($path && Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path);
    }

    private function canOpen(Request $request, SeminarRequest $seminarRequest): bool
    {
        $user = $request->user();
        $enrollment = $seminarRequest->enrollment;

        if (! $user || ! $enrollment) {
            return false;
        }

        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('mahasiswa') && $enrollment->student?->user_id === $user->id) {
            return true;
        }

        if ($user->hasRole('dosen') && $enrollment->lecturer_supervisor_user_id === $user->id) {
            return true;
        }

        return $user->lecturer?->coordinatorAssignments()
            ->where('status', 'active')
            ->where('internship_period_id', $enrollment->internship_period_id)
            ->where('study_program_id', $enrollment->study_program_id)
            ->exists() ?? false;
    }
}
