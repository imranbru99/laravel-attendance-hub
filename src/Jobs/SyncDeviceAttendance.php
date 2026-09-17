<?php

namespace ImranDevBd\AttendanceHub\Jobs;

use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use ImranDevBd\AttendanceHub\Facades\AttendanceHub;
use ImranDevBd\AttendanceHub\Models\AttendanceDevice;
use Throwable;

class SyncDeviceAttendance implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;
    public int $backoff = 30;

    public function __construct(
        public readonly AttendanceDevice|int|string $device,
        public readonly ?CarbonInterface $since = null
    ) {}

    public function handle(): void
    {
        $deviceModel = $this->device instanceof AttendanceDevice
            ? $this->device
            : AttendanceDevice::find($this->device);

        if (!$deviceModel) {
            Log::warning("[AttendanceHub] Device not found for queued sync: " . json_encode($this->device));
            return;
        }

        Log::info("[AttendanceHub] Starting scheduled sync for device: {$deviceModel->name} (ID: {$deviceModel->id})");

        $logs = AttendanceHub::sync($deviceModel, $this->since);

        Log::info("[AttendanceHub] Sync completed for device: {$deviceModel->name}. {$logs->count()} records processed.");
    }

    public function failed(Throwable $exception): void
    {
        Log::error("[AttendanceHub] Sync failed permanently for device ID: " . json_encode($this->device) . " Error: " . $exception->getMessage());
    }
}
