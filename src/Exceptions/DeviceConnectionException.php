<?php

namespace ImranDevBd\AttendanceHub\Exceptions;

class DeviceConnectionException extends AttendanceHubException
{
    public static function unreachable(string $ip, int $port, ?string $reason = null): self
    {
        $message = "Unable to connect to attendance hardware at [{$ip}:{$port}].";
        if ($reason) {
            $message .= " Reason: {$reason}";
        }

        return new self($message);
    }

    public static function timeout(string $ip, int $timeout): self
    {
        return new self("Connection timed out after {$timeout} seconds attempting to reach device at [{$ip}].");
    }
}
