<?php

namespace App\Http\Controllers;

use App\Models\SubmissionProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubmissionProgressFileController extends Controller
{
    public function __invoke(Request $request, SubmissionProgress $progress): StreamedResponse
    {
        $progress->loadMissing(['enrollment.student', 'enrollment.lecturer']);

        abort_unless($this->canOpen($request, $progress), 403);
        abort_unless(Storage::disk('public')->exists($progress->file_path), 404);

        return Storage::disk('public')->response($progress->file_path);
    }

    private function canOpen(Request $request, SubmissionProgress $progress): bool
    {
        $user = $request->user();
        $enrollment = $progress->enrollment;

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
