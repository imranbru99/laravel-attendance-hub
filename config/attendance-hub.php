<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Attendance Hardware Provider
    |--------------------------------------------------------------------------
    |
    | Supported providers: "zkteco", "adms", "hikvision", "suprema", "dahua", "virtual"
    |
    */
    'default_provider' => env('ATTENDANCE_DEFAULT_PROVIDER', 'zkteco'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone for Attendance Logs
    |--------------------------------------------------------------------------
    |
    | Timestamps pulled from devices will be converted to this timezone
    | when saved into the database.
    |
    */
    'timezone' => env('ATTENDANCE_TIMEZONE', config('app.timezone', 'UTC')),

    /*
    |--------------------------------------------------------------------------
    | Device Local Timezone
    |--------------------------------------------------------------------------
    |
    | Default timezone configured on the physical devices if unspecified.
    |
    */
    'device_timezone' => env('ATTENDANCE_DEVICE_TIMEZONE', 'UTC'),

    /*
    |--------------------------------------------------------------------------
    | ADMS / iClock HTTP Push Configuration
    |--------------------------------------------------------------------------
    |
    | Built-in push endpoint parameters for ZKTeco, eSSL, Anviz, and Realtime.
    |
    */
    'adms' => [
        'enabled' => env('ATTENDANCE_ADMS_ENABLED', true),
        'middleware' => ['api'],
        'push_interval_seconds' => env('ATTENDANCE_ADMS_INTERVAL', 30),
        'error_delay_seconds' => env('ATTENDANCE_ADMS_ERROR_DELAY', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Generic IoT / Wiegand Webhook Bridge Configuration
    |--------------------------------------------------------------------------
    |
    | Optional secret token required from ESP32, Raspberry Pi, or relay bridges.
    |
    */
    'bridge' => [
        'token' => env('ATTENDANCE_BRIDGE_TOKEN', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sync Retry, Backoff & Rate-Limiting Knobs
    |--------------------------------------------------------------------------
    |
    | Prevents hammering budget terminals and handles network backoff cleanly.
    |
    */
    'retry' => [
        'max_attempts' => env('ATTENDANCE_MAX_ATTEMPTS', 3),
        'backoff_seconds' => env('ATTENDANCE_BACKOFF_SECONDS', 30),
        'per_device_poll_interval' => env('ATTENDANCE_POLL_INTERVAL', 300), // 5 minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Virtual (Mobile / Kiosk / GPS / QR) Check-in Settings
    |--------------------------------------------------------------------------
    */
    'virtual' => [
        'default_radius_meters' => env('ATTENDANCE_GEOFENCE_RADIUS', 100),
        'qr_validity_seconds' => env('ATTENDANCE_QR_VALIDITY', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Punch Processing Pipeline
    |--------------------------------------------------------------------------
    |
    | Middleware pipes executed for every punch before database persistence.
    |
    */
    'pipeline' => [
        'debounce_minutes' => env('ATTENDANCE_DEBOUNCE_MINUTES', 2),
        'anti_passback' => env('ATTENDANCE_ANTI_PASSBACK', false),
        'pipes' => [
            \ImranDevBd\AttendanceHub\Pipes\DebounceRapidPunches::class,
            \ImranDevBd\AttendanceHub\Pipes\AntiPassbackCheck::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Shift & Working Hours Configuration
    |--------------------------------------------------------------------------
    |
    | Used by AttendanceCalculator to evaluate late minutes, overtime, and status.
    |
    */
    'shifts' => [
        'default' => [
            'name' => 'Standard Office Shift',
            'start_time' => env('ATTENDANCE_SHIFT_START', '09:00'),
            'end_time' => env('ATTENDANCE_SHIFT_END', '18:00'),
            'grace_period_minutes' => env('ATTENDANCE_GRACE_MINUTES', 15),
            'half_day_threshold_minutes' => env('ATTENDANCE_HALF_DAY_MINUTES', 240),
            'full_day_threshold_minutes' => env('ATTENDANCE_FULL_DAY_MINUTES', 480),
            'break_duration_minutes' => env('ATTENDANCE_BREAK_MINUTES', 60),
            'allow_overtime' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Multi-Tenancy Scoping Configuration
    |--------------------------------------------------------------------------
    |
    | Automatically scopes devices, logs, and maps to the current tenant ID.
    |
    */
    'tenancy' => [
        'enabled' => env('ATTENDANCE_TENANCY_ENABLED', false),
        'tenant_resolver' => null, // e.g. fn() => auth()->user()?->tenant_id
    ],

    /*
    |--------------------------------------------------------------------------
    | Outgoing Attendance Webhooks
    |--------------------------------------------------------------------------
    |
    | Dispatches HMAC-signed HTTP POST webhooks to external HRMS or ERP endpoints.
    |
    */
    'webhooks' => [
        'enabled' => env('ATTENDANCE_WEBHOOKS_ENABLED', false),
        'secret' => env('ATTENDANCE_WEBHOOK_SECRET', ''),
        'endpoints' => array_filter(explode(',', env('ATTENDANCE_WEBHOOK_ENDPOINTS', ''))),
    ],

    /*
    |--------------------------------------------------------------------------
    | Provider Specific Connection Defaults
    |--------------------------------------------------------------------------
    */
    'providers' => [
        'zkteco' => [
            'port' => 4370,
            'protocol' => 'tcp',
            'timeout' => 5,
        ],
        'hikvision' => [
            'port' => 80,
            'protocol' => 'http',
            'timeout' => 5,
        ],
        'suprema' => [
            'port' => 443,
            'protocol' => 'https',
            'timeout' => 5,
        ],
        'dahua' => [
            'port' => 80,
            'protocol' => 'http',
            'timeout' => 5,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User-Defined Custom Hardware Models & Providers
    |--------------------------------------------------------------------------
    |
    | Register unlisted or proprietary hardware models and map them to drivers.
    | Example:
    |   'XYZ-9000' => [
    |       'provider' => 'MyBrand',
    |       'driver' => 'zkteco', // or 'webhook_bridge', 'hikvision', or a custom extended driver
    |       'port' => 4370,
    |       'fp' => true,
    |       'face' => true,
    |   ],
    |
    */
    'custom_models' => [
        //
    ],

    /*
    |--------------------------------------------------------------------------
    | Filament Admin Panel Integration
    |--------------------------------------------------------------------------
    |
    | Enable or customize Filament v3 resource registration.
    |
    */
    'filament' => [
        'enabled' => env('ATTENDANCE_FILAMENT_ENABLED', true),
        'navigation_group' => 'Attendance Management',
        'navigation_sort' => 10,
    ],

];
