<?php

namespace ImranDevBd\AttendanceHub\Drivers;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use ImranDevBd\AttendanceHub\DTOs\AttendancePunch;
use ImranDevBd\AttendanceHub\DTOs\DeviceInfo;
use ImranDevBd\AttendanceHub\Enums\PunchType;
use ImranDevBd\AttendanceHub\Enums\VerifyMode;
use ImranDevBd\AttendanceHub\Exceptions\DeviceAuthenticationException;
use ImranDevBd\AttendanceHub\Models\AttendanceLog;
use ImranDevBd\AttendanceHub\Support\AttendanceNormalizer;

class WebhookBridgeDriver extends AbstractDeviceDriver
{
    public function getDeviceInfo(): DeviceInfo
    {
        return new DeviceInfo(
            serialNumber: $this->connection?->serialNumber ?? 'WIEGAND-BRIDGE-01',
            deviceName: 'IoT Wiegand / Webhook Bridge Relay',
            platform: 'Generic IoT Bridge (ESP32 / Raspberry Pi / Custom)'
        );
    }

    public function ping(): bool
    {
        return true;
    }

    public function supportsPush(): bool
    {
        return true;
    }

    /**
     * Process an incoming punch from a Wiegand / IoT bridge relay.
     *
     * @param array{
     *     card_number?: string|null,
     *     pin?: string|null,
     *     user_id?: string|null,
     *     employee_id?: string|null,
     *     reader_id?: string|null,
     *     facility_code?: string|null,
     *     timestamp?: string|null,
     *     punch_type?: string|null,
     *     token?: string|null,
     *     raw_bits?: string|null
     * } $payload
     */
    public function handleBridgePayload(array $payload): AttendanceLog
    {
        // Token verification if secret is configured
        $configuredToken = config('attendance-hub.bridge.token');
        if (!empty($configuredToken)) {
            $providedToken = $payload['token'] ?? request()->bearerToken() ?? request()->header('X-Bridge-Token');
            if (!hash_equals((string) $configuredToken, (string) $providedToken)) {
                throw DeviceAuthenticationException::failed('webhook_bridge', 'Invalid or missing bridge token.');
            }
        }

        $userId = (string) ($payload['user_id'] ?? $payload['card_number'] ?? $payload['pin'] ?? $payload['employee_id'] ?? '');
        if (empty($userId)) {
            throw ValidationException::withMessages([
                'user_id' => 'A valid card number, PIN, or user ID is required from the bridge.',
            ]);
        }

        $punchedAt = !empty($payload['timestamp'])
            ? Carbon::parse($payload['timestamp'])
            : now();

        $verifyMode = !empty($payload['pin']) ? VerifyMode::PIN : VerifyMode::CARD;
        $punchType = isset($payload['punch_type'])
            ? PunchType::tryFrom($payload['punch_type']) ?? PunchType::AUTO
            : PunchType::AUTO;

        $punch = new AttendancePunch(
            deviceUserId: $userId,
            punchedAt: $punchedAt,
            verifyMode: $verifyMode,
            punchType: $punchType,
            deviceId: $payload['reader_id'] ?? 'wiegand_bridge',
            employeeId: $payload['employee_id'] ?? null,
            workCode: $payload['facility_code'] ?? null,
            rawPayload: [
                'source' => 'wiegand_webhook_bridge',
                'raw_bits' => $payload['raw_bits'] ?? null,
                'reader_id' => $payload['reader_id'] ?? null,
            ]
        );

        $normalizer = new AttendanceNormalizer();
        return $normalizer->record($punch);
    }
}
