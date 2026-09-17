<?php

namespace ImranDevBd\AttendanceHub\Enums;

enum PunchType: string
{
    case CHECK_IN = 'check_in';
    case CHECK_OUT = 'check_out';
    case BREAK_OUT = 'break_out';
    case BREAK_IN = 'break_in';
    case OVERTIME_IN = 'overtime_in';
    case OVERTIME_OUT = 'overtime_out';
    case AUTO = 'auto';

    public static function fromRawState(int|string|null $state): self
    {
        if ($state === null) {
            return self::AUTO;
        }

        $state = (int) $state;

        return match ($state) {
            0 => self::CHECK_IN,
            1 => self::CHECK_OUT,
            2 => self::BREAK_OUT,
            3 => self::BREAK_IN,
            4 => self::OVERTIME_IN,
            5 => self::OVERTIME_OUT,
            default => self::AUTO,
        };
    }
}
