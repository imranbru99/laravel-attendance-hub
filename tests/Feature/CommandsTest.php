<?php

namespace ImranDevBd\AttendanceHub\Tests\Feature;

use ImranDevBd\AttendanceHub\Models\AttendanceDevice;
use ImranDevBd\AttendanceHub\Models\DeviceUserMap;
use ImranDevBd\AttendanceHub\Tests\TestCase;

class CommandsTest extends TestCase
{
    public function test_device_health_command(): void
    {
        $device = AttendanceDevice::create([
            'name' => 'Virtual Test Terminal',
            'provider' => 'virtual',
            'status' => 'offline',
        ]);

        $this->artisan('attendance:health', ['device' => $device->id])
            ->assertSuccessful();

        $device->refresh();
        $this->assertEquals('online', $device->status);
    }

    public function test_sync_command_without_devices(): void
    {
        $this->artisan('attendance:sync', ['--all' => true])
            ->expectsOutput('No attendance devices found to sync.')
            ->assertSuccessful();
    }

    public function test_sync_command_fails_without_arguments(): void
    {
        $this->artisan('attendance:sync')
            ->expectsOutput('Please specify a device ID or use the --all flag.')
            ->assertFailed();
    }
}
