<?php

namespace ImranDevBd\AttendanceHub\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use ImranDevBd\AttendanceHub\Drivers\AdmsPushDriver;
use ImranDevBd\AttendanceHub\Events\DeviceCameOnline;
use ImranDevBd\AttendanceHub\Models\AttendanceDevice;
use ImranDevBd\AttendanceHub\Support\AttendanceNormalizer;

class AdmsListenerController extends Controller
{
    public function __construct(
        protected AttendanceNormalizer $normalizer = new AttendanceNormalizer()
    ) {}

    /**
     * Handshake and data ingestion endpoint: /iclock/cdata
     */
    public function cdata(Request $request): Response
    {
        $serial = $request->query('SN') ?? $request->header('X-Serial-Number');

        // Resolve device model if exists
        $device = null;
        if ($serial) {
            $device = AttendanceDevice::where('serial_number', $serial)->first();
            if ($device) {
                if (!$device->isOnline()) {
                    $device->markOnline();
                    event(new DeviceCameOnline($device));
                } else {
                    $device->update(['last_seen_at' => now()]);
                }
            }
        }

        if ($request->isMethod('get')) {
            // Heartbeat / Config handshake
            return $this->handleHeartbeat($serial, $request);
        }

        // POST: Incoming Attendance / Operational Log Table
        return $this->handleDataPush($serial, $device, $request);
    }

    /**
     * Respond to GET /iclock/cdata heartbeat with ADMS device configuration.
     */
    protected function handleHeartbeat(?string $serial, Request $request): Response
    {
        $sn = $serial ?: 'UNKNOWN';
        $stamp = time();
        $delay = (int) config('attendance-hub.adms.push_interval_seconds', 30);
        $errorDelay = (int) config('attendance-hub.adms.error_delay_seconds', 60);

        $body = "GET OPTION FROM: {$sn}\r\n"
            . "Stamp={$stamp}\r\n"
            . "OpStamp={$stamp}\r\n"
            . "ErrorDelay={$errorDelay}\r\n"
            . "Delay={$delay}\r\n"
            . "ResStamp={$stamp}\r\n"
            . "TransTimes=00:00;14:05\r\n"
            . "TransInterval=1\r\n"
            . "TransFlag=TransData AttLog\r\n"
            . "TimeZone=UTC\r\n"
            . "Realtime=1\r\n"
            . "Encrypt=0\r\n";

        return response($body, 200, ['Content-Type' => 'text/plain']);
    }

    /**
     * Process incoming POST /iclock/cdata data buffer.
     */
    protected function handleDataPush(?string $serial, ?AttendanceDevice $device, Request $request): Response
    {
        $table = strtoupper($request->query('table', 'ATTLOG'));
        $content = $request->getContent();

        if (empty($content)) {
            return response('OK', 200, ['Content-Type' => 'text/plain']);
        }

        if ($table === 'ATTLOG') {
            $punches = AdmsPushDriver::parsePushPayload(
                payload: $content,
                serial: $serial,
                deviceId: $device?->id ? (string) $device->id : null
            );

            $saved = $this->normalizer->recordMany($punches, $device);

            if ($device) {
                $device->update(['last_synced_at' => now()]);
            }

            $count = $saved->count();
            return response("OK: {$count}", 200, ['Content-Type' => 'text/plain']);
        }

        return response('OK', 200, ['Content-Type' => 'text/plain']);
    }

    /**
     * Dispatch queued device commands: /iclock/getrequest
     */
    public function getrequest(Request $request): Response
    {
        $serial = $request->query('SN');
        if (!$serial) {
            return response('OK', 200, ['Content-Type' => 'text/plain']);
        }

        $commands = AdmsPushDriver::getPendingCommands($serial);
        if (empty($commands)) {
            return response('OK', 200, ['Content-Type' => 'text/plain']);
        }

        $output = '';
        foreach ($commands as $cmd) {
            $output .= "C:{$cmd['id']}:{$cmd['command']}\r\n";
        }

        return response(trim($output), 200, ['Content-Type' => 'text/plain']);
    }

    /**
     * Acknowledge command execution results: /iclock/devicecmd
     */
    public function devicecmd(Request $request): Response
    {
        // Device sends ID=...&Return=0
        return response('OK', 200, ['Content-Type' => 'text/plain']);
    }
}
