<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\InteractsWithTableControls;
use App\Models\InternshipEnrollment;
use App\Models\InternshipPeriod;
use App\Models\Program;
use App\Services\OperationalEmailNotificationService;
use App\Services\PeriodConfigurationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PeriodController extends Controller
{
    use InteractsWithTableControls;

    public function __construct(
        private readonly PeriodConfigurationService $configurations,
        private readonly OperationalEmailNotificationService $operationalEmails,
    )
    {
    }

    public function index(Request $request): View
    {
        $query = InternshipPeriod::query()->with('program');

        if ($request->filled('q')) {
            $search = $request->string('q')->toString();
            $query->where(fn ($query) => $query
                ->where('name', 'like', '%'.$search.'%')
                ->orWhere('academic_year', 'like', '%'.$search.'%')
                ->orWhere('semester', 'like', '%'.$search.'%')
                ->orWhereHas('program', fn ($query) => $query->where('name', 'like', '%'.$search.'%')));
        }

        if ($request->filled('program_id')) {
            $query->where('program_id', $request->integer('program_id'));
        }

        if ($request->filled('status')) {
            match ($request->string('status')->toString()) {
                'active' => $query->where('is_active', true)->where('is_locked', false),
                'locked' => $query->where('is_locked', true),
                'inactive' => $query->where('is_active', false)->where('is_locked', false),
                default => null,
            };
        }

        return view('management.periods.index', [
            'periods' => $this->applyTableSort($query, $request, ['name', 'academic_year', 'semester', 'starts_at', 'ends_at', 'id'], 'id', 'desc')
                ->paginate($this->tablePerPage($request))
                ->withQueryString(),
            'programs' => Program::query()->where('is_active', true)->orderBy('name')->get(),
            'selectedProgram' => $request->integer('program_id') ?: null,
            'selectedStatus' => $request->string('status')->toString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $period = InternshipPeriod::query()->create($this->validated($request));
        $this->configurations->seedMissing($request->user()?->id);
        $this->operationalEmails->periodChanged($period, 'created', $request->user());

        return redirect()->route('management.periods.edit', $period)->with('status', 'Periode berhasil ditambahkan.');
    }

    public function edit(InternshipPeriod $period): View
    {
        return view('management.periods.edit', [
            'period' => $period,
            'programs' => Program::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, InternshipPeriod $period): RedirectResponse
    {
        $period->update($this->validated($request, $period));
        $this->operationalEmails->periodChanged($period->refresh(), 'updated', $request->user());

        return redirect()->route('management.periods.index')->with('status', 'Periode berhasil diperbarui.');
    }

    public function complete(Request $request, InternshipPeriod $period): RedirectResponse
    {
        $completedCount = 0;

        DB::transaction(function () use ($period, &$completedCount): void {
            $completedCount = InternshipEnrollment::query()
                ->where('internship_period_id', $period->id)
                ->where('status', 'active')
                ->update(['status' => 'completed']);

            $period->update([
                'is_active' => false,
                'is_locked' => true,
            ]);
        });

        $this->operationalEmails->periodChanged($period->refresh(), 'completed', $request->user(), [
            "{$completedCount} peserta aktif diubah menjadi selesai.",
        ]);

        return redirect()
            ->route('management.periods.index')
            ->with('status', "Periode {$period->display_name} diset selesai. {$completedCount} peserta aktif diubah menjadi selesai.");
    }

    private function validated(Request $request, ?InternshipPeriod $period = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'program_id' => ['nullable', 'exists:programs,id'],
            'academic_year' => ['nullable', 'string', 'max:30'],
            'semester' => ['nullable', 'string', 'max:30'],
            'batch' => ['nullable', 'string', 'max:80'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['nullable', 'boolean'],
            'is_locked' => ['nullable', 'boolean'],
        ]) + ['is_active' => false, 'is_locked' => false];

        $data['program_id'] ??= Program::query()->where('code', 'KP')->value('id');

        return $data;
    }
}
