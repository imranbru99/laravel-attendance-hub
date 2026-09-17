<?php

namespace ImranDevBd\AttendanceHub\Drivers;

use Carbon\CarbonInterface;
use Exception;
use Illuminate\Support\Collection;
use ImranDevBd\AttendanceHub\DTOs\AttendancePunch;
use ImranDevBd\AttendanceHub\DTOs\DeviceConnection;
use ImranDevBd\AttendanceHub\DTOs\DeviceInfo;
use ImranDevBd\AttendanceHub\DTOs\DeviceUser;
use ImranDevBd\AttendanceHub\Support\ZKTecoProtocol;

class ZKTecoDriver extends AbstractDeviceDriver
{
    /**
     * @var resource|null
     */
    protected $socket = null;

    protected int $sessionId = 0;
    protected int $replyId = 0;

    public function connect(DeviceConnection $connection): bool
    {
        $this->connection = $connection;
        $ip = $connection->ip;
        $port = $connection->port ?: 4370;
        $timeout = $connection->timeout ?: 5;

        $protocol = strtolower($connection->protocol ?: 'tcp');

        if ($protocol === 'udp') {
            $this->socket = @fsockopen("udp://{$ip}", $port, $errno, $errstr, $timeout);
        } else {
            $this->socket = @fsockopen($ip, $port, $errno, $errstr, $timeout);
        }

        if (!$this->socket) {
            $this->connected = false;
            return false;
        }

        stream_set_timeout($this->socket, $timeout);

        // Initiate handshake with CMD_CONNECT
        $packet = ZKTecoProtocol::createPacket(ZKTecoProtocol::CMD_CONNECT, 0, 0);
        $this->writePacket($packet);

        $response = $this->readPacket();
        if (!$response) {
            $this->disconnect();
            return false;
        }

        $header = ZKTecoProtocol::parseHeader($response);
        if ($header['command'] !== ZKTecoProtocol::CMD_ACK_OK) {
            $this->disconnect();
            return false;
        }

        $this->sessionId = $header['session_id'];
        $this->replyId = $header['reply_id'];
        $this->connected = true;

        return true;
    }

    public function disconnect(): void
    {
        if ($this->connected && $this->socket) {
            try {
                $packet = ZKTecoProtocol::createPacket(
                    ZKTecoProtocol::CMD_EXIT,
                    $this->sessionId,
                    $this->replyId
                );
                $this->writePacket($packet);
            } catch (\Throwable) {
                // Silent fail on exit
            }
        }

        if ($this->socket && is_resource($this->socket)) {
            @fclose($this->socket);
        }

        $this->socket = null;
        $this->connected = false;
        $this->sessionId = 0;
        $this->replyId = 0;
    }

    public function ping(): bool
    {
        if (!$this->connected) {
            if ($this->connection) {
                return $this->connect($this->connection);
            }
            return false;
        }

        try {
            $packet = ZKTecoProtocol::createPacket(
                ZKTecoProtocol::CMD_ENABLEDEVICE,
                $this->sessionId,
                $this->replyId
            );
            $this->writePacket($packet);
            $res = $this->readPacket();
            if ($res) {
                $header = ZKTecoProtocol::parseHeader($res);
                return $header['command'] === ZKTecoProtocol::CMD_ACK_OK;
            }
        } catch (\Throwable) {
            $this->disconnect();
            return false;
        }

        return false;
    }

    public function getDeviceInfo(): DeviceInfo
    {
        $version = $this->getDeviceVersion();
        $serial = $this->getDeviceSerial();

        return new DeviceInfo(
            serialNumber: $serial ?: $this->connection?->serialNumber,
            deviceName: 'ZKTeco Standalone Device',
            firmwareVersion: $version,
            platform: 'ZEM500/ZEM800/Linux',
            rawInfo: [
                'version' => $version,
                'serial' => $serial,
            ]
        );
    }

