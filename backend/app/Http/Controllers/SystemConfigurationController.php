<?php

namespace App\Http\Controllers;

use App\Models\InternshipPeriod;
use App\Models\InternshipPeriodSetting;
use App\Services\PeriodConfigurationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SystemConfigurationController extends Controller
{
    public function __construct(private readonly PeriodConfigurationService $configurations)
    {
    }

    public function index(): View
    {
        return view('system-configurations.index', [
            'periods' => InternshipPeriod::query()
                ->with('setting')
                ->orderByDesc('is_active')
                ->orderByDesc('id')
                ->get(),
        ]);
    }

    public function edit(InternshipPeriod $period): View
    {
        return view('system-configurations.edit', [
            'period' => $period,
            'settings' => $this->configurations->forPeriod($period),
        ]);
    }

    public function update(Request $request, InternshipPeriod $period): RedirectResponse
    {
        $validated = $request->validate([
            'timezone' => ['required', 'string', 'max:80'],
            'check_in.recent_limit' => ['required', 'integer', 'min:1', 'max:100'],
            'check_in.photo_max_kb' => ['required', 'integer', 'min:1', 'max:20480'],
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
        ]);

        $defaults = $this->configurations->defaults();
        $settings = array_replace_recursive($defaults, $validated);
        $settings['calendar']['holidays'] = $this->parseHolidays($request->input('calendar.holidays_text'));
        unset($settings['calendar']['holidays_text']);
        $settings['check_in']['photo_disk'] = $defaults['check_in']['photo_disk'];
        $settings['check_in']['photo_directory'] = $defaults['check_in']['photo_directory'];
        $settings['map']['geolocation']['enable_high_accuracy'] = $request->boolean('map.geolocation.enable_high_accuracy');

        InternshipPeriodSetting::query()->updateOrCreate(
            ['internship_period_id' => $period->id],
            [
                'settings' => $settings,
                'updated_by' => $request->user()?->id,
            ],
        );

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
}
