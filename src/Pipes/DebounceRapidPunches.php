<?php

namespace ImranDevBd\AttendanceHub\Pipes;

use Closure;
use ImranDevBd\AttendanceHub\DTOs\AttendancePunch;
use ImranDevBd\AttendanceHub\Models\AttendanceLog;

class DebounceRapidPunches
{
    public function handle(AttendancePunch $punch, Closure $next)
    {
        $debounceMinutes = (int) config('attendance-hub.pipeline.debounce_minutes', 2);

        if ($debounceMinutes > 0) {
            $recent = AttendanceLog::where('device_user_id', $punch->deviceUserId)
                ->where('punched_at', '>=', $punch->punchedAt->copy()->subMinutes($debounceMinutes))
                ->where('punched_at', '<=', $punch->punchedAt->copy()->addMinutes($debounceMinutes))
                ->exists();

            if ($recent) {
                // Drop punch as rapid accidental duplicate tap
                return null;
            }
        }

        return $next($punch);
    }
}
