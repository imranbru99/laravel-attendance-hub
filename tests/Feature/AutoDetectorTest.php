<?php

namespace ImranDevBd\AttendanceHub\Tests\Feature;

use Illuminate\Support\Facades\Http;
use ImranDevBd\AttendanceHub\Support\DeviceAutoDetector;
use ImranDevBd\AttendanceHub\Support\NetworkScanner;
use ImranDevBd\AttendanceHub\Tests\TestCase;

class AutoDetectorTest extends TestCase
{
    public function test_detect_hikvision_from_http_response(): void
    {
        Http::fake([
            '*/ISAPI/System/deviceInfo*' => Http::response(
                '<?xml version="1.0" encoding="UTF-8"?><DeviceInfo><model>DS-K1T671MF</model><serialNumber>DS-K1T6712026</serialNumber></DeviceInfo>',
                200,
                ['Content-Type' => 'application/xml']
            ),
        ]);

        $detector = new DeviceAutoDetector();
        $result = $detector->detect('192.168.1.150', 1);

        $this->assertNotNull($result);
        $this->assertEquals('hikvision', $result['provider']);
        $this->assertEquals('DS-K1T671MF', $result['model']);
    }

    public function test_network_scanner_expand_cidr(): void
    {
        $scanner = new NetworkScanner();
        $ips = $scanner->expandCidr('192.168.1.0/30');

        $this->assertCount(2, $ips);
        $this->assertContains('192.168.1.1', $ips);
        $this->assertContains('192.168.1.2', $ips);
    }
}
