<?php

namespace ImranDevBd\AttendanceHub\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use ImranDevBd\AttendanceHub\DTOs\DeviceUser;
use ImranDevBd\AttendanceHub\Models\AttendanceDevice;

class UserEnrolled
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly AttendanceDevice $device,
        public readonly DeviceUser $user
    ) {}
}
