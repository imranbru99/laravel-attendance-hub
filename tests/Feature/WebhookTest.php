<?php

namespace ImranDevBd\AttendanceHub\Tests\Feature;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use ImranDevBd\AttendanceHub\Jobs\DispatchAttendanceWebhook;
use ImranDevBd\AttendanceHub\Models\AttendanceLog;
use ImranDevBd\AttendanceHub\Tests\TestCase;

class WebhookTest extends TestCase
{
    public function test_webhook_dispatches_with_hmac_signature(): void
    {
        Http::fake([
            'https://hrms.example.com/api/attendance' => Http::response(['status' => 'received'], 200),
        ]);

        config([
            'attendance-hub.webhooks.secret' => 'super-secret-key-123',
            'attendance-hub.webhooks.endpoints' => ['https://hrms.example.com/api/attendance'],
        ]);

        $log = AttendanceLog::create([
            'device_user_id' => '888',
            'employee_id' => 'EMP-888',
            'punched_at' => Carbon::now(),
            'verify_mode' => 'fingerprint',
            'punch_type' => 'check_in',
            'punch_hash' => 'dummy_hash_888',
        ]);

        $job = new DispatchAttendanceWebhook($log);
        $job->handle();

        Http::assertSent(function ($request) {
            return $request->url() === 'https://hrms.example.com/api/attendance' &&
                $request->hasHeader('X-Attendance-Signature') &&
                !empty($request->header('X-Attendance-Signature')[0]);
        });
    }
}
