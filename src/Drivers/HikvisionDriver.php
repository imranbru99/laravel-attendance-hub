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

class HikvisionDriver extends AbstractDeviceDriver
{
    protected string $baseUrl = '';

    public function connect(DeviceConnection $connection): bool
    {
        $this->connection = $connection;
        $protocol = $connection->protocol ?: 'http';
        $port = $connection->port ?: 80;
        $this->baseUrl = "{$protocol}://{$connection->ip}:{$port}";

        $this->connected = $this->ping();

        return $this->connected;
    }

    public function ping(): bool
    {
        try {
            $response = $this->client()->get("{$this->baseUrl}/ISAPI/System/status");
            if ($response->successful() || $response->status() === 401) {
                return true;
            }

            $info = $this->client()->get("{$this->baseUrl}/ISAPI/System/deviceInfo");
            return $info->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    public function getDeviceInfo(): DeviceInfo
    {
        try {
            $response = $this->client()->get("{$this->baseUrl}/ISAPI/System/deviceInfo");
            $body = $response->body();

            $model = $this->extractXmlTag($body, 'model') ?? 'Hikvision Terminal';
            $serial = $this->extractXmlTag($body, 'serialNumber') ?? $this->connection?->serialNumber;
            $firmware = $this->extractXmlTag($body, 'firmwareVersion');
            $mac = $this->extractXmlTag($body, 'macAddress');

            return new DeviceInfo(
                serialNumber: $serial,
                deviceName: $model,
                firmwareVersion: $firmware,
                macAddress: $mac,
                platform: 'Hikvision ISAPI',
                rawInfo: ['xml' => $body]
            );
        } catch (\Throwable) {
            return new DeviceInfo(
                serialNumber: $this->connection?->serialNumber,
                deviceName: 'Hikvision ISAPI Device'
            );
        }
    }

    public function pullAttendanceLogs(?CarbonInterface $since = null): Collection
    {
        $logs = collect();
        $startTime = $since ? $since->toIso8601String() : now()->subDays(30)->toIso8601String();
        $endTime = now()->toIso8601String();

        $payload = [
            'AcsEventCond' => [
                'searchID' => (string) time(),
                'searchResultPosition' => 0,
                'maxResults' => 100,
                'major' => 5, // Access control events
                'minor' => 0,
                'startTime' => $startTime,
                'endTime' => $endTime,
            ],
        ];

        try {
            $response = $this->client()
                ->post("{$this->baseUrl}/ISAPI/AccessControl/AcsEvent?format=json", $payload);

            if ($response->successful()) {
                $data = $response->json();
                $matches = $data['AcsEvent']['InfoList'] ?? [];

                foreach ($matches as $item) {
                    $employeeNo = $item['employeeNoString'] ?? $item['cardNo'] ?? null;
                    if (!$employeeNo) {
                        continue;
                    }

                    $time = Carbon::parse($item['time']);
                    $minor = (int) ($item['minor'] ?? 0);

                    // Minor event types in Hikvision ISAPI:
                    // 1=Card, 2=Fingerprint, 75=Face, 80=Fingerprint+Face
                    $verifyMode = match ($minor) {
                        2, 80 => VerifyMode::FINGERPRINT,
                        75 => VerifyMode::FACE,
                        1 => VerifyMode::CARD,
                        default => VerifyMode::OTHER,
                    };

                    $logs->push(new AttendancePunch(
                        deviceUserId: (string) $employeeNo,
                        punchedAt: $time,
                        verifyMode: $verifyMode,
                        punchType: PunchType::AUTO,
                        deviceId: $this->connection?->ip,
                        rawPayload: $item
                    ));
                }
            }
        } catch (\Throwable) {
            // Silently fall back on empty collection
        }

        return $logs;
    }

    public function pullUsers(): Collection
    {
        $users = collect();
        $payload = [
            'UserInfoSearchCond' => [
                'searchID' => (string) time(),
                'searchResultPosition' => 0,
                'maxResults' => 100,
            ],
        ];

        try {
            $response = $this->client()
                ->post("{$this->baseUrl}/ISAPI/AccessControl/UserInfo/Search?format=json", $payload);

            if ($response->successful()) {
                $items = $response->json()['UserInfoSearch']['UserInfo'] ?? [];
                foreach ($items as $item) {
                    $users->push(new DeviceUser(
                        uid: (string) ($item['employeeNo'] ?? $item['employeeNoString']),
                        userId: (string) ($item['employeeNoString'] ?? $item['employeeNo']),
                        name: $item['name'] ?? 'User',
                        card: $item['cardNo'] ?? null,
                        enabled: ($item['userType'] ?? 'normal') === 'normal',
                        rawPayload: $item
                    ));
                }
            }
        } catch (\Throwable) {
            // Return empty
        }

        return $users;
    }

    public function pushUser(DeviceUser $user): bool
    {
        $payload = [
            'UserInfo' => [
                'employeeNo' => $user->userId,
                'name' => $user->name,
                'userType' => 'normal',
                'closeDelayEnabled' => false,
                'Valid' => [
                    'enable' => true,
                    'beginTime' => '2020-01-01T00:00:00',
                    'endTime' => '2037-12-31T23:59:59',
                ],
            ],
        ];

        try {
            $response = $this->client()
                ->post("{$this->baseUrl}/ISAPI/AccessControl/UserInfo/Record?format=json", $payload);

            return $response->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    public function deleteUser(string $userId): bool
    {
        $payload = [
            'UserInfoDelCond' => [
                'EmployeeNoList' => [
                    ['employeeNo' => $userId],
                ],
            ],
        ];

        try {
            $response = $this->client()
                ->put("{$this->baseUrl}/ISAPI/AccessControl/UserInfo/Delete?format=json", $payload);

            return $response->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    public function supportsPush(): bool
    {
        return true;
    }

    public function supportsFaceEnrollment(): bool
    {
        return true;
    }

    protected function client(): PendingRequest
    {
        $req = Http::timeout($this->connection?->timeout ?: 5)->withoutVerifying();

        if ($this->connection?->username && $this->connection?->password) {
            // Hikvision ISAPI supports Digest and Basic
            $req = $req->withDigestAuth($this->connection->username, $this->connection->password);
        }

        return $req;
    }

    protected function extractXmlTag(string $xml, string $tag): ?string
    {
        if (preg_match("/<{$tag}>(.*?)<\/{$tag}>/i", $xml, $matches)) {
            return trim($matches[1]);
        }
        return null;
    }
}
