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

class AdmsPushDriver extends AbstractDeviceDriver
{
    /**
     * Stored commands for device dispatch via /iclock/getrequest.
     */
    protected static array $commandQueue = [];

    public function connect(DeviceConnection $connection): bool
    {
        $this->connection = $connection;
        $this->connected = true;

        return true;
    }

    public function ping(): bool
    {
        return true;
    }

    public function getDeviceInfo(): DeviceInfo
    {
        return new DeviceInfo(
            serialNumber: $this->connection?->serialNumber,
            deviceName: 'ADMS / iClock Push Terminal',
            platform: 'ADMS Cloud Protocol'
        );
    }

    public function supportsPush(): bool
    {
        return true;
    }

    public function supportsFaceEnrollment(): bool
    {
        return true;
    }

    public function clearLogs(): bool
    {
        if ($this->connection?->serialNumber) {
            self::queueCommand($this->connection->serialNumber, 'CLEAR LOG');
            return true;
        }

        return false;
    }

    public function pushUser(DeviceUser $user): bool
    {
        if (!$this->connection?->serialNumber) {
            return false;
        }

        // ADMS command to update user info
        $cmd = sprintf(
            'DATA USER PIN=%s\tName=%s\tPri=%d\tPasswd=%s\tCard=%s',
            $user->userId,
            $user->name,
            $user->role,
            $user->password ?? '',
            $user->card ?? ''
        );

        self::queueCommand($this->connection->serialNumber, $cmd);

        return true;
    }

    public function deleteUser(string $userId): bool
    {
        if (!$this->connection?->serialNumber) {
            return false;
        }

        self::queueCommand($this->connection->serialNumber, "DATA DELETE USER PIN={$userId}");

        return true;
    }

    /**
     * Parse raw ADMS/iClock ATTLOG payload into AttendancePunch collection.
     * ADMS rows are typically tab or space-separated:
     * Format: PIN \t Time \t Status \t Verify \t Workcode ...
     * Example: 101 \t 2026-09-17 08:30:00 \t 0 \t 1 \t 0
     *
     * @return Collection<int, AttendancePunch>
     */
    public static function parsePushPayload(string $payload, ?string $serial = null, ?string $deviceId = null): Collection
    {
        $punches = collect();
        $lines = preg_split('/\r\n|\r|\n/', trim($payload));

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            // Split by tab or multiple spaces
            $parts = preg_split('/[\t]+/', $line);
            if (count($parts) < 2) {
                $parts = preg_split('/\s{2,}/', $line);
            }

            if (count($parts) < 2) {
                continue;
            }

            $pin = trim($parts[0]);
            $timeString = trim($parts[1]);

            try {
                $punchedAt = Carbon::parse($timeString);
            } catch (\Throwable) {
                continue;
            }

            $state = isset($parts[2]) ? (int) $parts[2] : 0;
            $verify = isset($parts[3]) ? (int) $parts[3] : 1;
            $workCode = isset($parts[4]) ? trim($parts[4]) : null;

            $punches->push(new AttendancePunch(
                deviceUserId: $pin,
                punchedAt: $punchedAt,
                verifyMode: VerifyMode::fromRawCode($verify),
                punchType: PunchType::fromRawState($state),
                deviceId: $deviceId,
                serialNumber: $serial,
                workCode: $workCode,
                rawPayload: ['raw_line' => $line, 'source' => 'adms_push']
            ));
        }

        return $punches;
    }

    /**
     * Queue a command for a specific device serial.
     */
    public static function queueCommand(string $serial, string $command): int
    {
        if (!isset(self::$commandQueue[$serial])) {
            self::$commandQueue[$serial] = [];
        }

        $id = count(self::$commandQueue[$serial]) + 1;
        self::$commandQueue[$serial][] = [
            'id' => $id,
            'command' => $command,
            'created_at' => now(),
        ];

        return $id;
    }

    /**
     * Retrieve and drain pending commands for a device.
     */
    public static function getPendingCommands(string $serial): array
    {
        $commands = self::$commandQueue[$serial] ?? [];
        self::$commandQueue[$serial] = []; // Clear queue on retrieval

        return $commands;
    }
}
