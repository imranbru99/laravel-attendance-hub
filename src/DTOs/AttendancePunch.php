<?php

namespace ImranDevBd\AttendanceHub\DTOs;

use Carbon\CarbonInterface;
use ImranDevBd\AttendanceHub\Enums\PunchType;
use ImranDevBd\AttendanceHub\Enums\VerifyMode;

class AttendancePunch
{
    public function __construct(
        public readonly string $deviceUserId,
        public readonly CarbonInterface $punchedAt,
        public readonly VerifyMode $verifyMode = VerifyMode::OTHER,
        public readonly PunchType $punchType = PunchType::AUTO,
        public readonly ?string $deviceId = null,
        public readonly ?string $employeeId = null,
        public readonly ?string $serialNumber = null,
        public readonly ?string $workCode = null,
        public readonly array $rawPayload = [],
        public readonly ?array $location = null // ['lat' => ..., 'lng' => ...] for virtual
    ) {}

    /**
     * Compute a deterministic hash for deduplication.
     */
    public function getHash(?string $deviceIdOrSerial = null): string
    {
        $id = $deviceIdOrSerial ?? $this->deviceId ?? $this->serialNumber ?? 'generic';
        $timestamp = $this->punchedAt->toIso8601String();
        
        return hash('sha256', "{$id}:{$this->deviceUserId}:{$timestamp}");
    }

    public function toArray(): array
    {
        return [
            'device_user_id' => $this->deviceUserId,
            'employee_id' => $this->employeeId,
            'punched_at' => $this->punchedAt->toDateTimeString(),
            'verify_mode' => $this->verifyMode->value,
            'punch_type' => $this->punchType->value,
            'device_id' => $this->deviceId,
            'serial_number' => $this->serialNumber,
            'work_code' => $this->workCode,
            'location' => $this->location,
            'hash' => $this->getHash(),
        ];
    }
}
