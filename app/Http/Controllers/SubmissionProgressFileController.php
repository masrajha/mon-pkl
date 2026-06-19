<?php

namespace App\Http\Controllers;

use App\Models\SubmissionProgress;
use App\Models\InternshipEnrollment;
use App\Services\ReportScopeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubmissionProgressFileController extends Controller
{
    public function __invoke(Request $request, SubmissionProgress $progress, ReportScopeService $reportScope): StreamedResponse
    {
        $progress->loadMissing('enrollment');

        abort_unless($this->canOpen($request, $progress, $reportScope), 403);
        abort_unless(Storage::disk('public')->exists($progress->file_path), 404);

        return Storage::disk('public')->response($progress->file_path);
    }

    private function canOpen(Request $request, SubmissionProgress $progress, ReportScopeService $reportScope): bool
    {
        $user = $request->user();
        $enrollment = $progress->enrollment;

        if (! $user || ! $enrollment) {
            return false;
        }

        return $reportScope
            ->applyEnrollmentScope(InternshipEnrollment::query()->whereKey($enrollment->id), $user)
            ->exists();
    }
}
