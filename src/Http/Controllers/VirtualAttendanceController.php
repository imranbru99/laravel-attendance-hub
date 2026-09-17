<?php

namespace ImranDevBd\AttendanceHub\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use ImranDevBd\AttendanceHub\Drivers\VirtualDriver;

class VirtualAttendanceController extends Controller
{
    public function __construct(
        protected VirtualDriver $driver = new VirtualDriver()
    ) {}

    /**
     * Submit a virtual punch from mobile, kiosk, or web app.
     */
    public function checkIn(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => 'required|string',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'target_lat' => 'nullable|numeric',
            'target_lng' => 'nullable|numeric',
            'radius_meters' => 'nullable|integer',
            'method' => 'nullable|string', // gps, qr, selfie
            'punch_type' => 'nullable|string', // check_in, check_out, break_in, break_out
            'qr_token' => 'nullable|string',
            'photo_url' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        $log = $this->driver->recordCheckIn($validated);

        return response()->json([
            'success' => true,
            'message' => 'Attendance recorded successfully.',
            'data' => $log,
        ], 201);
    }
}
