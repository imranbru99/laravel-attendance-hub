<?php

namespace ImranDevBd\AttendanceHub\Tests\Unit;

use ImranDevBd\AttendanceHub\Drivers\AdmsPushDriver;
use ImranDevBd\AttendanceHub\Drivers\HikvisionDriver;
use ImranDevBd\AttendanceHub\Drivers\SupremaDriver;
use ImranDevBd\AttendanceHub\Drivers\WebhookBridgeDriver;
use ImranDevBd\AttendanceHub\Drivers\ZKTecoDriver;
use ImranDevBd\AttendanceHub\Facades\AttendanceHub;
use ImranDevBd\AttendanceHub\Support\DeviceCatalog;
use ImranDevBd\AttendanceHub\Tests\TestCase;

class DeviceCatalogTest extends TestCase
{
    public function test_catalog_contains_all_10_providers(): void
    {
        $providers = DeviceCatalog::providers();
        $this->assertCount(10, $providers);
        $this->assertArrayHasKey('zkteco', $providers);
        $this->assertArrayHasKey('hikvision', $providers);
        $this->assertArrayHasKey('anviz', $providers);
        $this->assertArrayHasKey('fingertec', $providers);
        $this->assertArrayHasKey('essl', $providers);
        $this->assertArrayHasKey('virdi', $providers);
        $this->assertArrayHasKey('suprema', $providers);
        $this->assertArrayHasKey('deli', $providers);
        $this->assertArrayHasKey('granding', $providers);
        $this->assertArrayHasKey('soyal', $providers);
    }

    public function test_catalog_contains_more_than_150_models(): void
    {
        $all = DeviceCatalog::all();
        $this->assertGreaterThanOrEqual(150, count($all));

        $summary = DeviceCatalog::summary();
        $this->assertEquals(20, $summary['ZKTeco']);
        $this->assertEquals(15, $summary['Hikvision']);
        $this->assertEquals(21, $summary['Anviz']);
        $this->assertEquals(18, $summary['FingerTec']);
        $this->assertEquals(22, $summary['eSSL']);
        $this->assertEquals(12, $summary['VIRDI']);
        $this->assertEquals(18, $summary['Suprema']);
        $this->assertEquals(10, $summary['Deli']);
        $this->assertEquals(15, $summary['Granding']);
        $this->assertEquals(15, $summary['Soyal']);
    }

    public function test_can_find_models_case_insensitively(): void
    {
        $ua860 = DeviceCatalog::find('ua860');
        $this->assertNotNull($ua860);
        $this->assertEquals('ZKTeco', $ua860['provider']);
        $this->assertEquals('UA860', $ua860['model']);
        $this->assertEquals('zkteco', $ua860['driver']);

        $speedFace = DeviceCatalog::find('SpeedFace-V5L');
        $this->assertNotNull($speedFace);
        $this->assertTrue($speedFace['face']);
        $this->assertTrue($speedFace['palm']);

        $hik = DeviceCatalog::find('DS-K1T341AMF');
        $this->assertNotNull($hik);
        $this->assertEquals('Hikvision', $hik['provider']);
        $this->assertEquals('hikvision', $hik['driver']);

        $anviz = DeviceCatalog::find('c2 pro');
        $this->assertNotNull($anviz);
        $this->assertEquals('Anviz', $anviz['provider']);

        $ta500 = DeviceCatalog::find('TA500');
        $this->assertNotNull($ta500);
        $this->assertEquals('FingerTec', $ta500['provider']);

        $essl = DeviceCatalog::find('x990');
        $this->assertNotNull($essl);
        $this->assertEquals('eSSL', $essl['provider']);

        $virdi = DeviceCatalog::find('ac-5000');
        $this->assertNotNull($virdi);
        $this->assertEquals('VIRDI', $virdi['provider']);

        $suprema = DeviceCatalog::find('biostation 2');
        $this->assertNotNull($suprema);
        $this->assertEquals('Suprema', $suprema['provider']);

        $soyal = DeviceCatalog::find('ar-721h');
        $this->assertNotNull($soyal);
        $this->assertEquals('Soyal', $soyal['provider']);
    }

