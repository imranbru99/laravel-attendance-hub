<?php

namespace ImranDevBd\AttendanceHub\Exceptions;

class UnsupportedOperationException extends AttendanceHubException
{
    public static function make(string $operation, string $provider): self
    {
        return new self("The operation [{$operation}] is not supported by the [{$provider}] driver or hardware model.");
    }
}
