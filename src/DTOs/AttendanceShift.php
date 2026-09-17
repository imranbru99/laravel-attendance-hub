<?php

namespace ImranDevBd\AttendanceHub\DTOs;

use Carbon\Carbon;

class AttendanceShift
{
    public function __construct(
        public readonly string $name = 'Standard Shift',
        public readonly string $startTime = '09:00',
        public readonly string $endTime = '18:00',
        public readonly int $gracePeriodMinutes = 15,
        public readonly int $halfDayThresholdMinutes = 240, // 4 hours
        public readonly int $fullDayThresholdMinutes = 480, // 8 hours
        public readonly int $breakDurationMinutes = 60,
        public readonly bool $allowOvertime = true
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? 'Standard Shift',
            startTime: $data['start_time'] ?? '09:00',
            endTime: $data['end_time'] ?? '18:00',
            gracePeriodMinutes: (int) ($data['grace_period_minutes'] ?? 15),
            halfDayThresholdMinutes: (int) ($data['half_day_threshold_minutes'] ?? 240),
            fullDayThresholdMinutes: (int) ($data['full_day_threshold_minutes'] ?? 480),
            breakDurationMinutes: (int) ($data['break_duration_minutes'] ?? 60),
            allowOvertime: (bool) ($data['allow_overtime'] ?? true)
        );
    }

    public function getShiftStartForDate(Carbon $date): Carbon
    {
        [$hour, $min] = explode(':', $this->startTime);
        return $date->copy()->setTime((int) $hour, (int) $min, 0);
    }

    public function getShiftEndForDate(Carbon $date): Carbon
    {
        [$hour, $min] = explode(':', $this->endTime);
        return $date->copy()->setTime((int) $hour, (int) $min, 0);
    }
}
