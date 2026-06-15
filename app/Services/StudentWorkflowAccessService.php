<?php

namespace App\Services;

use App\Models\InternshipEnrollment;
use App\Models\InternshipPeriod;
use App\Support\LocalClock;
use Carbon\CarbonInterface;

class StudentWorkflowAccessService
{
    public function __construct(private readonly PeriodConfigurationService $configurations)
    {
    }

    public function registrationOpen(InternshipPeriod $period, ?CarbonInterface $date = null): bool
    {
        if ($period->is_locked) {
            return false;
        }

        $today = ($date ?: LocalClock::today())->toDateString();
        $start = $period->deadlines->firstWhere('deadline_type', 'registration_start')?->deadline_date;
        $end = $period->deadlines->firstWhere('deadline_type', 'registration_end')?->deadline_date;

        if ($start && $today < $start->toDateString()) {
            return false;
        }

        if ($end && $today > $end->toDateString()) {
            return false;
        }

        return true;
    }

    public function placeProposalOpen(InternshipPeriod $period): bool
    {
        return $this->workflowEnabled($period, 'allow_place_proposal');
    }

    public function relocationOpen(InternshipEnrollment $enrollment): bool
    {
        return $enrollment->internshipPeriod
            && $this->workflowEnabled($enrollment->internshipPeriod, 'allow_relocation');
    }

    public function supervisorChangeOpen(InternshipEnrollment $enrollment): bool
    {
        return $enrollment->internshipPeriod
            && $this->workflowEnabled($enrollment->internshipPeriod, 'allow_supervisor_change');
    }

    private function workflowEnabled(InternshipPeriod $period, string $key): bool
    {
        if ($period->is_locked) {
            return false;
        }

        return (bool) data_get($this->configurations->forPeriod($period), "workflow.{$key}", true);
    }
}
