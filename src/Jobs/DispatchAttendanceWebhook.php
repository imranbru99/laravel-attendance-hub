<?php

namespace ImranDevBd\AttendanceHub\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use ImranDevBd\AttendanceHub\Models\AttendanceLog;

class DispatchAttendanceWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 15;

    public function __construct(
        public readonly AttendanceLog $log
    ) {}

    public function handle(): void
    {
        $endpoints = config('attendance-hub.webhooks.endpoints', []);
        $secret = config('attendance-hub.webhooks.secret', '');

        if (empty($endpoints)) {
            return;
        }

        $payload = [
            'event' => 'attendance.recorded',
            'timestamp' => now()->toIso8601String(),
            'data' => [
                'id' => $this->log->id,
                'device_id' => $this->log->device_id,
                'device_user_id' => $this->log->device_user_id,
                'employee_id' => $this->log->employee_id,
                'punched_at' => $this->log->punched_at->toIso8601String(),
                'verify_mode' => $this->log->verify_mode,
                'punch_type' => $this->log->punch_type,
                'location' => $this->log->location,
            ],
        ];

        $jsonPayload = json_encode($payload);
        $signature = hash_hmac('sha256', $jsonPayload, $secret);

        foreach ($endpoints as $url) {
            try {
                Http::timeout(5)
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                        'X-Attendance-Signature' => $signature,
                    ])
                    ->post($url, $payload);
            } catch (\Throwable $e) {
                Log::warning("[AttendanceHub Webhook] Failed to deliver webhook to {$url}: " . $e->getMessage());
            }
        }
    }
}
