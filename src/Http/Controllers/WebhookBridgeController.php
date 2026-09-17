<?php

namespace ImranDevBd\AttendanceHub\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use ImranDevBd\AttendanceHub\Drivers\WebhookBridgeDriver;

class WebhookBridgeController extends Controller
{
    public function __construct(
        protected WebhookBridgeDriver $driver = new WebhookBridgeDriver()
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $data = $request->all();

        $log = $this->driver->handleBridgePayload($data);

        return response()->json([
            'success' => true,
            'message' => 'Punch recorded via hardware bridge.',
            'data' => $log,
        ], 201);
    }
}
