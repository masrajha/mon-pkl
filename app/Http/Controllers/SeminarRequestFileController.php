<?php

namespace App\Http\Controllers;

use App\Models\InternshipEnrollment;
use App\Models\SeminarRequest;
use App\Services\ReportScopeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SeminarRequestFileController extends Controller
{
    public function __invoke(Request $request, SeminarRequest $seminarRequest, string $type, ReportScopeService $reportScope): StreamedResponse
    {
        $seminarRequest->loadMissing('enrollment');
        abort_unless(in_array($type, ['document', 'manual-acc', 'assessment'], true), 404);
        abort_unless($this->canOpen($request, $seminarRequest, $reportScope), 403);

        $path = match ($type) {
            'document' => $seminarRequest->seminar_document_path,
            'manual-acc' => $seminarRequest->manual_acc_path,
            'assessment' => $seminarRequest->assessment_file_path,
        };

        abort_unless($path && Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path);
    }

    private function canOpen(Request $request, SeminarRequest $seminarRequest, ReportScopeService $reportScope): bool
    {
        $user = $request->user();
        $enrollment = $seminarRequest->enrollment;

        if (! $user || ! $enrollment) {
            return false;
        }

        return $reportScope
            ->applyEnrollmentScope(InternshipEnrollment::query()->whereKey($enrollment->id), $user)
            ->exists();
    }
}
