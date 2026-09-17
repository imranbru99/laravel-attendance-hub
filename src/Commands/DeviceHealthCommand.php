<?php

namespace ImranDevBd\AttendanceHub\Commands;

use Illuminate\Console\Command;
use ImranDevBd\AttendanceHub\Events\DeviceCameOnline;
use ImranDevBd\AttendanceHub\Events\DeviceWentOffline;
use ImranDevBd\AttendanceHub\Facades\AttendanceHub;
use ImranDevBd\AttendanceHub\Models\AttendanceDevice;

class DeviceHealthCommand extends Command
{
    protected $signature = 'attendance:health {device? : Specific device ID to check}';

    protected $description = 'Perform connectivity health checks and update status on registered devices';

    public function handle(): int
    {
        $deviceId = $this->argument('device');
        $devices = $deviceId
            ? AttendanceDevice::where('id', $deviceId)->get()
            : AttendanceDevice::all();

        if ($devices->isEmpty()) {
            $this->warn('No devices found to check.');
            return self::SUCCESS;
        }

        $rows = [];

        foreach ($devices as $device) {
            $prevStatus = $device->status;

            try {
                $driver = AttendanceHub::device($device);
                $isOnline = $driver->ping();
                $driver->disconnect();
            } catch (\Throwable) {
                $isOnline = false;
            }

            if ($isOnline) {
                $device->markOnline();
                if ($prevStatus !== 'online') {
                    event(new DeviceCameOnline($device));
                }
            } else {
                $device->markOffline();
                if ($prevStatus === 'online') {
                    event(new DeviceWentOffline($device, 'Ping timeout during health check'));
                }
            }

            $rows[] = [
                $device->id,
                $device->name,
                $device->provider,
                $device->ip ?: 'Push/WAN',
                $isOnline ? '<fg=green>ONLINE</>' : '<fg=red>OFFLINE</>',
                $device->last_seen_at?->diffForHumans() ?? 'Never',
            ];
        }

        $this->table(
            ['ID', 'Name', 'Provider', 'Address', 'Status', 'Last Seen'],
            $rows
        );

        return self::SUCCESS;
    }
}
