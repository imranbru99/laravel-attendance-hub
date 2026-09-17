<?php

namespace ImranDevBd\AttendanceHub\Commands;

use Illuminate\Console\Command;
use ImranDevBd\AttendanceHub\DTOs\DeviceUser;
use ImranDevBd\AttendanceHub\Events\UserEnrolled;
use ImranDevBd\AttendanceHub\Facades\AttendanceHub;
use ImranDevBd\AttendanceHub\Models\AttendanceDevice;
use ImranDevBd\AttendanceHub\Models\DeviceUserMap;

class EnrollUserCommand extends Command
{
    protected $signature = 'attendance:enroll 
                            {device : The ID of the target device}
                            {--user-id= : Employee / User ID}
                            {--name= : Full name of the user}
                            {--card= : Proximity / RFID card number}
                            {--admin : Grant administrator privilege on terminal}';

    protected $description = 'Enroll or sync an employee user to an attendance terminal';

    public function handle(): int
    {
        $deviceId = $this->argument('device');
        $device = AttendanceDevice::find($deviceId);

        if (!$device) {
            $this->error("Device ID [{$deviceId}] not found.");
            return self::FAILURE;
        }

        $userId = $this->option('user-id') ?: $this->ask('Enter User ID / PIN');
        $name = $this->option('name') ?: $this->ask('Enter Full Name');
        $card = $this->option('card');
        $isAdmin = $this->option('admin');

        $driver = AttendanceHub::device($device);

        $user = new DeviceUser(
            uid: $userId,
            userId: $userId,
            name: $name,
            role: $isAdmin ? 14 : 0,
            card: $card
        );

        $this->info("Pushing user [{$name}] to device [{$device->name}]...");

        $success = $driver->pushUser($user);
        $driver->disconnect();

        if ($success) {
            // Update mapping table
            DeviceUserMap::updateOrCreate(
                ['device_id' => $device->id, 'device_user_id' => $userId],
                ['employee_id' => $userId, 'enrolled_at' => now()]
            );

            event(new UserEnrolled($device, $user));

            $this->info("User enrolled successfully.");
            return self::SUCCESS;
        }

        $this->error("Failed to enroll user on device.");
        return self::FAILURE;
    }
}
