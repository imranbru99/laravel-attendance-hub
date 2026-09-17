<?php

namespace ImranDevBd\AttendanceHub\Exceptions;

class DeviceAuthenticationException extends AttendanceHubException
{
    public static function failed(string $provider, ?string $reason = null): self
    {
        $msg = "Authentication failed for [{$provider}] device.";
        if ($reason) {
            $msg .= " Details: {$reason}";
        }

        return new self($msg);
    }
}
