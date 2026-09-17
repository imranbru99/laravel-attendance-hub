<?php

namespace ImranDevBd\AttendanceHub\Pipes;

use Closure;
use ImranDevBd\AttendanceHub\DTOs\AttendancePunch;
use ImranDevBd\AttendanceHub\Enums\PunchType;
use ImranDevBd\AttendanceHub\Models\AttendanceLog;

class AntiPassbackCheck
{
    public function handle(AttendancePunch $punch, Closure $next)
    {
        if (config('attendance-hub.pipeline.anti_passback', false)) {
            $lastLog = AttendanceLog::where('device_user_id', $punch->deviceUserId)
                ->where('punched_at', '<=', $punch->punchedAt)
                ->orderBy('punched_at', 'desc')
                ->first();

            if ($lastLog && $lastLog->punch_type === PunchType::CHECK_IN->value && $punch->punchType === PunchType::CHECK_IN) {
                $payload = $punch->rawPayload;
                $payload['anti_passback_violation'] = true;

                $punch = new AttendancePunch(
                    deviceUserId: $punch->deviceUserId,
                    punchedAt: $punch->punchedAt,
                    verifyMode: $punch->verifyMode,
                    punchType: $punch->punchType,
                    deviceId: $punch->deviceId,
                    employeeId: $punch->employeeId,
                    serialNumber: $punch->serialNumber,
                    workCode: $punch->workCode,
                    rawPayload: $payload,
                    location: $punch->location
                );
            }
        }

        return $next($punch);
    }
}
