<?php

namespace ImranDevBd\AttendanceHub\Tests\Feature;

use Illuminate\Validation\ValidationException;
use ImranDevBd\AttendanceHub\Drivers\VirtualDriver;
use ImranDevBd\AttendanceHub\Enums\VerifyMode;
use ImranDevBd\AttendanceHub\Tests\TestCase;

class VirtualDriverTest extends TestCase
{
    public function test_haversine_distance_calculation(): void
    {
        $driver = new VirtualDriver();

        // Distance between two points in Dhaka, Bangladesh (~1.1 km)
        $distance = $driver->calculateDistanceMeters(23.7925, 90.4078, 23.8010, 90.4130);

        $this->assertGreaterThan(900, $distance);
        $this->assertLessThan(1500, $distance);
    }

    public function test_valid_virtual_checkin_within_geofence(): void
    {
        $driver = new VirtualDriver();

        $log = $driver->recordCheckIn([
            'employee_id' => 'EMP-101',
            'lat' => 23.7925,
            'lng' => 90.4078,
            'target_lat' => 23.7926, // very close (~15m away)
            'target_lng' => 90.4079,
            'radius_meters' => 50,
            'method' => 'gps',
        ]);

        $this->assertEquals('EMP-101', $log->employee_id);
        $this->assertEquals(VerifyMode::GPS->value, $log->verify_mode);
        $this->assertNotNull($log->location);
        $this->assertLessThan(50, $log->location['distance_meters']);
    }

    public function test_checkin_outside_geofence_throws_validation_exception(): void
    {
        $this->expectException(ValidationException::class);

        $driver = new VirtualDriver();

        $driver->recordCheckIn([
            'employee_id' => 'EMP-102',
            'lat' => 23.7000,
            'lng' => 90.3000,
            'target_lat' => 23.8000, // kilometers away
            'target_lng' => 90.4000,
            'radius_meters' => 50,
            'method' => 'gps',
        ]);
    }

    public function test_virtual_checkin_api_endpoint(): void
    {
        $response = $this->postJson('/api/attendance/virtual/check-in', [
            'employee_id' => 'EMP-205',
            'lat' => 23.7925,
            'lng' => 90.4078,
            'method' => 'gps',
            'punch_type' => 'check_in',
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'data' => [
                'employee_id' => 'EMP-205',
                'verify_mode' => 'gps',
            ],
        ]);
    }
}
