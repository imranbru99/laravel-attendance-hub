<?php

namespace ImranDevBd\AttendanceHub\Contracts;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use ImranDevBd\AttendanceHub\DTOs\AttendancePunch;
use ImranDevBd\AttendanceHub\DTOs\DeviceConnection;
use ImranDevBd\AttendanceHub\DTOs\DeviceInfo;
use ImranDevBd\AttendanceHub\DTOs\DeviceUser;

interface DeviceDriverInterface
{
    /**
     * Connect to the hardware device or initialize client.
     */
    public function connect(DeviceConnection $connection): bool;

    /**
     * Disconnect and release network/socket resources.
     */
    public function disconnect(): void;

    /**
     * Health check / reachability ping.
     */
    public function ping(): bool;

    /**
     * Retrieve hardware specifications and capacity.
     */
    public function getDeviceInfo(): DeviceInfo;

    /**
     * Pull attendance logs from the device buffer.
     *
     * @param CarbonInterface|null $since
     * @return Collection<int, AttendancePunch>
     */
    public function pullAttendanceLogs(?CarbonInterface $since = null): Collection;

    /**
     * Pull enrolled user list from the device.
     *
     * @return Collection<int, DeviceUser>
     */
    public function pullUsers(): Collection;

    /**
     * Enroll or sync a user to the device.
     */
    public function pushUser(DeviceUser $user): bool;

    /**
     * Delete an enrolled user from the device.
     */
    public function deleteUser(string $userId): bool;

    /**
     * Listen for real-time punch events (stream) where supported.
     *
     * @param callable(AttendancePunch): void $onPunch
     */
    public function liveCapture(callable $onPunch): void;

    /**
     * Whether this driver supports incoming WAN push (ADMS/webhook).
     */
    public function supportsPush(): bool;

    /**
     * Whether the device supports biometric face templates.
     */
    public function supportsFaceEnrollment(): bool;

    /**
     * Clear the attendance log buffer on the device.
     */
    public function clearLogs(): bool;
}
