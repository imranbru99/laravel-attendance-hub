<?php

namespace ImranDevBd\AttendanceHub\Drivers;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use ImranDevBd\AttendanceHub\DTOs\AttendancePunch;
use ImranDevBd\AttendanceHub\DTOs\DeviceInfo;
use ImranDevBd\AttendanceHub\Enums\PunchType;
use ImranDevBd\AttendanceHub\Enums\VerifyMode;
use ImranDevBd\AttendanceHub\Models\AttendanceLog;
use ImranDevBd\AttendanceHub\Support\AttendanceNormalizer;

class VirtualDriver extends AbstractDeviceDriver
{
    public function getDeviceInfo(): DeviceInfo
    {
        return new DeviceInfo(
            serialNumber: 'VIRTUAL-HUB-01',
            deviceName: 'Virtual Mobile / Kiosk Channel',
            platform: 'Cloud Virtual Driver'
        );
    }

    public function ping(): bool
    {
        return true;
    }

    /**
     * Record a virtual check-in (GPS, QR, or Selfie).
     *
     * @param array{
     *     employee_id: string|int,
     *     lat?: float|null,
     *     lng?: float|null,
     *     target_lat?: float|null,
     *     target_lng?: float|null,
     *     radius_meters?: int|null,
     *     qr_token?: string|null,
     *     method?: string|null,
     *     punch_type?: string|null,
     *     photo_url?: string|null,
     *     metadata?: array|null
     * } $data
     * @return AttendanceLog
     * @throws ValidationException
     */
    public function recordCheckIn(array $data): AttendanceLog
    {
        $employeeId = (string) ($data['employee_id'] ?? $data['user_id'] ?? '');
        if (empty($employeeId)) {
            throw ValidationException::withMessages(['employee_id' => 'Employee ID is required.']);
        }

        $method = strtolower($data['method'] ?? 'gps');
        $verifyMode = match ($method) {
            'qr' => VerifyMode::QR,
            'selfie', 'face' => VerifyMode::FACE,
            default => VerifyMode::GPS,
        };

        // Geofence validation
        $location = null;
        if (isset($data['lat'], $data['lng'])) {
            $lat = (float) $data['lat'];
            $lng = (float) $data['lng'];
            $location = ['lat' => $lat, 'lng' => $lng];

            // If target geofence is provided, validate distance
            if (isset($data['target_lat'], $data['target_lng'])) {
                $targetLat = (float) $data['target_lat'];
                $targetLng = (float) $data['target_lng'];
                $radius = (int) ($data['radius_meters'] ?? config('attendance-hub.virtual.default_radius_meters', 100));

                $distance = $this->calculateDistanceMeters($lat, $lng, $targetLat, $targetLng);
                $location['distance_meters'] = round($distance, 2);
                $location['allowed_radius'] = $radius;

                if ($distance > $radius) {
                    throw ValidationException::withMessages([
                        'location' => "Outside allowed geofence boundary ({$distance}m > {$radius}m).",
                    ]);
                }
            }
        }

        // Validate QR Token if method is QR
        if ($method === 'qr' && !empty($data['qr_token'])) {
            $this->validateQrToken($data['qr_token']);
        }

        $punchType = isset($data['punch_type'])
            ? PunchType::tryFrom($data['punch_type']) ?? PunchType::AUTO
            : PunchType::AUTO;

        $punch = new AttendancePunch(
            deviceUserId: $employeeId,
            punchedAt: now(),
            verifyMode: $verifyMode,
            punchType: $punchType,
            deviceId: 'virtual',
            employeeId: $employeeId,
            rawPayload: [
                'virtual_method' => $method,
                'photo_url' => $data['photo_url'] ?? null,
                'metadata' => $data['metadata'] ?? [],
            ],
            location: $location
        );

        $normalizer = new AttendanceNormalizer();
        return $normalizer->record($punch);
    }

    /**
     * Calculate geodesic distance between two points in meters using Haversine formula.
     */
    public function calculateDistanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000; // in meters

        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lngDelta / 2) * sin($lngDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Validate rotating time-bound QR code token.
     */
    protected function validateQrToken(string $token): void
    {
        // Decode base64 or timestamp payload (e.g. timestamp:signature)
        $parts = explode(':', base64_decode($token) ?: $token);
        if (count($parts) >= 2) {
            $timestamp = (int) $parts[0];
            $validitySeconds = config('attendance-hub.virtual.qr_validity_seconds', 60);

            if (abs(time() - $timestamp) > $validitySeconds) {
                throw ValidationException::withMessages([
                    'qr_token' => 'QR code token has expired. Please scan the current code.',
                ]);
            }
        }
    }
}
