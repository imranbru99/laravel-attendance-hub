<?php

namespace ImranDevBd\AttendanceHub\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use ImranDevBd\AttendanceHub\DTOs\AttendancePunch;
use ImranDevBd\AttendanceHub\Enums\PunchType;
use ImranDevBd\AttendanceHub\Enums\VerifyMode;
use ImranDevBd\AttendanceHub\Models\AttendanceDevice;
use ImranDevBd\AttendanceHub\Models\DeviceUserMap;
use ImranDevBd\AttendanceHub\Support\AttendanceNormalizer;

class SimulateAttendanceCommand extends Command
{
    protected $signature = 'attendance:simulate 
                            {--employees=10 : Number of test employees to simulate}
                            {--days=7 : Number of past days to generate attendance logs for}
                            {--device-name=Demo Terminal : Name of the simulated demo hardware}
                            {--device-id=SIM_DEV_001 : Unique device hardware serial or ID}
                            {--tenant= : Optional tenant ID for multi-tenant simulation}';

    protected $description = 'Simulate realistic employee punches without physical hardware for testing and Filament UI demo';

    public function handle(): int
    {
        $employeeCount = (int) $this->option('employees');
        $daysCount = (int) $this->option('days');
        $deviceName = $this->option('device-name');
        $deviceId = $this->option('device-id') ?: 'SIM_DEV_001';
        $tenantId = $this->option('tenant');

        $this->info("Initializing simulated environment ({$employeeCount} employees, {$daysCount} days)...");

        // Create or get demo device
        $device = AttendanceDevice::firstOrCreate(
            ['serial_number' => $deviceId],
            [
                'name' => $deviceName,
                'tenant_id' => $tenantId,
                'provider' => 'fake',
                'model' => 'Hardware Simulator v1',
                'ip' => '127.0.0.1',
                'port' => 4370,
                'status' => 'online',
                'last_seen_at' => now(),
            ]
        );

        if ($tenantId && $device->tenant_id !== $tenantId) {
            $device->update(['tenant_id' => $tenantId]);
        }

        // Ensure employee mappings exist
        for ($i = 1; $i <= $employeeCount; $i++) {
            $userId = (string) (100 + $i);
            $empId = "EMP-{$userId}";

            DeviceUserMap::firstOrCreate(
                ['device_id' => $device->id, 'device_user_id' => $userId],
                ['employee_id' => $empId, 'enrolled_at' => now()]
            );
        }

        $normalizer = new AttendanceNormalizer();
        $totalPunches = 0;
        $startDate = now()->subDays($daysCount - 1)->startOfDay();

        $this->output->progressStart($daysCount);

        for ($d = 0; $d < $daysCount; $d++) {
            $date = $startDate->copy()->addDays($d);

            // Skip weekends
            if ($date->isWeekend()) {
                $this->output->progressAdvance();
                continue;
            }

            for ($i = 1; $i <= $employeeCount; $i++) {
                $userId = (string) (100 + $i);
                $empId = "EMP-{$userId}";

                // 90% attendance rate
                if (rand(1, 10) === 10) {
                    continue; // Absent day
                }

                // Check-in between 08:45 and 09:40 AM
                $inMinute = rand(-15, 40);
                $checkInTime = $date->copy()->setTime(9, 0, 0)->addMinutes($inMinute);

                // Check-out between 17:30 and 19:00 PM
                $outMinute = rand(-30, 60);
                $checkOutTime = $date->copy()->setTime(18, 0, 0)->addMinutes($outMinute);

                $mode = match (rand(1, 4)) {
                    1 => VerifyMode::FINGERPRINT,
                    2 => VerifyMode::FACE,
                    3 => VerifyMode::CARD,
                    default => VerifyMode::PIN,
                };

                // Record Check-in
                $inPunch = new AttendancePunch(
                    deviceUserId: $userId,
                    punchedAt: $checkInTime,
                    verifyMode: $mode,
                    punchType: PunchType::CHECK_IN,
                    deviceId: (string) $device->id,
                    employeeId: $empId,
                    rawPayload: ['simulation' => true]
                );
                $normalizer->record($inPunch, $device);
                $totalPunches++;

                // Record Check-out
                $outPunch = new AttendancePunch(
                    deviceUserId: $userId,
                    punchedAt: $checkOutTime,
                    verifyMode: $mode,
                    punchType: PunchType::CHECK_OUT,
                    deviceId: (string) $device->id,
                    employeeId: $empId,
                    rawPayload: ['simulation' => true]
                );
                $normalizer->record($outPunch, $device);
                $totalPunches++;
            }

            $this->output->progressAdvance();
        }

        $this->output->progressFinish();

        $this->newLine();
        $this->info("Simulation complete! Generated {$totalPunches} realistic punches across {$employeeCount} employees.");
        $this->line("View logs in your Filament panel or run: <fg=yellow>php artisan attendance:export --from={$startDate->format('Y-m-d')}</>");

        return self::SUCCESS;
    }
}
