<?php

namespace Tests\Unit;

use App\Services\CheckInStatusService;
use Carbon\Carbon;
use Tests\TestCase;

class CheckInStatusServiceTest extends TestCase
{
    public function test_status_is_resolved_from_configuration(): void
    {
        config()->set('monpkl.check_in.schedule', [
            ['status' => 'Masuk Khusus', 'start' => '06:30', 'end' => '09:00'],
        ]);

        $status = app(CheckInStatusService::class)
            ->statusFor(Carbon::create(2026, 5, 31, 8, 0, 0, config('monpkl.timezone')));

        $this->assertSame('Masuk Khusus', $status);
    }
}
