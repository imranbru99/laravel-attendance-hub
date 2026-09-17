<?php

namespace ImranDevBd\AttendanceHub\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use ImranDevBd\AttendanceHub\Facades\AttendanceHub;
use ImranDevBd\AttendanceHub\Jobs\SyncDeviceAttendance;
use ImranDevBd\AttendanceHub\Models\AttendanceDevice;

class SyncAttendanceCommand extends Command
{
    protected $signature = 'attendance:sync 
                            {device? : The ID of the device to sync}
                            {--all : Sync all registered devices}
                            {--queue : Dispatch sync as background queue jobs}
                            {--since= : Sync logs since datetime (e.g. 2026-01-01)}';

    protected $description = 'Pull attendance records from registered physical hardware terminals';

    public function handle(): int
    {
        $deviceId = $this->argument('device');
        $all = $this->option('all');
        $queue = $this->option('queue');
        $sinceStr = $this->option('since');
        $since = $sinceStr ? Carbon::parse($sinceStr) : null;

        if (!$deviceId && !$all) {
            $this->error('Please specify a device ID or use the --all flag.');
            return self::FAILURE;
        }

        $devices = $all
            ? AttendanceDevice::all()
            : AttendanceDevice::where('id', $deviceId)->get();

        if ($devices->isEmpty()) {
            $this->warn('No attendance devices found to sync.');
            return self::SUCCESS;
        }

        $this->info(sprintf('Processing sync for %d device(s)...', $devices->count()));

        foreach ($devices as $device) {
            $this->line(sprintf('Syncing device: %s [%s] (%s)', $device->name, $device->provider, $device->ip ?: 'Push/WAN'));

            if ($queue) {
                SyncDeviceAttendance::dispatch($device, $since);
                $this->info(" -> Dispatched to queue.");
                continue;
            }

            try {
                $logs = AttendanceHub::sync($device, $since);
                $this->info(sprintf(' -> Success! %d new attendance logs processed.', $logs->count()));
            } catch (\Throwable $e) {
                $this->error(" -> Failed: " . $e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}
