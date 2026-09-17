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

class DahuaDriver extends AbstractDeviceDriver
{
    protected string $baseUrl = '';

    public function connect(DeviceConnection $connection): bool
    {
        $this->connection = $connection;
        $port = $connection->port ?: 80;
        $this->baseUrl = "http://{$connection->ip}:{$port}";

        $this->connected = $this->ping();
        return $this->connected;
    }

    public function ping(): bool
    {
        try {
            $res = $this->client()->get("{$this->baseUrl}/cgi-bin/magicBox.cgi?action=getSystemInfo");
            return $res->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    public function getDeviceInfo(): DeviceInfo
    {
        try {
            $res = $this->client()->get("{$this->baseUrl}/cgi-bin/magicBox.cgi?action=getSystemInfo");
            $body = $res->body();

            $serial = $this->extractKeyValue($body, 'serialNumber') ?? $this->connection?->serialNumber;
            $type = $this->extractKeyValue($body, 'appType') ?? 'Dahua Access Terminal';

            return new DeviceInfo(
                serialNumber: $serial,
                deviceName: $type,
                platform: 'Dahua NetSDK / CGI'
            );
        } catch (\Throwable) {
            return new DeviceInfo(
                serialNumber: $this->connection?->serialNumber,
                deviceName: 'Dahua Access Device'
            );
        }
    }

    public function pullAttendanceLogs(?CarbonInterface $since = null): Collection
    {
        $logs = collect();
        if (!$this->connected) {
            return $logs;
        }

        $startTime = $since ? $since->toDateTimeString() : now()->subDays(30)->toDateTimeString();
        $endTime = now()->toDateTimeString();

        $url = "{$this->baseUrl}/cgi-bin/recordFinder.cgi?action=find&name=AccessControlCardRec&StartTime=" . urlencode($startTime) . "&EndTime=" . urlencode($endTime);

        try {
            $res = $this->client()->get($url);
            if ($res->successful()) {
                $lines = explode("\n", $res->body());
                $currentRecord = [];

                foreach ($lines as $line) {
                    $line = trim($line);
                    if (empty($line)) {
                        continue;
                    }

                    if (preg_match('/records\[\d+\]\.(.*?)=(.*)/', $line, $matches)) {
                        $key = $matches[1];
                        $val = $matches[2];

                        if ($key === 'CardNo' && !empty($currentRecord)) {
                            // Yield accumulated record
                            $punch = $this->createPunchFromDahuaRecord($currentRecord);
                            if ($punch) {
                                $logs->push($punch);
                            }
                            $currentRecord = [];
                        }

                        $currentRecord[$key] = $val;
                    }
                }

                if (!empty($currentRecord)) {
                    $punch = $this->createPunchFromDahuaRecord($currentRecord);
                    if ($punch) {
                        $logs->push($punch);
                    }
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
            $res = $this->client()->get("{$this->baseUrl}/cgi-bin/AccessUser.cgi?action=list");
            if ($res->successful()) {
                // Parse user records from response body
                $lines = explode("\n", $res->body());
                $userMap = [];

                foreach ($lines as $line) {
                    if (preg_match('/users\[(\d+)\]\.(.*?)=(.*)/', $line, $m)) {
                        $idx = $m[1];
                        $key = $m[2];
                        $val = $m[3];
                        $userMap[$idx][$key] = $val;
                    }
                }

                foreach ($userMap as $u) {
                    $userId = $u['UserID'] ?? $u['CardNo'] ?? null;
                    if ($userId) {
                        $users->push(new DeviceUser(
                            uid: (string) $userId,
                            userId: (string) $userId,
                            name: $u['UserName'] ?? "User {$userId}",
                            card: $u['CardNo'] ?? null,
                            rawPayload: $u
                        ));
                    }
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

        $params = http_build_query([
            'action' => 'insert',
            'UserID' => $user->userId,
            'UserName' => $user->name,
            'CardNo' => $user->card ?? '',
        ]);

        try {
            $res = $this->client()->get("{$this->baseUrl}/cgi-bin/AccessUser.cgi?{$params}");
            return $res->successful() && str_contains($res->body(), 'OK');
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
            $res = $this->client()->get("{$this->baseUrl}/cgi-bin/AccessUser.cgi?action=remove&UserID={$userId}");
            return $res->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    public function supportsFaceEnrollment(): bool
    {
        return true;
    }

    protected function createPunchFromDahuaRecord(array $rec): ?AttendancePunch
    {
        $userId = $rec['UserID'] ?? $rec['CardNo'] ?? null;
        $timeStr = $rec['CreateTime'] ?? null;

        if (!$userId || !$timeStr) {
            return null;
        }

        $method = (int) ($rec['Method'] ?? 0);
        $verifyMode = match ($method) {
            1 => VerifyMode::CARD,
            2 => VerifyMode::FINGERPRINT,
            3 => VerifyMode::PIN,
            4 => VerifyMode::FACE,
            default => VerifyMode::OTHER,
        };

        return new AttendancePunch(
            deviceUserId: (string) $userId,
            punchedAt: Carbon::parse($timeStr),
            verifyMode: $verifyMode,
            punchType: PunchType::AUTO,
            deviceId: $this->connection?->ip,
            rawPayload: $rec
        );
    }

    protected function client(): PendingRequest
    {
        $req = Http::timeout($this->connection?->timeout ?: 5)->withoutVerifying();

        if ($this->connection?->username && $this->connection?->password) {
            $req = $req->withDigestAuth($this->connection->username, $this->connection->password);
        }

        return $req;
    }

    protected function extractKeyValue(string $text, string $key): ?string
    {
        if (preg_match("/{$key}=(.*)/i", $text, $m)) {
            return trim($m[1]);
        }
        return null;
    }
}