    public function test_driver_resolution_for_all_10_providers(): void
    {
        $this->assertEquals('zkteco', DeviceCatalog::resolveDriver('UA860'));
        $this->assertEquals('zkteco', DeviceCatalog::resolveDriver('TA500'));
        $this->assertEquals('zkteco', DeviceCatalog::resolveDriver('GT100'));
        $this->assertEquals('adms', DeviceCatalog::resolveDriver('X990'));
        $this->assertEquals('adms', DeviceCatalog::resolveDriver('C2 Pro'));
        $this->assertEquals('hikvision', DeviceCatalog::resolveDriver('DS-K1T341AMF'));
        $this->assertEquals('suprema', DeviceCatalog::resolveDriver('BioStation 2'));
        $this->assertEquals('webhook_bridge', DeviceCatalog::resolveDriver('AC-5000'));
        $this->assertEquals('webhook_bridge', DeviceCatalog::resolveDriver('AR-721H'));
    }

    public function test_attendance_hub_resolves_model_driver_directly(): void
    {
        $zkDriver = AttendanceHub::model('UA860');
        $this->assertInstanceOf(ZKTecoDriver::class, $zkDriver);

        $hikDriver = AttendanceHub::model('DS-K1A8503');
        $this->assertInstanceOf(HikvisionDriver::class, $hikDriver);

        $supremaDriver = AttendanceHub::model('BioStation 2');
        $this->assertInstanceOf(SupremaDriver::class, $supremaDriver);

        $bridgeDriver = AttendanceHub::model('AR-721H');
        $this->assertInstanceOf(WebhookBridgeDriver::class, $bridgeDriver);
    }

    public function test_can_register_custom_unlisted_model_dynamically(): void
    {
        DeviceCatalog::register('XYZ-PRO-900', [
            'provider' => 'MyLocalBrand',
            'series' => 'XYZ Series',
            'driver' => 'zkteco',
            'port' => 4370,
            'fp' => true,
            'face' => true,
            'notes' => 'Custom OEM hardware not in standard list',
        ]);

        $custom = DeviceCatalog::find('XYZ-PRO-900');
        $this->assertNotNull($custom);
        $this->assertEquals('MyLocalBrand', $custom['provider']);
        $this->assertEquals('zkteco', $custom['driver']);
        $this->assertTrue($custom['face']);

        $driver = AttendanceHub::model('XYZ-PRO-900');
        $this->assertInstanceOf(ZKTecoDriver::class, $driver);
    }

    public function test_can_register_custom_driver_via_extend_and_resolve_via_model(): void
    {
        // Custom driver class for an unlisted proprietary device
        AttendanceHub::extend('my_proprietary_protocol', function () {
            return new class extends \ImranDevBd\AttendanceHub\Drivers\AbstractDeviceDriver {
                public function ping(): bool
                {
                    return true;
                }
            };
        });

        // Register custom model that points to this custom driver
        DeviceCatalog::register('PROPRIETARY-GATE-1', [
            'provider' => 'InventedBrand',
            'driver' => 'my_proprietary_protocol',
            'port' => 9999,
        ]);

        $resolvedDriver = AttendanceHub::model('PROPRIETARY-GATE-1');
        $this->assertTrue($resolvedDriver->ping());
    }

    public function test_can_load_custom_models_from_config(): void
    {
        DeviceCatalog::reset();

        config()->set('attendance-hub.custom_models', [
            'CONFIG-DEV-55' => [
                'provider' => 'ConfigBrand',
                'driver' => 'hikvision',
                'port' => 8080,
            ],
        ]);

        $entry = DeviceCatalog::find('CONFIG-DEV-55');
        $this->assertNotNull($entry);
        $this->assertEquals('ConfigBrand', $entry['provider']);
        $this->assertEquals('hikvision', $entry['driver']);
    }
}
