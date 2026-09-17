<?php

namespace ImranDevBd\AttendanceHub\Commands;

use Illuminate\Console\Command;
use ImranDevBd\AttendanceHub\Facades\AttendanceHub;
use ImranDevBd\AttendanceHub\Models\AttendanceDevice;

class BackupTemplatesCommand extends Command
{
    protected $signature = 'attendance:backup-templates {device : ID of the device to backup biometric templates from}';

    protected $description = 'Download and backup fingerprint and face templates from a hardware terminal into the vault';

    public function handle(): int
    {
        $deviceId = $this->argument('device');
        $device = AttendanceDevice::find($deviceId);

        if (!$device) {
            $this->error("Device ID [{$deviceId}] not found.");
            return self::FAILURE;
        }

        $this->info("Connecting to {$device->name} ({$device->ip})...");

        try {
            $count = AttendanceHub::vault()->backupFromDevice($device);
            $this->info("Successfully backed up {$count} biometric template(s) into the vault.");
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Failed to backup templates: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}
