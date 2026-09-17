<?php

namespace ImranDevBd\AttendanceHub\Tests\Feature;

use ImranDevBd\AttendanceHub\Models\AttendanceDevice;
use ImranDevBd\AttendanceHub\Models\AttendanceLog;
use ImranDevBd\AttendanceHub\Tests\TestCase;

class SimulateCommandTest extends TestCase
{
    public function test_attendance_simulate_command_generates_devices_and_punches(): void
    {
        $this->artisan('attendance:simulate', [
            '--employees' => 3,
            '--days' => 2,
            '--tenant' => 'tenant-test-corp',
        ])
            ->expectsOutputToContain('Initializing simulated environment')
            ->expectsOutputToContain('Simulation complete!')
            ->assertExitCode(0);

        // Assert simulated device created
        $device = AttendanceDevice::where('serial_number', 'SIM_DEV_001')->first();
        $this->assertNotNull($device);
        $this->assertEquals('tenant-test-corp', $device->tenant_id);
        $this->assertEquals('fake', $device->provider);

        // Assert attendance logs created
        $logs = AttendanceLog::where('device_id', $device->id)->get();
        $this->assertNotEmpty($logs);

        // Verify check-in and check-out types exist
        $this->assertTrue($logs->contains('punch_type', 'check_in'));
        $this->assertTrue($logs->contains('punch_type', 'check_out'));
    }
}
