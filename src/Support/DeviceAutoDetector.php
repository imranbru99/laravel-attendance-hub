<?php

namespace ImranDevBd\AttendanceHub\Support;

use Illuminate\Support\Facades\Http;
use ImranDevBd\AttendanceHub\DTOs\DeviceInfo;

class DeviceAutoDetector
{
    /**
     * Attempt to detect the device provider and model from an IP.
     *
     * @return array{provider: string, model: string, confidence: float, details: array}|null
     */
    public function detect(string $ip, int $timeout = 3): ?array
    {
        // 1. Probe ZKTeco TCP 4370
        $zkResult = $this->probeZKTeco($ip, 4370, $timeout);
        if ($zkResult !== null) {
            return $zkResult;
        }

        // 2. Probe Hikvision ISAPI (Ports 80, 8000, 443)
        $hikResult = $this->probeHikvision($ip, $timeout);
        if ($hikResult !== null) {
            return $hikResult;
        }

        // 3. Probe Dahua (Ports 80, 8000)
        $dahuaResult = $this->probeDahua($ip, $timeout);
        if ($dahuaResult !== null) {
            return $dahuaResult;
        }

        // 4. Probe Suprema BioStar 2 (Ports 80, 443, 51211)
        $supremaResult = $this->probeSuprema($ip, $timeout);
        if ($supremaResult !== null) {
            return $supremaResult;
        }

        // 5. Probe ADMS endpoint if the device acts as push server / host
        $admsResult = $this->probeAdms($ip, $timeout);
        if ($admsResult !== null) {
            return $admsResult;
        }

        return null;
    }

    /**
     * Probe ZKTeco native TCP protocol on port 4370.
     */
    protected function probeZKTeco(string $ip, int $port = 4370, int $timeout = 2): ?array
    {
        $socket = @fsockopen($ip, $port, $errno, $errstr, $timeout);
        if (!$socket) {
            return null;
        }

        stream_set_timeout($socket, $timeout);

        // Send CMD_CONNECT packet
        $packet = ZKTecoProtocol::createPacket(ZKTecoProtocol::CMD_CONNECT, 0, 0);
        $tcpPacket = ZKTecoProtocol::wrapTcp($packet);

        @fwrite($socket, $tcpPacket);
        $response = @fread($socket, 1024);

        if ($response) {
            $unwrapped = ZKTecoProtocol::unwrapTcp($response);
            if ($unwrapped['valid'] && strlen($unwrapped['packet']) >= 8) {
                $hdr = ZKTecoProtocol::parseHeader($unwrapped['packet']);
                if ($hdr['command'] === ZKTecoProtocol::CMD_ACK_OK) {
                    // Send CMD_EXIT before closing
                    $exitPacket = ZKTecoProtocol::createPacket(ZKTecoProtocol::CMD_EXIT, $hdr['session_id'], $hdr['reply_id']);
                    @fwrite($socket, ZKTecoProtocol::wrapTcp($exitPacket));
                    @fclose($socket);

                    return [
                        'provider' => 'zkteco',
                        'model' => 'Standalone ZK Terminal',
                        'confidence' => 0.99,
                        'details' => ['port' => $port, 'protocol' => 'tcp'],
                    ];
                }
            }
        }

        @fclose($socket);
        return null;
    }

    /**
     * Probe Hikvision ISAPI.
     */
    protected function probeHikvision(string $ip, int $timeout = 2): ?array
    {
        foreach ([80, 8000, 443] as $port) {
            $protocol = $port === 443 ? 'https' : 'http';
            $url = "{$protocol}://{$ip}:{$port}/ISAPI/System/deviceInfo";

            try {
                $response = Http::timeout($timeout)
                    ->withoutVerifying()
                    ->get($url);

                $body = $response->body();
                $headers = $response->headers();

                // Hikvision often returns 401 Unauthorized with Digest realm containing "Hikvision" or "DVR"
                $authHeader = $response->header('WWW-Authenticate') ?? '';
                $serverHeader = $response->header('Server') ?? '';

                if (
                    str_contains($body, '<DeviceInfo') ||
                    str_contains($authHeader, 'Hikvision') ||
                    str_contains($authHeader, 'App-webs') ||
                    str_contains($serverHeader, 'App-webs') ||
                    str_contains($body, 'model')
                ) {
                    $model = 'Hikvision Terminal';
                    if (preg_match('/<model>(.*?)<\/model>/i', $body, $matches)) {
                        $model = trim($matches[1]);
                    }

                    return [
                        'provider' => 'hikvision',
                        'model' => $model,
                        'confidence' => 0.95,
                        'details' => ['port' => $port, 'protocol' => $protocol],
                    ];
                }
            } catch (\Throwable) {
                // Ignore connection errors during probe
            }
        }

        return null;
    }

    /**
     * Probe Dahua HTTP API.
     */
    protected function probeDahua(string $ip, int $timeout = 2): ?array
    {
        foreach ([80, 8000] as $port) {
            $url = "http://{$ip}:{$port}/cgi-bin/magicBox.cgi?action=getSystemInfo";

            try {
                $response = Http::timeout($timeout)
                    ->withoutVerifying()
                    ->get($url);

                $body = $response->body();
                $authHeader = $response->header('WWW-Authenticate') ?? '';

                if (
                    str_contains($body, 'appType=') ||
                    str_contains($body, 'hardwareVersion=') ||
                    str_contains($authHeader, 'DH_') ||
                    str_contains($authHeader, 'Dahua')
                ) {
                    return [
                        'provider' => 'dahua',
                        'model' => 'Dahua Access Terminal',
                        'confidence' => 0.90,
                        'details' => ['port' => $port],
                    ];
                }
            } catch (\Throwable) {
                // Ignore
            }
        }

        return null;
    }

    /**
     * Probe Suprema BioStar 2 REST API.
     */
    protected function probeSuprema(string $ip, int $timeout = 2): ?array
    {
        foreach ([80, 443, 51211] as $port) {
            $protocol = $port === 443 ? 'https' : 'http';
            $url = "{$protocol}://{$ip}:{$port}/api/login";

            try {
                $response = Http::timeout($timeout)
                    ->withoutVerifying()
                    ->get($url);

                $headers = implode(' ', array_keys($response->headers()));
                $body = $response->body();

                if (
                    str_contains($headers, 'bs-session-id') ||
                    str_contains($body, 'BioStar') ||
                    str_contains($body, 'BS2')
                ) {
                    return [
                        'provider' => 'suprema',
                        'model' => 'Suprema BioStar Device',
                        'confidence' => 0.92,
                        'details' => ['port' => $port],
                    ];
                }
            } catch (\Throwable) {
                // Ignore
            }
        }

        return null;
    }

    /**
     * Probe ADMS / iClock device ping.
     */
    protected function probeAdms(string $ip, int $timeout = 2): ?array
    {
        try {
            $response = Http::timeout($timeout)
                ->withoutVerifying()
                ->get("http://{$ip}/iclock/cdata");

            if ($response->successful() && str_contains($response->body(), 'OK')) {
                return [
                    'provider' => 'adms',
                    'model' => 'ADMS/iClock Device',
                    'confidence' => 0.85,
                    'details' => ['port' => 80],
                ];
            }
        } catch (\Throwable) {
            // Ignore
        }

        return null;
    }
}
