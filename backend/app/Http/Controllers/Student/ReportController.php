<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\InternshipEnrollment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function show(Request $request, InternshipEnrollment $enrollment): View
    {
        $this->authorizeEnrollment($request, $enrollment);

        return view('student.reports.show', [
            'enrollment' => $enrollment->load(['student.user', 'studyProgram', 'internshipPeriod', 'internshipPlace', 'lecturer', 'checkIns' => fn ($query) => $query->orderBy('checked_at')]),
        ]);
    }

    public function print(Request $request, InternshipEnrollment $enrollment): View
    {
        $this->authorizeEnrollment($request, $enrollment);

        $enrollment->load(['student.user', 'studyProgram', 'internshipPeriod', 'internshipPlace', 'lecturer', 'checkIns' => fn ($query) => $query->orderBy('checked_at')]);

        $missing = collect([
            'Dosen pembimbing' => ! $enrollment->lecturer,
            'Pembimbing lapangan' => blank($enrollment->field_supervisor),
            'Tempat PKL' => ! $enrollment->internshipPlace,
            'Koordinat tempat PKL' => ! $enrollment->internshipPlace?->latitude || ! $enrollment->internshipPlace?->longitude,
        ])->filter()->keys();

        abort_if($missing->isNotEmpty(), 422, 'Data belum lengkap untuk cetak laporan: '.$missing->implode(', '));

        return view('student.reports.print', compact('enrollment'));
    }

    private function authorizeEnrollment(Request $request, InternshipEnrollment $enrollment): void
    {
        abort_if($enrollment->student?->user_id !== $request->user()->id, 403);
    }
}
