<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ReportScopeService
{
    public function applyEnrollmentScope(Builder $query, ?User $user, bool $includeLecturerSupervision = true): Builder
    {
        if ($user?->hasRole('admin')) {
            return $query;
        }

        if ($user?->hasRole(['dosen', 'koordinator', 'report_viewer'])) {
            $coordinatorAssignments = $user->lecturer?->coordinatorAssignments()
                ->where('status', 'active')
                ->get(['internship_period_id', 'study_program_id']) ?? collect();
            $viewerStudyProgramIds = $this->studyProgramIdsForUser($user);

            return $query->where(function (Builder $query) use ($user, $includeLecturerSupervision, $coordinatorAssignments, $viewerStudyProgramIds): void {
                $hasCondition = false;

                if ($includeLecturerSupervision && $user->role === 'dosen') {
                    $query->where(function (Builder $query) use ($user): void {
                        if ($user->lecturer?->id) {
                            $query->where('lecturer_supervisor_id', $user->lecturer->id);
                        }

                        $query->orWhere('lecturer_supervisor_user_id', $user->id)
                            ->orWhere('lecturer_supervisor', $user->name)
                            ->orWhere('lecturer_supervisor', $user->email);
                    });

                    $hasCondition = true;
                }

                $coordinatorAssignments->each(function ($assignment) use ($query, &$hasCondition): void {
                    $method = $hasCondition ? 'orWhere' : 'where';

                    $query->{$method}(function (Builder $query) use ($assignment): void {
                        $query->where('internship_period_id', $assignment->internship_period_id)
                            ->where('study_program_id', $assignment->study_program_id);
                    });

                    $hasCondition = true;
                });

                if ($viewerStudyProgramIds->isNotEmpty()) {
                    $method = $hasCondition ? 'orWhereIn' : 'whereIn';
                    $query->{$method}('study_program_id', $viewerStudyProgramIds->all());
                    $hasCondition = true;
                }

                if (! $hasCondition) {
                    $query->whereRaw('1 = 0');
                }
            });
        }

        return $query->whereHas('student', fn (Builder $query) => $query->where('user_id', $user?->id));
    }

    public function studyProgramIdsForUser(?User $user): Collection
    {
        if (! $user?->lecturer) {
            return collect();
        }

        $assignments = $user->lecturer->reportViewerAssignments()
            ->with('organization')
            ->where('status', 'active')
            ->where(fn (Builder $query) => $query->whereNull('starts_at')->orWhereDate('starts_at', '<=', now()->toDateString()))
            ->where(fn (Builder $query) => $query->whereNull('ends_at')->orWhereDate('ends_at', '>=', now()->toDateString()))
            ->get();

        $studyProgramIds = collect();

        foreach ($assignments as $assignment) {
            if ($assignment->study_program_id) {
                $studyProgramIds->push((int) $assignment->study_program_id);
            }

            if ($assignment->organization_id) {
                $organizationIds = $this->organizationAndDescendantIds((int) $assignment->organization_id);
                $studyProgramIds = $studyProgramIds->merge(
                    \App\Models\StudyProgram::query()
                        ->whereIn('organization_id', $organizationIds)
                        ->pluck('id')
                        ->map(fn ($id): int => (int) $id)
                );
            }
        }

        return $studyProgramIds->filter()->unique()->values();
    }

    public function organizationAndDescendantIds(int $organizationId): Collection
    {
        $organizations = Organization::query()->get(['id', 'parent_id']);
        $ids = collect([$organizationId]);
        $frontier = collect([$organizationId]);

        while ($frontier->isNotEmpty()) {
            $children = $organizations
                ->whereIn('parent_id', $frontier->all())
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->values();

            $children = $children->diff($ids);

            if ($children->isEmpty()) {
                break;
            }

            $ids = $ids->merge($children)->unique()->values();
            $frontier = $children;
        }

        return $ids;
    }
}
