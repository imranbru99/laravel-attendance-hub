<?php

namespace ImranDevBd\AttendanceHub\Support;

use Carbon\Carbon;
use ImranDevBd\AttendanceHub\DTOs\AttendancePunch;
use ImranDevBd\AttendanceHub\DTOs\DeviceUser;
use ImranDevBd\AttendanceHub\Enums\PunchType;
use ImranDevBd\AttendanceHub\Enums\VerifyMode;

class ZKTecoProtocol
{
    public const CMD_CONNECT = 1000;
    public const CMD_EXIT = 1001;
    public const CMD_ENABLEDEVICE = 1002;
    public const CMD_DISABLEDEVICE = 1003;
    public const CMD_RESTART = 1004;
    public const CMD_POWEROFF = 1005;
    public const CMD_ACK_OK = 2000;
    public const CMD_ACK_ERROR = 2001;
    public const CMD_ACK_DATA = 2002;
    public const CMD_ACK_RETRY = 2003;
    public const CMD_ACK_REPEAT = 2004;
    public const CMD_ACK_UNAUTH = 2005;

    public const CMD_PREPARE_DATA = 1500;
    public const CMD_DATA = 1501;
    public const CMD_FREE_DATA = 1502;

    public const CMD_USERTEMP_RRQ = 9;
    public const CMD_ATTLOG_RRQ = 13;
    public const CMD_CLEAR_DATA = 14;
    public const CMD_CLEAR_ATTLOG = 15;
    public const CMD_DELETE_USER = 18;
    public const CMD_DELETE_USER_TEMP = 19;
    public const CMD_CLEAR_ADMIN = 20;
    public const CMD_USERINFO_RRQ = 8;
    public const CMD_USERTEMP_WRQ = 10;
    public const CMD_USERINFO_WRQ = 72;

    public const CMD_VERSION = 1100;
    public const CMD_DEVICE = 11;
    public const CMD_GET_FREE_SIZES = 1014;

    public const TCP_MAGIC = 0x5050827D; // "PP}" marker (little endian 0x7D 0x82 0x50 0x50)

    /**
     * Calculate 16-bit 1's complement checksum for ZKTeco protocol.
     */
    public static function createChecksum(string $bytes): int
    {
        $l = strlen($bytes);
        $chk = 0;
        $i = 0;

        while ($i < $l - 1) {
            $val = unpack('v', substr($bytes, $i, 2))[1];
            $chk += $val;
            $chk = ($chk & 0xFFFF) + ($chk >> 16);
            $i += 2;
        }

        if ($i < $l) {
            $val = ord($bytes[$i]);
            $chk += $val;
            $chk = ($chk & 0xFFFF) + ($chk >> 16);
        }

        return (~$chk) & 0xFFFF;
    }

    /**
     * Build UDP/payload packet.
     * Header is 8 bytes: Command (2B), Checksum (2B), Session ID (2B), Reply ID (2B) + Payload
     */
    public static function createPacket(int $command, int $sessionId, int $replyId, string $payload = ''): string
    {
        // First build header with 0 checksum
        $buf = pack('vvvv', $command, 0, $sessionId, $replyId) . $payload;
        $chk = self::createChecksum($buf);

        return pack('vvvv', $command, $chk, $sessionId, $replyId) . $payload;
    }

    /**
     * Wrap payload in TCP framing: [Magic 4B: 0x5050827D] [Length 4B little-endian] [Packet]
     */
    public static function wrapTcp(string $packet): string
    {
        $len = strlen($packet);
        $magic = "\x50\x50\x82\x7d";
        $lenBytes = pack('V', $len);

        return $magic . $lenBytes . $packet;
    }

    /**
     * Unwrap TCP packet framing.
     */
    public static function unwrapTcp(string $buffer): array
    {
        if (strlen($buffer) < 8) {
            return ['valid' => false, 'packet' => '', 'length' => 0];
        }

        $magic = substr($buffer, 0, 4);
        if ($magic !== "\x50\x50\x82\x7d") {
            // Some devices might omit magic or use raw UDP format
            return ['valid' => true, 'packet' => $buffer, 'length' => strlen($buffer)];
        }

        $length = unpack('V', substr($buffer, 4, 4))[1];
        $packet = substr($buffer, 8, $length);

        return [
            'valid' => true,
            'packet' => $packet,
            'length' => $length,
        ];
    }

