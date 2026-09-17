<?php

namespace ImranDevBd\AttendanceHub\DTOs;

use Carbon\Carbon;
use Carbon\CarbonInterface;

class DailyAttendanceSummary
{
    public const STATUS_PRESENT = 'PRESENT';
    public const STATUS_LATE = 'LATE';
    public const STATUS_HALF_DAY = 'HALF_DAY';
    public const STATUS_ABSENT = 'ABSENT';

    public function __construct(
        public readonly string $employeeId,
        public readonly string $date, // Y-m-d
        public readonly ?CarbonInterface $firstIn = null,
        public readonly ?CarbonInterface $lastOut = null,
        public readonly int $totalWorkedMinutes = 0,
        public readonly int $lateMinutes = 0,
        public readonly int $earlyLeaveMinutes = 0,
        public readonly int $overtimeMinutes = 0,
        public readonly string $status = self::STATUS_ABSENT,
        public readonly int $punchesCount = 0,
        public readonly ?string $shiftName = null,
        public readonly array $rawPunches = []
    ) {}

    public function toArray(): array
    {
        return [
            'employee_id' => $this->employeeId,
            'date' => $this->date,
            'first_in' => $this->firstIn?->toDateTimeString(),
            'last_out' => $this->lastOut?->toDateTimeString(),
            'total_worked_minutes' => $this->totalWorkedMinutes,
            'total_worked_hours' => round($this->totalWorkedMinutes / 60, 2),
            'late_minutes' => $this->lateMinutes,
            'early_leave_minutes' => $this->earlyLeaveMinutes,
            'overtime_minutes' => $this->overtimeMinutes,
            'status' => $this->status,
            'punches_count' => $this->punchesCount,
            'shift_name' => $this->shiftName,
        ];
    }
}
