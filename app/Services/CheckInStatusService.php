<?php

namespace App\Services;

use Carbon\Carbon;

class CheckInStatusService
{
    public function statusFor(Carbon $time, ?array $configuration = null): string
    {
        $clock = $this->minutesOfDay($time);
        $schedule = $configuration['check_in']['schedule'] ?? config('monpkl.check_in.schedule', []);

        foreach ($schedule as $slot) {
            $start = $this->timeToMinutes($slot['start'] ?? '00:00');
            $end = $this->timeToMinutes($slot['end'] ?? '00:00');

            if ($clock >= $start && $clock < $end) {
                return $slot['status'] ?? 'Tidak Aktif';
            }
        }

        return 'Tidak Aktif';
    }

    private function minutesOfDay(Carbon $time): int
    {
        return ((int) $time->format('H')) * 60 + (int) $time->format('i');
    }

    private function timeToMinutes(string $value): int
    {
        [$hour, $minute] = array_pad(explode(':', $value, 2), 2, 0);

        return ((int) $hour) * 60 + (int) $minute;
    }
}
