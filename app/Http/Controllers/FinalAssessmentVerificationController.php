<?php

namespace App\Http\Controllers;

use App\Models\FinalAssessment;
use Illuminate\View\View;

class FinalAssessmentVerificationController extends Controller
{
    public function show(string $token): View
    {
        $finalAssessment = FinalAssessment::query()
            ->with([
                'enrollment.student',
                'enrollment.studyProgram',
                'enrollment.internshipPeriod.program',
                'enrollment.internshipPlace',
                'enrollment.lecturer',
                'finalizer',
            ])
            ->where('verification_token', $token)
            ->firstOrFail();

        return view('final-assessments.verify', [
            'finalAssessment' => $finalAssessment,
            'enrollment' => $finalAssessment->enrollment,
            'letterGrade' => $this->letterGrade((float) $finalAssessment->final_score),
        ]);
    }

    private function letterGrade(float $score): string
    {
        return match (true) {
            $score >= 76 => 'A',
            $score >= 71 => 'B+',
            $score >= 66 => 'B',
            $score >= 61 => 'C+',
            $score >= 56 => 'C',
            default => 'BL',
        };
    }
}
