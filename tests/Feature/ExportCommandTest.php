<?php

namespace ImranDevBd\AttendanceHub\Tests\Feature;

use Carbon\Carbon;
use ImranDevBd\AttendanceHub\Models\AttendanceLog;
use ImranDevBd\AttendanceHub\Tests\TestCase;

class ExportCommandTest extends TestCase
{
    public function test_attendance_export_command(): void
    {
        AttendanceLog::create([
            'device_user_id' => '701',
            'employee_id' => 'EMP-701',
            'punched_at' => Carbon::now(),
            'verify_mode' => 'fingerprint',
            'punch_type' => 'check_in',
            'punch_hash' => 'hash_exp_1',
        ]);

        $this->artisan('attendance:export', [
            '--from' => now()->subDay()->format('Y-m-d'),
            '--to' => now()->format('Y-m-d'),
            '--format' => 'json',
        ])->assertSuccessful();
    }
}
