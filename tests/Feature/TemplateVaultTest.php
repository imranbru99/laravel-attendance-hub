<?php

namespace ImranDevBd\AttendanceHub\Tests\Feature;

use ImranDevBd\AttendanceHub\Models\AttendanceDevice;
use ImranDevBd\AttendanceHub\Models\DeviceBiometricTemplate;
use ImranDevBd\AttendanceHub\Support\DeviceTemplateVault;
use ImranDevBd\AttendanceHub\Tests\TestCase;

class TemplateVaultTest extends TestCase
{
    public function test_template_vault_storage_and_retrieval(): void
    {
        $device = AttendanceDevice::create([
            'name' => 'ZK Terminal 1',
            'provider' => 'zkteco',
        ]);

        $template = DeviceBiometricTemplate::create([
            'device_id' => $device->id,
            'device_user_id' => '101',
            'employee_id' => 'EMP-101',
            'template_type' => 'fingerprint',
            'finger_index' => 0,
            'template_data' => base64_encode('RAW_BINARY_FINGERPRINT_TEMPLATE_DATA'),
            'version' => 'ZK10',
        ]);

        $vault = new DeviceTemplateVault();
        $templates = $vault->getTemplatesForUser('101');

        $this->assertCount(1, $templates);
        $this->assertEquals('fingerprint', $templates->first()->template_type);
        $this->assertEquals('RAW_BINARY_FINGERPRINT_TEMPLATE_DATA', base64_decode($templates->first()->template_data));
    }
}
