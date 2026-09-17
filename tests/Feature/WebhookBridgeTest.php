<?php

namespace ImranDevBd\AttendanceHub\Tests\Feature;

use ImranDevBd\AttendanceHub\Exceptions\DeviceAuthenticationException;
use ImranDevBd\AttendanceHub\Models\AttendanceLog;
use ImranDevBd\AttendanceHub\Tests\TestCase;

class WebhookBridgeTest extends TestCase
{
    public function test_webhook_bridge_ingests_wiegand_punch(): void
    {
        $response = $this->postJson('/api/attendance/webhook-bridge', [
            'card_number' => '987654321',
            'reader_id' => 'DOOR_READER_01',
            'facility_code' => 'FAC-99',
            'timestamp' => '2026-09-17 08:45:00',
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('attendance_logs', [
            'device_user_id' => '987654321',
            'verify_mode' => 'card',
        ]);
    }

    public function test_webhook_bridge_rejects_invalid_token(): void
    {
        $this->withoutExceptionHandling();
        $this->expectException(DeviceAuthenticationException::class);

        config(['attendance-hub.bridge.token' => 'secret-bridge-token-xyz']);

        $this->postJson('/api/attendance/webhook-bridge', [
            'card_number' => '12345',
            'token' => 'WRONG_TOKEN',
        ]);
    }
}
