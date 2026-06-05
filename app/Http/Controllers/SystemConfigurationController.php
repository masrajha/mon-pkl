<?php

namespace App\Http\Controllers;

use App\Models\InternshipPeriod;
use App\Models\InternshipPeriodSetting;
use App\Models\PeriodDeadline;
use App\Services\PeriodConfigurationService;
use App\Http\Controllers\Concerns\InteractsWithTableControls;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SystemConfigurationController extends Controller
{
    use InteractsWithTableControls;

    public function __construct(private readonly PeriodConfigurationService $configurations)
    {
    }

    public function index(Request $request): View
    {
        $query = InternshipPeriod::query()->with(['program', 'setting']);

        if ($request->filled('q')) {
            $search = $request->string('q')->toString();
            $query->where(fn ($query) => $query
                ->where('name', 'like', '%'.$search.'%')
                ->orWhere('academic_year', 'like', '%'.$search.'%')
                ->orWhereHas('program', fn ($query) => $query->where('name', 'like', '%'.$search.'%')));
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->string('status')->toString() === 'active');
        }

        return view('system-configurations.index', [
            'periods' => $this->applyTableSort($query, $request, ['name', 'academic_year', 'is_active', 'id'], 'id', 'desc')
                ->paginate($this->tablePerPage($request))
                ->withQueryString(),
            'selectedStatus' => $request->string('status')->toString(),
        ]);
    }

    public function edit(InternshipPeriod $period): View
    {
        return view('system-configurations.edit', [
            'period' => $period->load(['program', 'deadlines']),
            'settings' => $this->configurations->forPeriod($period),
            'deadlineTypes' => $this->deadlineTypes(),
        ]);
    }

    public function update(Request $request, InternshipPeriod $period): RedirectResponse
    {
        abort_if($period->is_locked && ! $this->isSuperAdmin($request), 403, 'Periode sudah terkunci. Konfigurasi tidak dapat diubah.');

        $validated = $request->validate([
            'timezone' => ['required', 'string', 'max:80'],
            'enrollment.min_place_quota' => ['required', 'integer', 'min:1', 'max:100'],
            'enrollment.max_place_quota' => ['required', 'integer', 'min:1', 'max:100'],
            'enrollment.minimum_total_sks_s1' => ['required', 'integer', 'min:0', 'max:250'],
            'enrollment.minimum_total_sks_d3' => ['required', 'integer', 'min:0', 'max:250'],
            'enrollment.minimum_semester_s1' => ['required', 'integer', 'min:1', 'max:20'],
            'enrollment.minimum_semester_d3' => ['required', 'integer', 'min:1', 'max:20'],
            'enrollment.minimum_gpa' => ['required', 'numeric', 'min:0', 'max:4'],
            'workflow.allow_place_proposal' => ['nullable', 'boolean'],
            'workflow.allow_relocation' => ['nullable', 'boolean'],
            'workflow.allow_supervisor_change' => ['nullable', 'boolean'],
            'check_in.recent_limit' => ['required', 'integer', 'min:1', 'max:100'],
            'check_in.photo_max_kb' => ['required', 'integer', 'min:1', 'max:20480'],
            'check_in.max_distance_meters' => ['required', 'integer', 'min:0', 'max:100000'],
            'check_in.min_daily_duration_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'check_in.insufficient_duration_penalty_per_hour' => ['required', 'integer', 'min:0', 'max:1000'],
            'check_in.inactive_message' => ['required', 'string', 'max:255'],
            'check_in.schedule' => ['required', 'array', 'min:1'],
            'check_in.schedule.*.status' => ['required', 'string', 'max:80'],
            'check_in.schedule.*.start' => ['required', 'date_format:H:i'],
            'check_in.schedule.*.end' => ['required', 'date_format:H:i'],
            'report.single_check_in_cutoff' => ['required', 'date_format:H:i'],
            'report.single_morning_checkout_hour' => ['required', 'integer', 'between:0,23'],
            'report.single_afternoon_checkin_hour' => ['required', 'integer', 'between:0,23'],
            'calendar.holidays_text' => ['nullable', 'string'],
            'distance.earth_radius_meters' => ['required', 'integer', 'min:1'],
            'map.center.lat' => ['required', 'numeric', 'between:-90,90'],
            'map.center.lng' => ['required', 'numeric', 'between:-180,180'],
            'map.zoom' => ['required', 'integer', 'between:1,22'],
            'map.max_zoom' => ['required', 'integer', 'between:1,22'],
            'map.fit_max_zoom' => ['required', 'integer', 'between:1,22'],
            'map.office_zoom' => ['required', 'integer', 'between:1,22'],
            'map.current_location_zoom' => ['required', 'integer', 'between:1,22'],
            'map.tile_url' => ['required', 'string', 'max:255'],
            'map.tile_attribution' => ['required', 'string', 'max:255'],
            'map.geolocation.timeout_ms' => ['required', 'integer', 'min:1000', 'max:120000'],
            'map.geolocation.maximum_age_ms' => ['required', 'integer', 'min:0', 'max:3600000'],
            'map.monitoring_limit_default' => ['required', 'integer', 'min:1', 'max:10000'],
            'map.monitoring_limit_max' => ['required', 'integer', 'min:1', 'max:20000'],
            'deadlines' => ['nullable', 'array'],
            'deadlines.*.deadline_type' => ['required_with:deadlines', 'string', 'max:50', Rule::in(array_keys($this->deadlineTypes()))],
            'deadlines.*.deadline_date' => ['nullable', 'date'],
            'deadlines.*.penalty_points' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'deadlines.*.is_fixed_penalty' => ['nullable', 'boolean'],
        ]);

        $defaults = $this->configurations->defaults();
        $settings = array_replace_recursive($defaults, $validated);
        unset($settings['deadlines']);
        if ($settings['enrollment']['min_place_quota'] > $settings['enrollment']['max_place_quota']) {
            return back()
                ->withErrors(['enrollment.min_place_quota' => 'Kuota minimal tidak boleh lebih besar dari kuota maksimal.'])
                ->withInput();
        }
        $settings['calendar']['holidays'] = $this->parseHolidays($request->input('calendar.holidays_text'));
        unset($settings['calendar']['holidays_text']);
        $settings['workflow']['allow_place_proposal'] = $request->boolean('workflow.allow_place_proposal');
        $settings['workflow']['allow_relocation'] = $request->boolean('workflow.allow_relocation');
        $settings['workflow']['allow_supervisor_change'] = $request->boolean('workflow.allow_supervisor_change');
        $settings['check_in']['photo_disk'] = $defaults['check_in']['photo_disk'];
        $settings['check_in']['photo_directory'] = $defaults['check_in']['photo_directory'];
        $settings['map']['geolocation']['enable_high_accuracy'] = $request->boolean('map.geolocation.enable_high_accuracy');

        DB::transaction(function () use ($period, $request, $settings): void {
            InternshipPeriodSetting::query()->updateOrCreate(
                ['internship_period_id' => $period->id],
                [
                    'settings' => $settings,
                    'updated_by' => $request->user()?->id,
                ],
            );

            collect($request->input('deadlines', []))
                ->filter(fn (array $deadline) => ! empty($deadline['deadline_date']))
                ->each(function (array $deadline) use ($period): void {
                    PeriodDeadline::query()->updateOrCreate(
                        [
                            'internship_period_id' => $period->id,
                            'deadline_type' => $deadline['deadline_type'],
                        ],
                        [
                            'deadline_date' => $deadline['deadline_date'],
                            'penalty_points' => $deadline['penalty_points'] ?? 0,
                            'is_fixed_penalty' => (bool) ($deadline['is_fixed_penalty'] ?? false),
                        ],
                    );
                });
        });

        return redirect()
            ->route('system-configurations.edit', $period)
            ->with('status', 'Konfigurasi sistem berhasil disimpan.');
    }

    private function parseHolidays(?string $value): array
    {
        return collect(preg_split('/[\s,]+/', (string) $value))
            ->map(fn (string $date) => trim($date))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function deadlineTypes(): array
    {
        return config('monpkl.deadline_types');
    }

    private function isSuperAdmin(Request $request): bool
    {
        return in_array($request->user()?->email, config('monpkl.super_admin_emails', []), true);
    }
}
