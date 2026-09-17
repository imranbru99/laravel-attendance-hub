<?php

namespace ImranDevBd\AttendanceHub\Tests\Feature;

use Carbon\Carbon;
use ImranDevBd\AttendanceHub\DTOs\AttendancePunch;
use ImranDevBd\AttendanceHub\Models\AttendanceLog;
use ImranDevBd\AttendanceHub\Support\AttendanceNormalizer;
use ImranDevBd\AttendanceHub\Tests\TestCase;

class PunchPipelineTest extends TestCase
{
    public function test_debounce_rapid_double_taps(): void
    {
        config(['attendance-hub.pipeline.debounce_minutes' => 2]);

        $normalizer = new AttendanceNormalizer();

        $punch1 = new AttendancePunch(
            deviceUserId: '505',
            punchedAt: Carbon::create(2026, 9, 17, 9, 0, 0)
        );

        $log1 = $normalizer->record($punch1);
        $this->assertNotNull($log1);

        // Employee accidentally taps fingerprint again 45 seconds later
        $punch2 = new AttendancePunch(
            deviceUserId: '505',
            punchedAt: Carbon::create(2026, 9, 17, 9, 0, 45)
        );

        $log2 = $normalizer->record($punch2);
        $this->assertNull($log2);

        $this->assertEquals(1, AttendanceLog::where('device_user_id', '505')->count());
    }
}
