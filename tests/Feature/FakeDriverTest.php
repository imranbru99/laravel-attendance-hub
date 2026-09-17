<?php

namespace ImranDevBd\AttendanceHub\Tests\Feature;

use ImranDevBd\AttendanceHub\DTOs\AttendancePunch;
use ImranDevBd\AttendanceHub\DTOs\DeviceConnection;
use ImranDevBd\AttendanceHub\DTOs\DeviceUser;
use ImranDevBd\AttendanceHub\Enums\VerifyMode;
use ImranDevBd\AttendanceHub\Exceptions\DeviceConnectionException;
use ImranDevBd\AttendanceHub\Facades\AttendanceHub;
use ImranDevBd\AttendanceHub\Models\AttendanceDevice;
use ImranDevBd\AttendanceHub\Tests\TestCase;

class FakeDriverTest extends TestCase
{
    public function test_fake_driver_simulation_and_sync(): void
    {
        $fake = AttendanceHub::fake();
        $this->assertTrue(AttendanceHub::isFake());

        $device = AttendanceDevice::create([
            'name' => 'Simulated Office Terminal',
            'provider' => 'zkteco', // Requested as zkteco, but intercepted by fake()!
            'ip' => '192.168.1.200',
        ]);

        $logs = AttendanceHub::sync($device);

        $this->assertNotEmpty($logs);
        $this->assertDatabaseHas('attendance_logs', [
            'device_user_id' => '101',
        ]);

        $fake->assertConnected();
    }

    public function test_fake_driver_assertions_and_user_push(): void
    {
        $fake = AttendanceHub::fake();

        $driver = AttendanceHub::provider('fake');
        $driver->connect(new DeviceConnection(ip: '10.0.0.1'));

        $driver->pushUser(new DeviceUser(
            uid: '201',
            userId: '201',
            name: 'Jane Doe'
        ));

        $fake->assertUserPushed('201');

        $driver->clearLogs();
        $fake->assertLogsCleared();
    }

    public function test_fake_driver_simulated_connection_failure(): void
    {
        $this->expectException(DeviceConnectionException::class);

        $fake = AttendanceHub::fake();
        $fake->shouldFailConnection(true);

        $driver = AttendanceHub::provider();
        $driver->connect(new DeviceConnection(ip: '192.168.1.99'));
    }
}
