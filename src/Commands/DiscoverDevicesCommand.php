<?php

namespace ImranDevBd\AttendanceHub\Commands;

use Illuminate\Console\Command;
use ImranDevBd\AttendanceHub\Facades\AttendanceHub;
use ImranDevBd\AttendanceHub\Models\AttendanceDevice;

class DiscoverDevicesCommand extends Command
{
    protected $signature = 'attendance:discover 
                            {subnet : IPv4 subnet to scan in CIDR notation (e.g. 192.168.1.0/24)}
                            {--register : Automatically register discovered devices in database}';

    protected $description = 'Scan local network subnet to automatically discover and fingerprint attendance terminals';

    public function handle(): int
    {
        $subnet = $this->argument('subnet');
        $autoRegister = $this->option('register');

        $this->info("Scanning subnet {$subnet} for biometric & attendance hardware...");

        $scanner = AttendanceHub::scanner();
        $discovered = [];

        $scanner->scan($subnet, function (string $ip, ?array $detected) use (&$discovered) {
            if ($detected) {
                $this->info("Found: {$ip} -> Provider: {$detected['provider']} ({$detected['model']})");
                $discovered[] = $detected;
            }
        });

        if (empty($discovered)) {
            $this->warn('No attendance devices detected on the specified subnet.');
            return self::SUCCESS;
        }

        $this->newLine();
        $this->table(
            ['IP Address', 'Provider', 'Detected Model', 'Port'],
            collect($discovered)->map(fn ($d) => [
                $d['ip'],
                $d['provider'],
                $d['model'],
                $d['details']['port'] ?? '-',
            ])
        );

        if ($autoRegister) {
            foreach ($discovered as $item) {
                $exists = AttendanceDevice::where('ip', $item['ip'])->first();
                if (!$exists) {
                    AttendanceDevice::create([
                        'name' => "{$item['model']} ({$item['ip']})",
                        'provider' => $item['provider'],
                        'model' => $item['model'],
                        'ip' => $item['ip'],
                        'port' => $item['details']['port'] ?? 4370,
                        'status' => 'online',
                    ]);
                    $this->info("Registered device: {$item['ip']}");
                }
            }
        }

        return self::SUCCESS;
    }
}
