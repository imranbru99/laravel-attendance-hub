<?php

namespace ImranDevBd\AttendanceHub\Drivers;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use ImranDevBd\AttendanceHub\DTOs\AttendancePunch;
use ImranDevBd\AttendanceHub\DTOs\DeviceConnection;
use ImranDevBd\AttendanceHub\DTOs\DeviceInfo;
use ImranDevBd\AttendanceHub\DTOs\DeviceUser;
use ImranDevBd\AttendanceHub\Enums\PunchType;
use ImranDevBd\AttendanceHub\Enums\VerifyMode;
use ImranDevBd\AttendanceHub\Exceptions\DeviceConnectionException;
use PHPUnit\Framework\Assert as PHPUnit;

class FakeDeviceDriver extends AbstractDeviceDriver
{
    protected array $fakeLogs = [];
    protected array $fakeUsers = [];
    protected bool $shouldFailConnection = false;
    protected bool $shouldFailSync = false;
    protected bool $logsCleared = false;
    protected bool $hasConnected = false;
    protected int $connectCount = 0;
    protected array $pushedUsers = [];
    protected array $deletedUsers = [];

    public function __construct(array $initialPunches = [])
    {
        $this->fakeLogs = $initialPunches;
    }

    public function connect(DeviceConnection $connection): bool
    {
        if ($this->shouldFailConnection) {
            $this->connected = false;
            throw DeviceConnectionException::unreachable($connection->ip ?? '127.0.0.1', $connection->port, 'Simulated connection failure');
        }

        $this->connection = $connection;
        $this->connected = true;
        $this->hasConnected = true;
        $this->connectCount++;

        return true;
    }

    public function ping(): bool
    {
        return !$this->shouldFailConnection;
    }

    public function getDeviceInfo(): DeviceInfo
    {
        return new DeviceInfo(
            serialNumber: $this->connection?->serialNumber ?? 'FAKE-TERMINAL-001',
            deviceName: 'Simulated Hardware Terminal',
            firmwareVersion: 'v9.9.9-FAKE',
            platform: 'Mock Hardware Emulator',
            userCount: count($this->fakeUsers),
            logCount: count($this->fakeLogs)
        );
    }

    public function pullAttendanceLogs(?CarbonInterface $since = null): Collection
    {
        if ($this->shouldFailSync) {
            throw new \RuntimeException('Simulated device sync failure.');
        }

        if (empty($this->fakeLogs)) {
            // Generate realistic fallback punches for demo/testing
            $this->fakeLogs = [
                new AttendancePunch(
                    deviceUserId: '101',
                    punchedAt: now()->subHours(8),
                    verifyMode: VerifyMode::FINGERPRINT,
                    punchType: PunchType::CHECK_IN,
                    deviceId: $this->connection?->ip ?? 'fake_device'
                ),
                new AttendancePunch(
                    deviceUserId: '101',
                    punchedAt: now()->subMinutes(10),
                    verifyMode: VerifyMode::FINGERPRINT,
                    punchType: PunchType::CHECK_OUT,
                    deviceId: $this->connection?->ip ?? 'fake_device'
                ),
                new AttendancePunch(
                    deviceUserId: '102',
                    punchedAt: now()->subHours(7),
                    verifyMode: VerifyMode::FACE,
                    punchType: PunchType::CHECK_IN,
                    deviceId: $this->connection?->ip ?? 'fake_device'
                ),
            ];
        }

        return collect($this->fakeLogs)->filter(function (AttendancePunch $punch) use ($since) {
            if ($since === null) {
                return true;
            }
            return $punch->punchedAt->greaterThanOrEqualTo($since);
        })->values();
    }

    public function pullUsers(): Collection
    {
        if (empty($this->fakeUsers)) {
            $this->fakeUsers = [
                new DeviceUser(uid: '101', userId: '101', name: 'Alice Smith', role: 0),
                new DeviceUser(uid: '102', userId: '102', name: 'Bob Jones', role: 0),
                new DeviceUser(uid: '999', userId: '999', name: 'Admin User', role: 14),
            ];
        }

        return collect($this->fakeUsers);
    }

    public function pushUser(DeviceUser $user): bool
    {
        $this->pushedUsers[$user->userId] = $user;
        return true;
    }

    public function deleteUser(string $userId): bool
    {
        $this->deletedUsers[] = $userId;
        return true;
    }

    public function clearLogs(): bool
    {
        $this->logsCleared = true;
        $this->fakeLogs = [];
        return true;
    }

    public function liveCapture(callable $onPunch): void
    {
        foreach ($this->pullAttendanceLogs() as $punch) {
            $onPunch($punch);
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

    // --- Testing & Simulation Helpers ---

    public function setPunches(array $punches): self
    {
        $this->fakeLogs = $punches;
        return $this;
    }

    public function addPunch(AttendancePunch $punch): self
    {
        $this->fakeLogs[] = $punch;
        return $this;
    }

    public function shouldFailConnection(bool $fail = true): self
    {
        $this->shouldFailConnection = $fail;
        return $this;
    }

    public function shouldFailSync(bool $fail = true): self
    {
        $this->shouldFailSync = $fail;
        return $this;
    }

    public function assertConnected(): void
    {
        PHPUnit::assertTrue($this->hasConnected, 'Expected fake device to have been connected.');
    }

    public function assertCurrentlyConnected(): void
    {
        PHPUnit::assertTrue($this->connected, 'Expected fake device to currently be connected.');
    }

    public function assertUserPushed(string $userId): void
    {
        PHPUnit::assertArrayHasKey($userId, $this->pushedUsers, "Expected user [{$userId}] to have been pushed to fake terminal.");
    }

    public function assertLogsCleared(): void
    {
        PHPUnit::assertTrue($this->logsCleared, 'Expected attendance logs to have been cleared on fake device.');
    }
}
