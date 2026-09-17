<?php

namespace ImranDevBd\AttendanceHub\Tests\Unit;

use Carbon\Carbon;
use ImranDevBd\AttendanceHub\Enums\PunchType;
use ImranDevBd\AttendanceHub\Enums\VerifyMode;
use ImranDevBd\AttendanceHub\Support\ZKTecoProtocol;
use PHPUnit\Framework\TestCase;

class ProtocolTest extends TestCase
{
    public function test_checksum_calculation(): void
    {
        $payload = pack('vvvv', 1000, 0, 10, 20);
        $checksum = ZKTecoProtocol::createChecksum($payload);

        $this->assertIsInt($checksum);
        $this->assertGreaterThan(0, $checksum);
    }

    public function test_tcp_wrap_and_unwrap(): void
    {
        $packet = "TEST_PAYLOAD_12345";
        $wrapped = ZKTecoProtocol::wrapTcp($packet);

        $this->assertStringStartsWith("\x50\x50\x82\x7d", $wrapped);

        $unwrapped = ZKTecoProtocol::unwrapTcp($wrapped);
        $this->assertTrue($unwrapped['valid']);
        $this->assertEquals($packet, $unwrapped['packet']);
        $this->assertEquals(strlen($packet), $unwrapped['length']);
    }

    public function test_packet_creation_and_header_parsing(): void
    {
        $packet = ZKTecoProtocol::createPacket(
            command: ZKTecoProtocol::CMD_CONNECT,
            sessionId: 1234,
            replyId: 5678,
            payload: 'EXTRA_DATA'
        );

        $header = ZKTecoProtocol::parseHeader($packet);

        $this->assertEquals(ZKTecoProtocol::CMD_CONNECT, $header['command']);
        $this->assertEquals(1234, $header['session_id']);
        $this->assertEquals(5678, $header['reply_id']);
        $this->assertEquals('EXTRA_DATA', $header['payload']);
    }

    public function test_time_encoding_and_decoding(): void
    {
        $original = Carbon::create(2026, 9, 17, 8, 30, 45);
        $encoded = ZKTecoProtocol::encodeTime($original);
        $decoded = ZKTecoProtocol::decodeTime($encoded);

        $this->assertEquals($original->year, $decoded->year);
        $this->assertEquals($original->month, $decoded->month);
        $this->assertEquals($original->day, $decoded->day);
        $this->assertEquals($original->hour, $decoded->hour);
        $this->assertEquals($original->minute, $decoded->minute);
        $this->assertEquals($original->second, $decoded->second);
    }

    public function test_parse_zk8_attendance_records(): void
    {
        // 40 bytes per record
        $userId = "1001\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0"; // 24 bytes
        $state = chr(0); // check-in
        $verify = chr(1); // fingerprint
        $timeVal = pack('V', ZKTecoProtocol::encodeTime(Carbon::create(2026, 9, 17, 9, 0, 0)));
        $workCode = str_repeat("\0", 10);
        $record = substr($userId, 0, 24) . $state . $verify . $timeVal . $workCode; // 40 bytes

        $punches = ZKTecoProtocol::parseAttendanceLogs($record, '192.168.1.100');

        $this->assertCount(1, $punches);
        $this->assertEquals('1001', $punches[0]->deviceUserId);
        $this->assertEquals(VerifyMode::FINGERPRINT, $punches[0]->verifyMode);
        $this->assertEquals(PunchType::CHECK_IN, $punches[0]->punchType);
        $this->assertEquals('2026-09-17 09:00:00', $punches[0]->punchedAt->format('Y-m-d H:i:s'));
    }

    public function test_parse_zk6_attendance_records(): void
    {
        // 8 bytes per record: short user, long time, char state, char verify
        $timeVal = ZKTecoProtocol::encodeTime(Carbon::create(2026, 9, 17, 17, 30, 0));
        $record = pack('vVCC', 42, $timeVal, 1, 3); // user 42, check-out (1), card (3)

        $punches = ZKTecoProtocol::parseAttendanceLogs($record, '192.168.1.101');

        $this->assertCount(1, $punches);
        $this->assertEquals('42', $punches[0]->deviceUserId);
        $this->assertEquals(VerifyMode::CARD, $punches[0]->verifyMode);
        $this->assertEquals(PunchType::CHECK_OUT, $punches[0]->punchType);
        $this->assertEquals('2026-09-17 17:30:00', $punches[0]->punchedAt->format('Y-m-d H:i:s'));
    }
}