    public function pullAttendanceLogs(?CarbonInterface $since = null): Collection
    {
        if (!$this->connected) {
            return collect();
        }

        // Disable device during data read to prevent conflict
        $this->sendCommand(ZKTecoProtocol::CMD_DISABLEDEVICE);

        // Send CMD_ATTLOG_RRQ
        $this->sendCommand(ZKTecoProtocol::CMD_ATTLOG_RRQ);
        $data = $this->readDataStream();

        // Re-enable device
        $this->sendCommand(ZKTecoProtocol::CMD_ENABLEDEVICE);

        $logs = ZKTecoProtocol::parseAttendanceLogs($data, $this->connection?->ip);

        return collect($logs)->filter(function (AttendancePunch $punch) use ($since) {
            if ($since === null) {
                return true;
            }
            return $punch->punchedAt->greaterThanOrEqualTo($since);
        })->values();
    }

    public function pullUsers(): Collection
    {
        if (!$this->connected) {
            return collect();
        }

        $this->sendCommand(ZKTecoProtocol::CMD_DISABLEDEVICE);
        $this->sendCommand(ZKTecoProtocol::CMD_USERTEMP_RRQ);
        $data = $this->readDataStream();
        $this->sendCommand(ZKTecoProtocol::CMD_ENABLEDEVICE);

        $users = ZKTecoProtocol::parseUsers($data);

        return collect($users);
    }

    public function pushUser(DeviceUser $user): bool
    {
        if (!$this->connected) {
            return false;
        }

        // Build ZK8 user record (72 bytes)
        $uid = (int) $user->uid;
        $name = substr($user->name, 0, 24);
        $password = substr($user->password ?? '', 0, 8);
        $card = (int) ($user->card ?? 0);
        $userId = substr($user->userId, 0, 24);

        $record = pack('v', $uid)
            . chr($user->role)
            . str_pad($password, 8, "\0")
            . str_pad($name, 24, "\0")
            . pack('V', $card)
            . str_repeat("\0", 9)
            . str_pad($userId, 24, "\0");

        $packet = ZKTecoProtocol::createPacket(
            ZKTecoProtocol::CMD_USERINFO_WRQ,
            $this->sessionId,
            $this->replyId,
            $record
        );

        $this->writePacket($packet);
        $resp = $this->readPacket();

        if ($resp) {
            $hdr = ZKTecoProtocol::parseHeader($resp);
            return $hdr['command'] === ZKTecoProtocol::CMD_ACK_OK;
        }

        return false;
    }

    public function deleteUser(string $userId): bool
    {
        if (!$this->connected) {
            return false;
        }

        $payload = pack('v', (int) $userId);
        $packet = ZKTecoProtocol::createPacket(
            ZKTecoProtocol::CMD_DELETE_USER,
            $this->sessionId,
            $this->replyId,
            $payload
        );

        $this->writePacket($packet);
        $resp = $this->readPacket();

        if ($resp) {
            $hdr = ZKTecoProtocol::parseHeader($resp);
            return $hdr['command'] === ZKTecoProtocol::CMD_ACK_OK;
        }

        return false;
    }

    public function clearLogs(): bool
    {
        if (!$this->connected) {
            return false;
        }

        $res = $this->sendCommand(ZKTecoProtocol::CMD_CLEAR_ATTLOG);
        return $res && ($res['command'] === ZKTecoProtocol::CMD_ACK_OK);
    }

    public function supportsPush(): bool
    {
        return false;
    }

    public function supportsFaceEnrollment(): bool
    {
        return true;
    }

