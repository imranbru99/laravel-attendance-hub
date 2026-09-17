<?php

namespace ImranDevBd\AttendanceHub\Tests\Feature;

use Carbon\Carbon;
use ImranDevBd\AttendanceHub\DTOs\AttendanceShift;
use ImranDevBd\AttendanceHub\DTOs\DailyAttendanceSummary;
use ImranDevBd\AttendanceHub\Models\AttendanceDevice;
use ImranDevBd\AttendanceHub\Models\AttendanceLog;
use ImranDevBd\AttendanceHub\Support\AttendanceCalculator;
use ImranDevBd\AttendanceHub\Tests\TestCase;

class ShiftCalculatorTest extends TestCase
{
    public function test_calculate_on_time_daily_summary(): void
    {
        $device = AttendanceDevice::create(['name' => 'ZK Test', 'provider' => 'zkteco']);

        // First punch at 08:55 AM
        AttendanceLog::create([
            'device_id' => $device->id,
            'device_user_id' => '101',
            'employee_id' => 'EMP-101',
            'punched_at' => Carbon::parse('2026-09-17 08:55:00'),
            'punch_hash' => 'hash1',
            'punch_type' => 'check_in',
        ]);

        // Second punch at 06:05 PM
        AttendanceLog::create([
            'device_id' => $device->id,
            'device_user_id' => '101',
            'employee_id' => 'EMP-101',
            'punched_at' => Carbon::parse('2026-09-17 18:05:00'),
            'punch_hash' => 'hash2',
            'punch_type' => 'check_out',
        ]);

        $shift = new AttendanceShift(
            startTime: '09:00',
            endTime: '18:00',
            gracePeriodMinutes: 15,
            breakDurationMinutes: 60
        );

        $calculator = new AttendanceCalculator($shift);
        $summary = $calculator->calculate('EMP-101', '2026-09-17');

        $this->assertEquals(DailyAttendanceSummary::STATUS_PRESENT, $summary->status);
        $this->assertEquals(0, $summary->lateMinutes);
        $this->assertEquals(0, $summary->earlyLeaveMinutes);
        $this->assertGreaterThan(480, $summary->totalWorkedMinutes);
    }

    public function test_calculate_late_arrival(): void
    {
        $device = AttendanceDevice::create(['name' => 'ZK Test 2', 'provider' => 'zkteco']);

        // Punch at 09:35 AM (grace period is 15 mins till 09:15)
        AttendanceLog::create([
            'device_id' => $device->id,
            'device_user_id' => '102',
            'employee_id' => 'EMP-102',
            'punched_at' => Carbon::parse('2026-09-17 09:35:00'),
            'punch_hash' => 'hash3',
        ]);

        AttendanceLog::create([
            'device_id' => $device->id,
            'device_user_id' => '102',
            'employee_id' => 'EMP-102',
            'punched_at' => Carbon::parse('2026-09-17 18:00:00'),
            'punch_hash' => 'hash4',
        ]);

        $calculator = new AttendanceCalculator();
        $summary = $calculator->calculate('EMP-102', '2026-09-17');

        $this->assertEquals(DailyAttendanceSummary::STATUS_LATE, $summary->status);
        $this->assertEquals(35, $summary->lateMinutes);
    }

    public function test_absent_when_no_punches(): void
    {
        $calculator = new AttendanceCalculator();
        $summary = $calculator->calculate('EMP-UNKNOWN', '2026-09-17');

        $this->assertEquals(DailyAttendanceSummary::STATUS_ABSENT, $summary->status);
        $this->assertEquals(0, $summary->punchesCount);
    }
}
