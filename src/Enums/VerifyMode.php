<?php

namespace ImranDevBd\AttendanceHub\Enums;

enum VerifyMode: string
{
    case FINGERPRINT = 'fingerprint';
    case FACE = 'face';
    case CARD = 'card';
    case PIN = 'pin';
    case PALM = 'palm';
    case IRIS = 'iris';
    case QR = 'qr';
    case GPS = 'gps';
    case BLUETOOTH = 'bluetooth';
    case MANUAL = 'manual';
    case OTHER = 'other';

    public static function fromRawCode(int|string|null $code): self
    {
        if ($code === null) {
            return self::OTHER;
        }

        $code = (int) $code;

        return match ($code) {
            1 => self::FINGERPRINT,
            2 => self::PIN,
            3 => self::CARD,
            15, 20 => self::FACE,
            25 => self::PALM,
            default => self::OTHER,
        };
    }
}
