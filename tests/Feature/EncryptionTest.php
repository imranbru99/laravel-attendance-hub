<?php

namespace ImranDevBd\AttendanceHub\Tests\Feature;

use Illuminate\Support\Facades\DB;
use ImranDevBd\AttendanceHub\Models\AttendanceDevice;
use ImranDevBd\AttendanceHub\Models\DeviceBiometricTemplate;
use ImranDevBd\AttendanceHub\Tests\TestCase;

class EncryptionTest extends TestCase
{
    public function test_device_connection_settings_are_encrypted_at_rest(): void
    {
        $device = AttendanceDevice::create([
            'serial_number' => 'DEV_ENC_001',
            'name' => 'HQ Secure Terminal',
            'provider' => 'zkteco',
            'connection_settings' => [
                'ip' => '192.168.1.201',
                'port' => 4370,
                'comm_key' => 'SuperSecretKey123',
                'password' => 'VaultPass#409!',
            ],
            'status' => 'online',
        ]);

        // When retrieved through Eloquent, it should automatically decrypt
        $retrieved = AttendanceDevice::find($device->id);
        $this->assertEquals('SuperSecretKey123', $retrieved->connection_settings['comm_key']);
        $this->assertEquals('VaultPass#409!', $retrieved->connection_settings['password']);

        // In raw database storage, the value must be encrypted (not plaintext JSON containing the password)
        $raw = DB::table('attendance_devices')->where('id', $device->id)->first();
        $this->assertIsString($raw->connection_settings);
        $this->assertStringNotContainsString('SuperSecretKey123', $raw->connection_settings);
        $this->assertStringNotContainsString('VaultPass#409!', $raw->connection_settings);
    }

    public function test_biometric_templates_are_encrypted_at_rest(): void
    {
        $device = AttendanceDevice::create([
            'serial_number' => 'DEV_ENC_002',
            'name' => 'Biometric Vault Terminal',
            'provider' => 'zkteco',
            'connection_settings' => ['ip' => '192.168.1.202', 'port' => 4370],
            'status' => 'online',
        ]);

        $rawBioTemplateString = 'RAW_BIOMETRIC_VECTOR_BLOB_BASE64_ABC123456789_SECRET';

        $template = DeviceBiometricTemplate::create([
            'device_id' => $device->id,
            'device_user_id' => 'USR_1001',
            'template_type' => 'fingerprint',
            'finger_index' => 1,
            'template_data' => $rawBioTemplateString,
            'version' => '10.0',
        ]);

        // When retrieved through Eloquent, it decrypts seamlessly
        $retrieved = DeviceBiometricTemplate::find($template->id);
        $this->assertEquals($rawBioTemplateString, $retrieved->template_data);

        // In raw database storage, sensitive biometric vectors must never be readable as plaintext
        $raw = DB::table('device_biometric_templates')->where('id', $template->id)->first();
        $this->assertIsString($raw->template_data);
        $this->assertStringNotContainsString('RAW_BIOMETRIC_VECTOR_BLOB', $raw->template_data);
        $this->assertStringNotContainsString('SECRET', $raw->template_data);
    }
}
