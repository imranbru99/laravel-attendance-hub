<?php

namespace ImranDevBd\AttendanceHub\Enums;

enum DeviceStatus: string
{
    case ONLINE = 'online';
    case OFFLINE = 'offline';
    case WARNING = 'warning';
    case UNCONFIGURED = 'unconfigured';
}
