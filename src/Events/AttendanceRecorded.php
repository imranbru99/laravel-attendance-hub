<?php

namespace ImranDevBd\AttendanceHub\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use ImranDevBd\AttendanceHub\DTOs\AttendancePunch;
use ImranDevBd\AttendanceHub\Models\AttendanceLog;

class AttendanceRecorded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly AttendanceLog $log,
        public readonly ?AttendancePunch $punch = null
    ) {}
}