    /**
     * Parse 8-byte response header.
     */
    public static function parseHeader(string $packet): array
    {
        if (strlen($packet) < 8) {
            return [
                'command' => self::CMD_ACK_ERROR,
                'checksum' => 0,
                'session_id' => 0,
                'reply_id' => 0,
                'payload' => '',
            ];
        }

        $hdr = unpack('vcommand/vchecksum/vsession_id/vreply_id', substr($packet, 0, 8));
        $hdr['payload'] = substr($packet, 8);

        return $hdr;
    }

    /**
     * Decode ZKTeco 32-bit timestamp to Carbon instance.
     */
    public static function decodeTime(int $time): Carbon
    {
        $second = $time % 60;
        $time = (int) ($time / 60);

        $minute = $time % 60;
        $time = (int) ($time / 60);

        $hour = $time % 24;
        $time = (int) ($time / 24);

        $day = ($time % 31) + 1;
        $time = (int) ($time / 31);

        $month = ($time % 12) + 1;
        $time = (int) ($time / 12);

        $year = $time + 2000;

        return Carbon::create($year, $month, $day, $hour, $minute, $second);
    }

    /**
     * Encode date/time to ZKTeco 32-bit timestamp.
     */
    public static function encodeTime(Carbon $date): int
    {
        $year = $date->year - 2000;
        $month = $date->month - 1;
        $day = $date->day - 1;
        $hour = $date->hour;
        $min = $date->minute;
        $sec = $date->second;

        return (($year * 12 * 31 + $month * 31 + $day) * 24 + $hour) * 3600 + $min * 60 + $sec;
    }

    /**
     * Parse raw attendance log buffer chunk (supports ZK6 8-byte and ZK8 40-byte records).
     *
     * @return array<AttendancePunch>
     */
    public static function parseAttendanceLogs(string $data, ?string $deviceId = null): array
    {
        $logs = [];
        $length = strlen($data);

        // Check if data is prefixed with size (4 bytes) from command payload
        if ($length > 4 && ($length % 40 !== 0 && $length % 14 !== 0 && $length % 8 !== 0)) {
            $prefixSize = unpack('V', substr($data, 0, 4))[1];
            if ($prefixSize === ($length - 4) || (($length - 4) % 40 === 0) || (($length - 4) % 8 === 0)) {
                $data = substr($data, 4);
                $length -= 4;
            }
        }

        // Detect record size: ZK8 (40 bytes), ZK6 (8 bytes), or Extended (14 bytes)
        $recordSize = 40;
        if ($length % 40 !== 0) {
            if ($length % 14 === 0) {
                $recordSize = 14;
            } elseif ($length % 8 === 0) {
                $recordSize = 8;
            }
        }

        $offset = 0;
        while ($offset + $recordSize <= $length) {
            $record = substr($data, $offset, $recordSize);
            $offset += $recordSize;

            if ($recordSize === 40) {
                // ZK8 format (40 bytes):
                // User PIN: 24 bytes string
                // State: 1 byte
                // Verify mode: 1 byte
                // Timestamp: 4 bytes encoded int
                // WorkCode: 8 bytes
                $userId = trim(substr($record, 0, 24));
                // Remove null characters
                $userId = rtrim(str_replace("\0", '', $userId));
                if (empty($userId)) {
                    continue;
                }

                $state = ord($record[24]);
                $verify = ord($record[25]);
                $timeVal = unpack('V', substr($record, 26, 4))[1];
                $punchedAt = self::decodeTime($timeVal);

                $logs[] = new AttendancePunch(
                    deviceUserId: $userId,
                    punchedAt: $punchedAt,
                    verifyMode: VerifyMode::fromRawCode($verify),
                    punchType: PunchType::fromRawState($state),
                    deviceId: $deviceId,
                    rawPayload: ['raw_hex' => bin2hex($record), 'zk_format' => 'ZK8']
                );
            } elseif ($recordSize === 8) {
                // ZK6 format (8 bytes):
                // User ID: 2 bytes unsigned short
                // Timestamp: 4 bytes encoded
                // State: 1 byte
                // Verify: 1 byte
                $unpacked = unpack('vuser/Vtime/Cstate/Cverify', $record);
                $userId = (string) $unpacked['user'];
                $punchedAt = self::decodeTime($unpacked['time']);

                $logs[] = new AttendancePunch(
                    deviceUserId: $userId,
                    punchedAt: $punchedAt,
                    verifyMode: VerifyMode::fromRawCode($unpacked['verify']),
                    punchType: PunchType::fromRawState($unpacked['state']),
                    deviceId: $deviceId,
                    rawPayload: ['raw_hex' => bin2hex($record), 'zk_format' => 'ZK6']
                );
            } elseif ($recordSize === 14) {
                // 14-byte format
                $unpacked = unpack('vuser/Vtime/Cstate/Cverify', substr($record, 0, 8));
                $userId = (string) $unpacked['user'];
                $punchedAt = self::decodeTime($unpacked['time']);

                $logs[] = new AttendancePunch(
                    deviceUserId: $userId,
                    punchedAt: $punchedAt,
                    verifyMode: VerifyMode::fromRawCode($unpacked['verify']),
                    punchType: PunchType::fromRawState($unpacked['state']),
                    deviceId: $deviceId,
                    rawPayload: ['raw_hex' => bin2hex($record), 'zk_format' => 'ZK14']
                );
            }
        }

        return $logs;
    }

