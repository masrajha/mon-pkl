<?php

namespace App\Services;

use App\Models\InternshipPeriod;
use App\Models\InternshipPeriodSetting;

class PeriodConfigurationService
{
    public function defaults(): array
    {
        return [
            'timezone' => config('monpkl.timezone'),
            'check_in' => config('monpkl.check_in'),
            'report' => config('monpkl.report'),
            'final_assessment_document' => config('monpkl.final_assessment_document'),
            'calendar' => config('monpkl.calendar'),
            'enrollment' => config('monpkl.enrollment'),
            'distance' => config('monpkl.distance'),
            'map' => config('monpkl.map'),
        ];
    }

    public function forPeriod(null|int|InternshipPeriod $period): array
    {
        $period = $this->resolvePeriod($period);
        $settings = $period?->setting?->settings ?? [];

        return array_replace_recursive($this->defaults(), $settings);
    }

    public function forActivePeriod(): array
    {
        return $this->forPeriod(
            InternshipPeriod::query()
                ->with('setting')
                ->orderByDesc('is_active')
                ->orderByDesc('id')
                ->first()
        );
    }

    public function frontendMapConfig(null|int|InternshipPeriod $period): array
    {
        $map = $this->forPeriod($period)['map'];

        return [
            'center' => $map['center'],
            'zoom' => $map['zoom'],
            'maxZoom' => $map['max_zoom'],
            'fitMaxZoom' => $map['fit_max_zoom'],
            'officeZoom' => $map['office_zoom'],
            'currentLocationZoom' => $map['current_location_zoom'],
            'tileUrl' => $map['tile_url'],
            'tileAttribution' => $map['tile_attribution'],
            'geolocation' => $map['geolocation'],
        ];
    }

    public function seedMissing(?int $updatedBy = null): void
    {
        InternshipPeriod::query()
            ->whereDoesntHave('setting')
            ->each(function (InternshipPeriod $period) use ($updatedBy): void {
                InternshipPeriodSetting::query()->create([
                    'internship_period_id' => $period->id,
                    'settings' => $this->defaults(),
                    'updated_by' => $updatedBy,
                ]);
            });
    }

    private function resolvePeriod(null|int|InternshipPeriod $period): ?InternshipPeriod
    {
        if ($period instanceof InternshipPeriod) {
            return $period->loadMissing('setting');
        }

        if ($period) {
            return InternshipPeriod::query()->with('setting')->find($period);
        }

        return null;
    }
}
