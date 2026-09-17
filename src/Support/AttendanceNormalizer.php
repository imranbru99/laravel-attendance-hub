<?php

namespace ImranDevBd\AttendanceHub\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Collection;
use ImranDevBd\AttendanceHub\DTOs\AttendancePunch;
use ImranDevBd\AttendanceHub\Events\AttendanceRecorded;
use ImranDevBd\AttendanceHub\Jobs\DispatchAttendanceWebhook;
use ImranDevBd\AttendanceHub\Models\AttendanceDevice;
use ImranDevBd\AttendanceHub\Models\AttendanceLog;
use ImranDevBd\AttendanceHub\Models\DeviceUserMap;

class AttendanceNormalizer
{
    /**
     * Custom employee resolver hook.
     *
     * @var (callable(string $deviceUserId, ?AttendanceDevice $device): ?string)|null
     */
    protected static $employeeResolver = null;

    /**
     * Register a callback to resolve employee_id from device_user_id.
     */
    public static function resolveEmployeeUsing(callable $callback): void
    {
        static::$employeeResolver = $callback;
    }

    /**
     * Normalize a single AttendancePunch and persist it to the database.
     */
    public function record(AttendancePunch $punch, ?AttendanceDevice $device = null): ?AttendanceLog
    {
        $deviceId = $device?->id ?? $punch->deviceId;
        $targetTimezone = config('attendance-hub.timezone', 'UTC');
        $deviceTimezone = $device?->timezone ?? config('attendance-hub.device_timezone', 'UTC');

        // Adjust timezone if device timezone differs from target application timezone
        $punchedAt = Carbon::parse($punch->punchedAt)
            ->setTimezone($deviceTimezone)
            ->setTimezone($targetTimezone);

        // Compute deduplication hash
        $hash = $punch->getHash($deviceId ? (string) $deviceId : null);

        // Check if exact punch was already recorded (idempotent deduplication)
        $existing = AttendanceLog::where('punch_hash', $hash)->first();
        if ($existing) {
            return $existing;
        }

        // Execute punch pipeline (debouncing rapid double-taps, anti-passback, custom pipes)
        $pipes = config('attendance-hub.pipeline.pipes', []);
        if (!empty($pipes)) {
            $punch = app(Pipeline::class)
                ->send($punch)
                ->through($pipes)
                ->thenReturn();

            if ($punch === null) {
                return null;
            }
        }

        // Resolve Employee ID
        $employeeId = $punch->employeeId ?? $this->resolveEmployeeId($punch->deviceUserId, $device);
        if (!$employeeId && ($punch->deviceId === 'virtual' || $device?->provider === 'virtual')) {
            $employeeId = $punch->deviceUserId;
        }

        $log = AttendanceLog::create([
            'device_id' => $deviceId,
            'device_user_id' => $punch->deviceUserId,
            'employee_id' => $employeeId,
            'punched_at' => $punchedAt,
            'verify_mode' => $punch->verifyMode->value,
            'punch_type' => $punch->punchType->value,
            'punch_hash' => $hash,
            'location' => $punch->location,
            'raw_payload' => $punch->rawPayload,
        ]);

        event(new AttendanceRecorded($log, $punch));

        if (config('attendance-hub.webhooks.enabled', false)) {
            DispatchAttendanceWebhook::dispatch($log);
        }

        return $log;
    }

    /**
     * Normalize and record a batch of punches.
     *
     * @param iterable<AttendancePunch> $punches
     * @return Collection<int, AttendanceLog>
     */
    public function recordMany(iterable $punches, ?AttendanceDevice $device = null): Collection
    {
        $recorded = collect();

        foreach ($punches as $punch) {
            $log = $this->record($punch, $device);
            if ($log) {
                $recorded->push($log);
            }
        }

        return $recorded;
    }

    /**
     * Resolve employee ID from mapping table or custom resolver.
     */
    protected function resolveEmployeeId(string $deviceUserId, ?AttendanceDevice $device): ?string
    {
        if (static::$employeeResolver !== null) {
            $resolved = call_user_func(static::$employeeResolver, $deviceUserId, $device);
            if ($resolved !== null) {
                return (string) $resolved;
            }
        }

        if ($device) {
            $map = DeviceUserMap::where('device_id', $device->id)
                ->where('device_user_id', $deviceUserId)
                ->first();

            if ($map) {
                return (string) $map->employee_id;
            }
        }

        // Fallback: If deviceUserId is already the employee ID or if mapped globally
        $globalMap = DeviceUserMap::where('device_user_id', $deviceUserId)->first();
        if ($globalMap) {
            return (string) $globalMap->employee_id;
        }

        return null;
    }
}
