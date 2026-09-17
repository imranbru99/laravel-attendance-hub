<?php

namespace ImranDevBd\AttendanceHub\Support;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use ImranDevBd\AttendanceHub\DTOs\AttendanceShift;
use ImranDevBd\AttendanceHub\DTOs\DailyAttendanceSummary;
use ImranDevBd\AttendanceHub\Models\AttendanceLog;

class AttendanceCalculator
{
    public function __construct(
        protected ?AttendanceShift $defaultShift = null
    ) {
        $this->defaultShift = $defaultShift ?? AttendanceShift::fromArray(
            config('attendance-hub.shifts.default', [])
        );
    }

    /**
     * Calculate daily attendance summary for a specific employee on a date.
     */
    public function calculate(
        string $employeeId,
        Carbon|string $date,
        ?AttendanceShift $shift = null
    ): DailyAttendanceSummary {
        $date = is_string($date) ? Carbon::parse($date) : $date->copy();
        $dateStr = $date->format('Y-m-d');
        $shift = $shift ?? $this->defaultShift;

        $punches = AttendanceLog::where('employee_id', $employeeId)
            ->whereDate('punched_at', $dateStr)
            ->orderBy('punched_at', 'asc')
            ->get();

        if ($punches->isEmpty()) {
            return new DailyAttendanceSummary(
                employeeId: $employeeId,
                date: $dateStr,
                status: DailyAttendanceSummary::STATUS_ABSENT,
                punchesCount: 0,
                shiftName: $shift->name
            );
        }

        $firstPunch = $punches->first();
        $lastPunch = $punches->last();

        $firstIn = Carbon::parse($firstPunch->punched_at);
        $lastOut = $punches->count() > 1 ? Carbon::parse($lastPunch->punched_at) : null;

        // Shift Boundaries (aligned with firstIn date and timezone)
        $shiftStart = $shift->getShiftStartForDate($firstIn);
        $shiftEnd = $shift->getShiftEndForDate($firstIn);
        $graceEnd = $shiftStart->copy()->addMinutes($shift->gracePeriodMinutes);

        // Late calculation
        $lateMinutes = 0;
        if ($firstIn->greaterThan($graceEnd)) {
            $lateMinutes = abs((int) $firstIn->diffInMinutes($shiftStart));
        }

        // Early leave calculation
        $earlyLeaveMinutes = 0;
        if ($lastOut && $lastOut->lessThan($shiftEnd)) {
            $earlyLeaveMinutes = abs((int) $shiftEnd->diffInMinutes($lastOut));
        }

        // Total worked duration
        $totalWorkedMinutes = 0;
        if ($lastOut) {
            $grossMinutes = abs((int) $firstIn->diffInMinutes($lastOut));
            // Deduct break duration if gross time exceeds half-day threshold
            if ($grossMinutes > $shift->halfDayThresholdMinutes) {
                $totalWorkedMinutes = max(0, $grossMinutes - $shift->breakDurationMinutes);
            } else {
                $totalWorkedMinutes = $grossMinutes;
            }
        }

        // Overtime
        $overtimeMinutes = 0;
        if ($shift->allowOvertime && $totalWorkedMinutes > $shift->fullDayThresholdMinutes) {
            $overtimeMinutes = $totalWorkedMinutes - $shift->fullDayThresholdMinutes;
        }

        // Status Evaluation
        $status = DailyAttendanceSummary::STATUS_PRESENT;
        if ($totalWorkedMinutes > 0 && $totalWorkedMinutes < $shift->halfDayThresholdMinutes) {
            $status = DailyAttendanceSummary::STATUS_HALF_DAY;
        } elseif ($lateMinutes > 0) {
            $status = DailyAttendanceSummary::STATUS_LATE;
        }

        return new DailyAttendanceSummary(
            employeeId: $employeeId,
            date: $dateStr,
            firstIn: $firstIn,
            lastOut: $lastOut,
            totalWorkedMinutes: $totalWorkedMinutes,
            lateMinutes: $lateMinutes,
            earlyLeaveMinutes: $earlyLeaveMinutes,
            overtimeMinutes: $overtimeMinutes,
            status: $status,
            punchesCount: $punches->count(),
            shiftName: $shift->name,
            rawPunches: $punches->toArray()
        );
    }

    /**
     * Calculate daily attendance summaries for all employees with punches on a date.
     *
     * @return Collection<string, DailyAttendanceSummary>
     */
    public function calculateDay(Carbon|string $date, ?AttendanceShift $shift = null): Collection
    {
        $date = is_string($date) ? Carbon::parse($date) : $date->copy();
        $dateStr = $date->format('Y-m-d');

        $employeeIds = AttendanceLog::whereDate('punched_at', $dateStr)
            ->whereNotNull('employee_id')
            ->distinct()
            ->pluck('employee_id');

        $summaries = collect();

        foreach ($employeeIds as $employeeId) {
            $summaries->put($employeeId, $this->calculate($employeeId, $date, $shift));
        }

        return $summaries;
    }
}