    public function liveCapture(callable $onPunch): void
    {
        // Real-time event capture loop
        if (!$this->connected) {
            return;
        }

        // Enable real-time events on terminal
        $packet = ZKTecoProtocol::createPacket(
            500, // CMD_REG_EVENT
            $this->sessionId,
            $this->replyId,
            pack('V', 0xFFFF)
        );
        $this->writePacket($packet);

        while ($this->connected && $this->socket && !feof($this->socket)) {
            $packetData = $this->readPacket();
            if (!$packetData) {
                continue;
            }

            $hdr = ZKTecoProtocol::parseHeader($packetData);
            if ($hdr['command'] === 500 && strlen($hdr['payload']) >= 8) {
                // Parse instant event punch
                $punches = ZKTecoProtocol::parseAttendanceLogs($hdr['payload'], $this->connection?->ip);
                foreach ($punches as $punch) {
                    $onPunch($punch);
                }
            }
        }
    }

    protected function getDeviceVersion(): ?string
    {
        $res = $this->sendCommand(ZKTecoProtocol::CMD_VERSION);
        if ($res && !empty($res['payload'])) {
            return trim($res['payload']);
        }
        return null;
    }

    protected function getDeviceSerial(): ?string
    {
        $packet = ZKTecoProtocol::createPacket(
            ZKTecoProtocol::CMD_DEVICE,
            $this->sessionId,
            $this->replyId,
            '~SerialNumber' . "\0"
        );
        $this->writePacket($packet);
        $resp = $this->readPacket();
        if ($resp) {
            $hdr = ZKTecoProtocol::parseHeader($resp);
            if ($hdr['command'] === ZKTecoProtocol::CMD_ACK_OK) {
                $parts = explode('=', $hdr['payload']);
                return isset($parts[1]) ? trim($parts[1]) : trim($hdr['payload']);
            }
        }
        return null;
    }

    protected function sendCommand(int $command, string $payload = ''): ?array
    {
        $packet = ZKTecoProtocol::createPacket($command, $this->sessionId, $this->replyId, $payload);
        $this->writePacket($packet);
        $res = $this->readPacket();
        if ($res) {
            $hdr = ZKTecoProtocol::parseHeader($res);
            $this->replyId = $hdr['reply_id'];
            return $hdr;
        }
        return null;
    }

    protected function writePacket(string $packet): void
    {
        if (!$this->socket) {
            return;
        }

        $protocol = strtolower($this->connection?->protocol ?: 'tcp');
        if ($protocol === 'tcp') {
            $packet = ZKTecoProtocol::wrapTcp($packet);
        }

        @fwrite($this->socket, $packet);
    }

    protected function readPacket(): ?string
    {
        if (!$this->socket) {
            return null;
        }

        $protocol = strtolower($this->connection?->protocol ?: 'tcp');

        if ($protocol === 'tcp') {
            $headerBytes = @fread($this->socket, 8);
            if (!$headerBytes || strlen($headerBytes) < 8) {
                return null;
            }

            $unwrapped = ZKTecoProtocol::unwrapTcp($headerBytes);
            $len = unpack('V', substr($headerBytes, 4, 4))[1];

            $body = '';
            $read = 0;
            while ($read < $len) {
                $chunk = @fread($this->socket, min(1024, $len - $read));
                if (!$chunk) {
                    break;
                }
                $body .= $chunk;
                $read += strlen($chunk);
            }

            return $body;
        }

        return @fread($this->socket, 1024);
    }

    protected function readDataStream(): string
    {
        $buffer = '';

        while ($this->socket && !feof($this->socket)) {
            $packet = $this->readPacket();
            if (!$packet) {
                break;
            }

            $hdr = ZKTecoProtocol::parseHeader($packet);
            if ($hdr['command'] === ZKTecoProtocol::CMD_DATA) {
                $buffer .= $hdr['payload'];
                // Acknowledge received chunk
                $ack = ZKTecoProtocol::createPacket(ZKTecoProtocol::CMD_ACK_OK, $this->sessionId, $hdr['reply_id']);
                $this->writePacket($ack);
            } elseif ($hdr['command'] === ZKTecoProtocol::CMD_ACK_OK) {
                $buffer .= $hdr['payload'];
                break;
            } else {
                break;
            }
        }

        return $buffer;
    }
}
