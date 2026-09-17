<?php

namespace ImranDevBd\AttendanceHub\Tests\Feature;

use ImranDevBd\AttendanceHub\Drivers\AdmsPushDriver;
use ImranDevBd\AttendanceHub\Models\AttendanceDevice;
use ImranDevBd\AttendanceHub\Models\AttendanceLog;
use ImranDevBd\AttendanceHub\Tests\TestCase;

class AdmsListenerTest extends TestCase
{
    public function test_adms_heartbeat_response(): void
    {
        $response = $this->get('/iclock/cdata?SN=SN-ESSL-9988');

        $response->assertStatus(200);
        $this->assertStringContainsString('GET OPTION FROM: SN-ESSL-9988', $response->getContent());
        $this->assertStringContainsString('TransFlag=TransData AttLog', $response->getContent());
    }

    public function test_adms_push_attlog_ingestion(): void
    {
        $device = AttendanceDevice::create([
            'name' => 'eSSL ADMS Device',
            'provider' => 'adms',
            'serial_number' => 'ESSL-12345',
        ]);

        // Standard ADMS ATTLOG payload format: PIN \t YYYY-MM-DD HH:MM:SS \t State \t Verify
        $payload = "101\t2026-09-17 08:30:00\t0\t1\r\n102\t2026-09-17 08:31:00\t0\t2\r\n";

        $response = $this->call(
            method: 'POST',
            uri: '/iclock/cdata?table=ATTLOG&SN=ESSL-12345',
            content: $payload
        );

        $response->assertStatus(200);
        $response->assertSeeText('OK: 2');

        $this->assertEquals(2, AttendanceLog::count());
        $this->assertDatabaseHas('attendance_logs', [
            'device_user_id' => '101',
            'verify_mode' => 'fingerprint',
            'punch_type' => 'check_in',
        ]);
        $this->assertDatabaseHas('attendance_logs', [
            'device_user_id' => '102',
            'verify_mode' => 'pin',
            'punch_type' => 'check_in',
        ]);
    }

    public function test_adms_command_dispatch(): void
    {
        AdmsPushDriver::queueCommand('ESSL-12345', 'CHECK');

        $response = $this->get('/iclock/getrequest?SN=ESSL-12345');

        $response->assertStatus(200);
        $this->assertStringContainsString('CHECK', $response->getContent());
    }
}
