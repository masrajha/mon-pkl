<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Concerns\InteractsWithTableControls;
use App\Http\Controllers\Controller;
use App\Models\InternshipPeriod;
use App\Models\WfaRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WfaRequestController extends Controller
{
    use InteractsWithTableControls;

    public function index(Request $request): View
    {
        $query = WfaRequest::query()
            ->with([
                'enrollment.student',
                'enrollment.studyProgram',
                'enrollment.internshipPeriod.program',
                'enrollment.internshipPlace',
                'reviewer',
            ])
            ->latest('id');

        $this->scopeQuery($query, $request);

        if ($request->filled('q')) {
            $search = mb_strtolower(trim($request->string('q')->toString()));
            $like = '%'.$search.'%';
            $query->where(fn (Builder $query) => $query
                ->whereRaw('LOWER(planned_location) LIKE ?', [$like])
                ->orWhereHas('enrollment.student', fn (Builder $student) => $student
                    ->whereRaw('LOWER(full_name) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(npm) LIKE ?', [$like]))
                ->orWhereHas('enrollment.internshipPlace', fn (Builder $place) => $place->whereRaw('LOWER(name) LIKE ?', [$like])));
        }

        $selectedStatus = $request->string('status')->toString();
        if ($selectedStatus !== '') {
            $query->where('status', $selectedStatus);
        }

        $selectedPeriodId = $request->integer('period_id') ?: null;
        if ($selectedPeriodId) {
            $query->whereHas('enrollment', fn (Builder $enrollment) => $enrollment->where('internship_period_id', $selectedPeriodId));
        }

        return view('management.wfa-requests.index', [
            'requests' => $this->applyTableSort($query, $request, ['id', 'starts_at', 'ends_at', 'status'], 'id', 'desc')
                ->paginate($this->tablePerPage($request))
                ->withQueryString(),
            'selectedStatus' => $selectedStatus,
            'selectedPeriodId' => $selectedPeriodId,
            'periodOptions' => $this->periodOptions($request),
        ]);
    }

    private function scopeQuery(Builder $query, Request $request): void
    {
        $user = $request->user();

        if ($user?->hasRole('admin')) {
            return;
        }

        $assignments = $user?->lecturer?->coordinatorAssignments()
            ->where('status', 'active')
            ->get(['internship_period_id', 'study_program_id']) ?? collect();

        if ($assignments->isEmpty()) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereHas('enrollment', function (Builder $query) use ($assignments): void {
            $query->where(function (Builder $query) use ($assignments): void {
                foreach ($assignments as $assignment) {
                    $query->orWhere(function (Builder $query) use ($assignment): void {
                        $query->where('internship_period_id', $assignment->internship_period_id)
                            ->where('study_program_id', $assignment->study_program_id);
                    });
                }
            });
        });
    }

    private function periodOptions(Request $request)
    {
        $query = InternshipPeriod::query()->with('program')->orderByDesc('starts_at')->orderByDesc('id');
        $user = $request->user();

        if (! $user?->hasRole('admin')) {
            $periodIds = $user?->lecturer?->coordinatorAssignments()
                ->where('status', 'active')
                ->pluck('internship_period_id')
                ->filter()
                ->unique()
                ->values() ?? collect();

            $query->whereIn('id', $periodIds);
        }

        return $query->get();
    }
}
