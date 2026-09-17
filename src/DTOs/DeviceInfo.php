<?php

namespace ImranDevBd\AttendanceHub\DTOs;

class DeviceInfo
{
    public function __construct(
        public readonly ?string $serialNumber = null,
        public readonly ?string $deviceName = null,
        public readonly ?string $firmwareVersion = null,
        public readonly ?string $macAddress = null,
        public readonly ?string $platform = null,
        public readonly int $userCount = 0,
        public readonly int $logCount = 0,
        public readonly int $fingerprintCount = 0,
        public readonly int $faceCount = 0,
        public readonly array $rawInfo = []
    ) {}

    public function toArray(): array
    {
        return [
            'serial_number' => $this->serialNumber,
            'device_name' => $this->deviceName,
            'firmware_version' => $this->firmwareVersion,
            'mac_address' => $this->macAddress,
            'platform' => $this->platform,
            'user_count' => $this->userCount,
            'log_count' => $this->logCount,
            'fingerprint_count' => $this->fingerprintCount,
            'face_count' => $this->faceCount,
            'raw_info' => $this->rawInfo,
        ];
    }
}
