<?php

namespace ImranDevBd\AttendanceHub\Tests\Unit;

use ImranDevBd\AttendanceHub\DTOs\DeviceConnection;
use ImranDevBd\AttendanceHub\Drivers\AdmsPushDriver;
use ImranDevBd\AttendanceHub\Drivers\DahuaDriver;
use ImranDevBd\AttendanceHub\Drivers\HikvisionDriver;
use ImranDevBd\AttendanceHub\Drivers\SupremaDriver;
use ImranDevBd\AttendanceHub\Drivers\VirtualDriver;
use ImranDevBd\AttendanceHub\Drivers\ZKTecoDriver;
use ImranDevBd\AttendanceHub\Tests\TestCase;

class DriversTest extends TestCase
{
    public function test_driver_resolution_and_types(): void
    {
        $manager = app('attendance-hub');

        $zk = $manager->provider('zkteco');
        $this->assertInstanceOf(ZKTecoDriver::class, $zk);
        $this->assertFalse($zk->supportsPush());
        $this->assertTrue($zk->supportsFaceEnrollment());

        $adms = $manager->provider('adms');
        $this->assertInstanceOf(AdmsPushDriver::class, $adms);
        $this->assertTrue($adms->supportsPush());

        $hik = $manager->provider('hikvision');
        $this->assertInstanceOf(HikvisionDriver::class, $hik);
        $this->assertTrue($hik->supportsPush());

        $suprema = $manager->provider('suprema');
        $this->assertInstanceOf(SupremaDriver::class, $suprema);

        $dahua = $manager->provider('dahua');
        $this->assertInstanceOf(DahuaDriver::class, $dahua);

        $virtual = $manager->provider('virtual');
        $this->assertInstanceOf(VirtualDriver::class, $virtual);
        $this->assertTrue($virtual->ping());
    }

    public function test_device_connection_dto_serialization(): void
    {
        $conn = new DeviceConnection(
            ip: '192.168.1.50',
            port: 4370,
            protocol: 'tcp',
            username: 'admin',
            password: 'secretpassword',
            serialNumber: 'SN12345'
        );

        $arr = $conn->toArray();
        $this->assertEquals('192.168.1.50', $arr['ip']);
        $this->assertEquals(4370, $arr['port']);
        $this->assertEquals('SN12345', $arr['serial']);

        $restored = DeviceConnection::fromArray($arr);
        $this->assertEquals($conn->ip, $restored->ip);
        $this->assertEquals($conn->port, $restored->port);
        $this->assertEquals($conn->serialNumber, $restored->serialNumber);
    }
}
