<?php

namespace ImranDevBd\AttendanceHub\Drivers;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use ImranDevBd\AttendanceHub\DTOs\AttendancePunch;
use ImranDevBd\AttendanceHub\DTOs\DeviceConnection;
use ImranDevBd\AttendanceHub\DTOs\DeviceInfo;
use ImranDevBd\AttendanceHub\DTOs\DeviceUser;
use ImranDevBd\AttendanceHub\Enums\PunchType;
use ImranDevBd\AttendanceHub\Enums\VerifyMode;

class SupremaDriver extends AbstractDeviceDriver
{
    protected string $baseUrl = '';
    protected ?string $sessionId = null;

    public function connect(DeviceConnection $connection): bool
    {
        $this->connection = $connection;
        $protocol = $connection->protocol ?: 'https';
        $port = $connection->port ?: 443;
        $this->baseUrl = "{$protocol}://{$connection->ip}:{$port}";

        // Authenticate with BioStar 2 REST API
        try {
            $response = Http::timeout($connection->timeout ?: 5)
                ->withoutVerifying()
                ->post("{$this->baseUrl}/api/login", [
                    'User' => [
                        'login_id' => $connection->username,
                        'password' => $connection->password,
                    ],
                ]);

            if ($response->successful()) {
                $this->sessionId = $response->header('bs-session-id') ?? $response->json()['bs-session-id'] ?? null;
                $this->connected = !empty($this->sessionId);
                return $this->connected;
            }
        } catch (\Throwable) {
            $this->connected = false;
        }

        return false;
    }

    public function disconnect(): void
    {
        if ($this->connected && $this->sessionId) {
            try {
                $this->client()->post("{$this->baseUrl}/api/logout");
            } catch (\Throwable) {
                // Ignore
            }
        }

        $this->sessionId = null;
        $this->connected = false;
    }

    public function ping(): bool
    {
        if (!$this->connected || !$this->sessionId) {
            return false;
        }

        try {
            $res = $this->client()->get("{$this->baseUrl}/api/devices");
            return $res->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    public function getDeviceInfo(): DeviceInfo
    {
        return new DeviceInfo(
            serialNumber: $this->connection?->serialNumber,
            deviceName: 'Suprema BioStar 2 Terminal',
            platform: 'Suprema BioStar 2'
        );
    }

    public function pullAttendanceLogs(?CarbonInterface $since = null): Collection
    {
        $logs = collect();
        if (!$this->connected) {
            return $logs;
        }

        $payload = [
            'Query' => [
                'limit' => 100,
                'offset' => 0,
            ],
        ];

        if ($since) {
            $payload['Query']['conditions'] = [
                [
                    'column' => 'datetime',
                    'operator' => 3, // greater than or equal
                    'values' => [$since->toIso8601String()],
                ],
            ];
        }

        try {
            $response = $this->client()->post("{$this->baseUrl}/api/events/search", $payload);
            if ($response->successful()) {
                $events = $response->json()['EventCollection']['rows'] ?? [];

                foreach ($events as $row) {
                    $userId = $row['user_id']['user_id'] ?? null;
                    if (!$userId) {
                        continue;
                    }

                    $punchedAt = Carbon::parse($row['datetime']);
                    $eventCode = $row['event_type_id']['code'] ?? 0;

                    // Suprema event type codes
                    $verifyMode = match ($eventCode) {
                        4096 => VerifyMode::CARD,
                        4097 => VerifyMode::FINGERPRINT,
                        4098 => VerifyMode::FACE,
                        4099 => VerifyMode::PIN,
                        default => VerifyMode::OTHER,
                    };

                    $logs->push(new AttendancePunch(
                        deviceUserId: (string) $userId,
                        punchedAt: $punchedAt,
                        verifyMode: $verifyMode,
                        punchType: PunchType::AUTO,
                        deviceId: $this->connection?->ip,
                        rawPayload: $row
                    ));
                }
            }
        } catch (\Throwable) {
            // Ignore
        }

        return $logs;
    }

    public function pullUsers(): Collection
    {
        $users = collect();
        if (!$this->connected) {
            return $users;
        }

        try {
            $response = $this->client()->get("{$this->baseUrl}/api/users");
            if ($response->successful()) {
                $rows = $response->json()['UserCollection']['rows'] ?? [];
                foreach ($rows as $row) {
                    $users->push(new DeviceUser(
                        uid: (string) $row['user_id'],
                        userId: (string) $row['user_id'],
                        name: $row['name'] ?? 'User',
                        enabled: !empty($row['status']),
                        rawPayload: $row
                    ));
                }
            }
        } catch (\Throwable) {
            // Ignore
        }

        return $users;
    }

    public function pushUser(DeviceUser $user): bool
    {
        if (!$this->connected) {
            return false;
        }

        try {
            $response = $this->client()->post("{$this->baseUrl}/api/users", [
                'User' => [
                    'user_id' => $user->userId,
                    'name' => $user->name,
                    'password' => $user->password,
                ],
            ]);

            return $response->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    public function deleteUser(string $userId): bool
    {
        if (!$this->connected) {
            return false;
        }

        try {
            $response = $this->client()->delete("{$this->baseUrl}/api/users/{$userId}");
            return $response->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    public function supportsPush(): bool
    {
        return false;
    }

    public function supportsFaceEnrollment(): bool
    {
        return true;
    }

    protected function client(): PendingRequest
    {
        $req = Http::timeout($this->connection?->timeout ?: 5)->withoutVerifying();

        if ($this->sessionId) {
            $req = $req->withHeaders([
                'bs-session-id' => $this->sessionId,
            ]);
        }

        return $req;
    }
}
