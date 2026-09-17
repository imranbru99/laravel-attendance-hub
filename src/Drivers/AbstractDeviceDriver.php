<?php

namespace ImranDevBd\AttendanceHub\Drivers;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use ImranDevBd\AttendanceHub\Contracts\DeviceDriverInterface;
use ImranDevBd\AttendanceHub\DTOs\AttendancePunch;
use ImranDevBd\AttendanceHub\DTOs\DeviceConnection;
use ImranDevBd\AttendanceHub\DTOs\DeviceInfo;
use ImranDevBd\AttendanceHub\DTOs\DeviceUser;

abstract class AbstractDeviceDriver implements DeviceDriverInterface
{
    protected ?DeviceConnection $connection = null;
    protected bool $connected = false;

    public function connect(DeviceConnection $connection): bool
    {
        $this->connection = $connection;
        $this->connected = true;

        return true;
    }

    public function disconnect(): void
    {
        $this->connected = false;
    }

    public function isConnected(): bool
    {
        return $this->connected;
    }

    public function getConnection(): ?DeviceConnection
    {
        return $this->connection;
    }

    public function ping(): bool
    {
        return $this->connected;
    }

    public function getDeviceInfo(): DeviceInfo
    {
        return new DeviceInfo(
            serialNumber: $this->connection?->serialNumber,
            deviceName: 'Generic Attendance Device'
        );
    }

    public function pullAttendanceLogs(?CarbonInterface $since = null): Collection
    {
        return collect();
    }

    public function pullUsers(): Collection
    {
        return collect();
    }

    public function pushUser(DeviceUser $user): bool
    {
        return false;
    }

    public function deleteUser(string $userId): bool
    {
        return false;
    }

    public function liveCapture(callable $onPunch): void
    {
        // Default no-op for drivers without streaming support
    }

    public function supportsPush(): bool
    {
        return false;
    }

    public function supportsFaceEnrollment(): bool
    {
        return false;
    }

    public function clearLogs(): bool
    {
        return false;
    }
}