    /**
     * Parse raw user table data (ZK6 28-byte or ZK8 72-byte format).
     *
     * @return array<DeviceUser>
     */
    public static function parseUsers(string $data): array
    {
        $users = [];
        $length = strlen($data);

        if ($length > 4 && ($length % 72 !== 0 && $length % 28 !== 0)) {
            $prefixSize = unpack('V', substr($data, 0, 4))[1];
            if ($prefixSize === ($length - 4) || (($length - 4) % 72 === 0) || (($length - 4) % 28 === 0)) {
                $data = substr($data, 4);
                $length -= 4;
            }
        }

        $recordSize = 72;
        if ($length % 72 !== 0 && $length % 28 === 0) {
            $recordSize = 28;
        }

        $offset = 0;
        while ($offset + $recordSize <= $length) {
            $record = substr($data, $offset, $recordSize);
            $offset += $recordSize;

            if ($recordSize === 72) {
                // ZK8 User (72 bytes):
                // Card/PIN: 24 bytes string
                // Name: 24 bytes string
                // Password: 8 bytes
                // Role: 1 byte
                // Enabled: 1 byte
                // Card: 4 bytes integer
                $uid = (string) unpack('v', substr($record, 0, 2))[1];
                $role = ord($record[2]);
                $password = rtrim(str_replace("\0", '', substr($record, 3, 8)));
                $name = rtrim(str_replace("\0", '', substr($record, 11, 24)));
                $card = (string) unpack('V', substr($record, 35, 4))[1];
                $userId = rtrim(str_replace("\0", '', substr($record, 48, 24)));

                if (empty($userId)) {
                    $userId = $uid;
                }

                $users[] = new DeviceUser(
                    uid: $uid,
                    userId: $userId,
                    name: $name ?: "User {$userId}",
                    role: $role,
                    password: $password ?: null,
                    card: $card !== '0' ? $card : null,
                    enabled: true,
                    rawPayload: ['format' => 'ZK8']
                );
            } elseif ($recordSize === 28) {
                // ZK6 User (28 bytes):
                // UID: 2 bytes
                // Role: 2 bytes
                // Password: 8 bytes
                // Name: 12 bytes
                // Card: 4 bytes
                $uid = (string) unpack('v', substr($record, 0, 2))[1];
                $role = unpack('v', substr($record, 2, 2))[1];
                $password = rtrim(str_replace("\0", '', substr($record, 4, 8)));
                $name = rtrim(str_replace("\0", '', substr($record, 12, 12)));
                $card = (string) unpack('V', substr($record, 24, 4))[1];

                $users[] = new DeviceUser(
                    uid: $uid,
                    userId: $uid,
                    name: $name ?: "User {$uid}",
                    role: $role,
                    password: $password ?: null,
                    card: $card !== '0' ? $card : null,
                    enabled: true,
                    rawPayload: ['format' => 'ZK6']
                );
            }
        }

        return $users;
    }
}
