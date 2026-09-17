<?php

namespace ImranDevBd\AttendanceHub\Commands;

use Illuminate\Console\Command;
use ImranDevBd\AttendanceHub\Facades\AttendanceHub;
use ImranDevBd\AttendanceHub\Models\AttendanceDevice;

class RestoreTemplatesCommand extends Command
{
    protected $signature = 'attendance:restore-templates 
                            {target_device : Target device ID to restore templates to}
                            {--users= : Comma-separated list of employee/user IDs to restore}';

    protected $description = 'Push backed-up biometric templates from the vault to a new or replacement terminal';

    public function handle(): int
    {
        $deviceId = $this->argument('target_device');
        $device = AttendanceDevice::find($deviceId);

        if (!$device) {
            $this->error("Device ID [{$deviceId}] not found.");
            return self::FAILURE;
        }

        $usersOption = $this->option('users');
        $userIds = $usersOption ? explode(',', $usersOption) : null;

        $this->info("Restoring biometric templates to {$device->name} ({$device->ip})...");

        try {
            $pushed = AttendanceHub::vault()->restoreToDevice($device, $userIds);
            $this->info("Successfully pushed {$pushed} user profile(s) with biometric templates to the target device.");
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Failed to restore templates: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}
