<?php

namespace ImranDevBd\AttendanceHub\Support;

use Illuminate\Support\Collection;

class NetworkScanner
{
    public function __construct(
        protected DeviceAutoDetector $autoDetector = new DeviceAutoDetector()
    ) {}

    /**
     * Scan a CIDR subnet or range for attendance devices.
     *
     * @param string $cidr e.g. "192.168.1.0/24"
     * @param callable|null $onProgress function(string $ip, ?array $detected): void
     * @return Collection<int, array{ip: string, provider: string, model: string, details: array}>
     */
    public function scan(string $cidr, ?callable $onProgress = null): Collection
    {
        $ips = $this->expandCidr($cidr);
        $results = collect();

        foreach ($ips as $ip) {
            $detected = $this->autoDetector->detect($ip, 1);

            if ($detected !== null) {
                $item = [
                    'ip' => $ip,
                    'provider' => $detected['provider'],
                    'model' => $detected['model'],
                    'details' => $detected['details'],
                ];
                $results->push($item);

                if ($onProgress) {
                    $onProgress($ip, $item);
                }
            } else {
                if ($onProgress) {
                    $onProgress($ip, null);
                }
            }
        }

        return $results;
    }

    /**
     * Convert CIDR notation into an array of IP addresses.
     */
    public function expandCidr(string $cidr): array
    {
        if (!str_contains($cidr, '/')) {
            return [$cidr];
        }

        [$subnet, $mask] = explode('/', $cidr);
        $mask = (int) $mask;

        // Limit to /24 maximum scan range for safety and performance (254 IPs)
        if ($mask < 24) {
            $mask = 24;
        }

        $ipLong = ip2long($subnet);
        $netmask = ~((1 << (32 - $mask)) - 1);
        $network = $ipLong & $netmask;
        $broadcast = $network | (~$netmask);

        $ips = [];
        for ($i = $network + 1; $i < $broadcast; $i++) {
            $ips[] = long2ip($i);
        }

        return $ips;
    }
}
