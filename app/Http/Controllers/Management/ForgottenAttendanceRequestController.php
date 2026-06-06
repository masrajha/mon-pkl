<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Concerns\InteractsWithTableControls;
use App\Http\Controllers\Controller;
use App\Models\ForgottenAttendanceRequest;
use App\Models\InternshipPeriod;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ForgottenAttendanceRequestController extends Controller
{
    use InteractsWithTableControls;

    public function index(Request $request): View
    {
        $query = ForgottenAttendanceRequest::query()
            ->with([
                'enrollment.student',
                'enrollment.studyProgram',
                'enrollment.internshipPeriod.program',
                'enrollment.internshipPlace',
                'reviewer',
                'createdCheckIn',
            ])
            ->latest('id');

        $this->scopeQuery($query, $request);

        if ($request->filled('q')) {
            $search = $request->string('q')->toString();
            $query->where(fn ($query) => $query
                ->whereHas('enrollment.student', fn ($student) => $student->where('full_name', 'like', '%'.$search.'%')->orWhere('npm', 'like', '%'.$search.'%'))
                ->orWhereHas('enrollment.internshipPlace', fn ($place) => $place->where('name', 'like', '%'.$search.'%')));
        }

        $selectedStatus = $request->string('status')->toString();
        if ($selectedStatus !== '') {
            $query->where('status', $selectedStatus);
        }

        $selectedPeriodId = $request->integer('period_id') ?: null;
        if ($selectedPeriodId) {
            $query->whereHas('enrollment', fn ($enrollment) => $enrollment->where('internship_period_id', $selectedPeriodId));
        }

        return view('management.forgotten-attendance-requests.index', [
            'requests' => $this->applyTableSort($query, $request, ['id', 'requested_checked_at', 'status'], 'id', 'desc')
                ->paginate($this->tablePerPage($request))
                ->withQueryString(),
            'selectedStatus' => $selectedStatus,
            'selectedPeriodId' => $selectedPeriodId,
            'periodOptions' => $this->periodOptions($request),
        ]);
    }

    private function scopeQuery($query, Request $request): void
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

        $query->whereHas('enrollment', function ($query) use ($assignments): void {
            $query->where(function ($query) use ($assignments): void {
                foreach ($assignments as $assignment) {
                    $query->orWhere(function ($query) use ($assignment): void {
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
