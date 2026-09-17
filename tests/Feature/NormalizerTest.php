<?php

namespace ImranDevBd\AttendanceHub\Tests\Feature;

use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use ImranDevBd\AttendanceHub\DTOs\AttendancePunch;
use ImranDevBd\AttendanceHub\Enums\PunchType;
use ImranDevBd\AttendanceHub\Enums\VerifyMode;
use ImranDevBd\AttendanceHub\Events\AttendanceRecorded;
use ImranDevBd\AttendanceHub\Models\AttendanceDevice;
use ImranDevBd\AttendanceHub\Models\AttendanceLog;
use ImranDevBd\AttendanceHub\Models\DeviceUserMap;
use ImranDevBd\AttendanceHub\Support\AttendanceNormalizer;
use ImranDevBd\AttendanceHub\Tests\TestCase;

class NormalizerTest extends TestCase
{
    public function test_normalizer_persists_log_and_dispatches_event(): void
    {
        Event::fake([AttendanceRecorded::class]);

        $device = AttendanceDevice::create([
            'name' => 'Main Gate ZK',
            'provider' => 'zkteco',
            'ip' => '192.168.1.100',
            'port' => 4370,
        ]);

        $punch = new AttendancePunch(
            deviceUserId: '101',
            punchedAt: Carbon::create(2026, 9, 17, 9, 15, 0),
            verifyMode: VerifyMode::FINGERPRINT,
            punchType: PunchType::CHECK_IN,
            deviceId: (string) $device->id
        );

        $normalizer = new AttendanceNormalizer();
        $log = $normalizer->record($punch, $device);

        $this->assertInstanceOf(AttendanceLog::class, $log);
        $this->assertDatabaseHas('attendance_logs', [
            'device_id' => $device->id,
            'device_user_id' => '101',
            'verify_mode' => 'fingerprint',
            'punch_type' => 'check_in',
        ]);

        Event::assertDispatched(AttendanceRecorded::class, function ($event) use ($log) {
            return $event->log->id === $log->id;
        });
    }

    public function test_deduplication_prevents_duplicate_records(): void
    {
        $device = AttendanceDevice::create([
            'name' => 'HQ Gate',
            'provider' => 'zkteco',
            'ip' => '192.168.1.101',
        ]);

        $punch = new AttendancePunch(
            deviceUserId: '202',
            punchedAt: Carbon::create(2026, 9, 17, 8, 45, 0),
            verifyMode: VerifyMode::FACE,
            punchType: PunchType::CHECK_IN,
            deviceId: (string) $device->id
        );

        $normalizer = new AttendanceNormalizer();
        $log1 = $normalizer->record($punch, $device);
        $log2 = $normalizer->record($punch, $device);

        $this->assertEquals($log1->id, $log2->id);
        $this->assertEquals(1, AttendanceLog::where('device_user_id', '202')->count());
    }

    public function test_employee_id_mapping_resolution(): void
    {
        $device = AttendanceDevice::create([
            'name' => 'Factory Gate',
            'provider' => 'zkteco',
            'ip' => '192.168.1.102',
        ]);

        DeviceUserMap::create([
            'device_id' => $device->id,
            'device_user_id' => '99',
            'employee_id' => 'EMP-007',
            'enrolled_at' => now(),
        ]);

        $punch = new AttendancePunch(
            deviceUserId: '99',
            punchedAt: Carbon::create(2026, 9, 17, 10, 0, 0),
            verifyMode: VerifyMode::CARD,
            deviceId: (string) $device->id
        );

        $normalizer = new AttendanceNormalizer();
        $log = $normalizer->record($punch, $device);

        $this->assertEquals('EMP-007', $log->employee_id);
    }
}
