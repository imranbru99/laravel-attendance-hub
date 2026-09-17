<?php

namespace ImranDevBd\AttendanceHub\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use ImranDevBd\AttendanceHub\Models\AttendanceDevice;

class DeviceCameOnline
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly AttendanceDevice $device
    ) {}
}
