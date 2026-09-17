<?php

use Illuminate\Support\Facades\Route;
use ImranDevBd\AttendanceHub\Http\Controllers\AdmsListenerController;
use ImranDevBd\AttendanceHub\Http\Controllers\VirtualAttendanceController;
use ImranDevBd\AttendanceHub\Http\Controllers\WebhookBridgeController;

Route::group(['middleware' => config('attendance-hub.adms.middleware', ['api'])], function () {
    // ADMS / iClock hardware endpoints (must match terminal firmware path conventions)
    Route::match(['get', 'post'], '/iclock/cdata', [AdmsListenerController::class, 'cdata'])
        ->name('attendance-hub.adms.cdata');

    Route::get('/iclock/getrequest', [AdmsListenerController::class, 'getrequest'])
        ->name('attendance-hub.adms.getrequest');

    Route::post('/iclock/devicecmd', [AdmsListenerController::class, 'devicecmd'])
        ->name('attendance-hub.adms.devicecmd');

    // Virtual Attendance Check-In endpoint
    Route::post('/api/attendance/virtual/check-in', [VirtualAttendanceController::class, 'checkIn'])
        ->name('attendance-hub.virtual.check-in');

    // Generic IoT / Wiegand Webhook Bridge endpoint
    Route::post('/api/attendance/webhook-bridge', [WebhookBridgeController::class, 'handle'])
        ->name('attendance-hub.bridge');
});
