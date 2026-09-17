<?php

namespace ImranDevBd\AttendanceHub\Exceptions;

use Throwable;

class SyncFailedException extends AttendanceHubException
{
    public static function forDevice(string $deviceName, string $deviceId, Throwable $previous): self
    {
        return new self(
            "Failed to synchronize attendance logs from device [{$deviceName}] (ID: {$deviceId}): {$previous->getMessage()}",
            (int) $previous->getCode(),
            $previous
        );
    }
}
