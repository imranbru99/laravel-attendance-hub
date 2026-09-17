# laravel-attendance-hub

<p align="center">
  <img src="https://raw.githubusercontent.com/imrandevbd/laravel-attendance-hub/main/art/banner.png" alt="Laravel Attendance Hub Banner" width="100%" style="border-radius: 8px; margin-bottom: 20px;" onerror="this.style.display='none'">
</p>

<p align="center">
  <strong>One driver interface. Every attendance device. Auto-detected, auto-connected.</strong>
</p>

<p align="center">
  <a href="https://packagist.org/packages/imrandevbd/laravel-attendance-hub"><img src="https://img.shields.io/packagist/v/imrandevbd/laravel-attendance-hub.svg?style=flat-square" alt="Latest Version on Packagist"></a>
  <a href="https://github.com/imrandevbd/laravel-attendance-hub/actions"><img src="https://img.shields.io/github/actions/workflow/status/imrandevbd/laravel-attendance-hub/run-tests.yml?branch=main&label=tests&style=flat-square" alt="Tests"></a>
  <a href="https://packagist.org/packages/imrandevbd/laravel-attendance-hub"><img src="https://img.shields.io/packagist/dt/imrandevbd/laravel-attendance-hub.svg?style=flat-square" alt="Total Downloads"></a>
  <a href="https://packagist.org/packages/imrandevbd/laravel-attendance-hub"><img src="https://img.shields.io/packagist/php-v/imrandevbd/laravel-attendance-hub.svg?style=flat-square" alt="PHP Version"></a>
  <a href="https://packagist.org/packages/imrandevbd/laravel-attendance-hub"><img src="https://img.shields.io/packagist/l/imrandevbd/laravel-attendance-hub.svg?style=flat-square" alt="License"></a>
</p>

---

## 📖 Overview

**`laravel-attendance-hub`** is the **`laravel-ai-hub`** of attendance and access control hardware. Rather than writing brittle, vendor-specific socket code or fragmented HTTP controllers for each brand, this package provides a clean, unified driver abstraction.

Pick a **provider** (device brand), configure connection details, and pull or push attendance logs using **one consistent API** — with full support for zero-hardware testing, biometric template encryption at rest, automatic shift calculations, and multi-tenancy out of the box.

### Supported Hardware Families (10 Brands & 166+ Models)

> 📖 **Full Catalog Matrix**: See [**docs/device-catalog.md**](docs/device-catalog.md) for the complete 166-model compatibility table with biometrics, protocols, default ports, and market availability.

| Brand / Family | Provider Key | Models Supported | Protocol | Mode | Supported Features |
|---|---|:---:|---|---|---|
| **ZKTeco** | `zkteco` | **20 models** (UA860, MB20, K40, SpeedFace-V5L...) | Native TCP/UDP (`:4370`) | LAN Pull / Stream | Logs, Users, Fingerprints, Face, Palm, Live Capture |
| **Hikvision** | `hikvision` | **15 models** (DS-K1A8503, DS-K1T341, DS-K1T671...) | ISAPI REST (HTTP/HTTPS) | LAN / HTTP(S) | Card, Face, Fingerprint Logs, User Record CRUD |
| **Anviz** | `anviz` | **21 models** (C2 Pro, W1, A350, FaceDeep, FacePass...) | CrossChex B-comm / ADMS | WAN / Cloud Push | Fingerprint, AI Face Recognition, Cloud Ingestion |
| **FingerTec** | `fingertec` | **18 models** (TA300, TA500, R2, Face ID 2/3/4...) | Native TCP/UDP (`:4370`) | LAN / ADMS | Multi-Biometric Fingerprint & Face Terminals |
| **eSSL** | `essl` | **22 models** (X990, iClock990, F18, Aiface Vesta...) | ADMS Push / TCP (`:4370`) | WAN Push / LAN | South Asia Market Leader, High-Capacity Factory Readers |
| **VIRDI** | `virdi` | **12 models** (AC-5000, AC-6000, UBio-X Face...) | TCP/IP (`:9870`) / Wiegand | LAN / IoT Bridge | IP65 Waterproof, Vandal-Resistant Enterprise Hardware |
| **Suprema** | `suprema` | **18 models** (BioStation 2/3, BioEntry W2, FaceStation...) | BioStar 2 REST API (`:443`) | Cloud / On-Prem | High-Throughput Government & Corporate Grade |
| **Deli** | `deli` | **10 models** (E3960, ES152, ES161, ES172...) | TCP/UDP (`:4370`) / USB | LAN Pull / Push | Cost-Effective Retail & Small Business Terminals |
| **Granding** | `granding` | **15 models** (GT100, GT800, FA1, FacePro...) | TCP/UDP (`:4370`) / ADMS | LAN / Cloud Push | Hybrid Fingerprint, Touchless Palm & Face Hardware |
| **Soyal** | `soyal` | **15 models** (AR-721H, AR-725E, AR-837EF...) | RS-485 / TCP / Wiegand | Relay / IoT Bridge | Access Control Panels, Elevator & Turnstile Control |
| **Generic Webhook / Bridge** | `webhook_bridge` | Any ESP32 / Raspberry Pi | Token-Signed JSON POST | WAN / IoT Bridge | Wiegand 26/34-bit RFID panels & custom microcontrollers |
| **Virtual (Mobile / QR)** | `virtual` | Mobile Apps / Tablets | Geofence / QR Token | REST API | Haversine Boundary Validation, Rotating QR Tokens |
| **Hardware Simulator / Fake**| `fake` | In-Memory Simulation | Unit & CI Tests | Testing | Zero-Hardware Testing, Instant Punch Simulation |

---

## ⚖️ How It Compares

Why adopt `laravel-attendance-hub` over fragmented single-vendor packages?

| Feature / Capability | Standalone ZKTeco Packages | Standalone Hikvision Packages | Custom Scripts | `laravel-attendance-hub` |
|---|:---:|:---:|:---:|:---:|
| **Multi-Vendor Unified API** | ❌ (ZKTeco only) | ❌ (Hikvision only) | ❌ (Ad-hoc) | ✅ **ZKTeco, Hikvision, Suprema, Dahua, ADMS, Wiegand** |
| **Zero-Hardware Mock (`AttendanceHub::fake()`)** | ❌ | ❌ | ❌ | ✅ **Full mock driver with assertions & failure simulation** |
| **CLI Seed Simulator (`attendance:simulate`)** | ❌ | ❌ | ❌ | ✅ **Generates realistic employees & punches for instant demo** |
| **Biometric & Credential Encryption at Rest** | ❌ (Plaintext) | ❌ (Plaintext) | ❌ | ✅ **GDPR Article 9 & BIPA compliant (`encrypted` casts)** |
| **Shift & Hours Calculator (`AttendanceCalculator`)** | ❌ | ❌ | ❌ | ✅ **Late, Overtime, Half-Day, Working Hours Engine** |
| **Anti-Passback & Rapid Double-Tap Debouncing** | ❌ | ❌ | ❌ | ✅ **Configurable 2-minute debounce pipeline** |
| **Cloud ADMS Listener (NAT/Firewall-free)** | ⚠️ (Requires fork) | ❌ | ❌ | ✅ **Built-in `/iclock/cdata` endpoints + command queue** |
| **Generic IoT / Wiegand Bridge Support** | ❌ | ❌ | ❌ | ✅ **Signed webhook endpoint for ESP32/RPi relays** |
| **Multi-Tenancy Support** | ❌ | ❌ | ❌ | ✅ **Native tenant scoping (`BelongsToTenant`)** |
| **Real-time HMAC Webhooks** | ❌ | ❌ | ❌ | ✅ **Dispatches signed webhooks to external HRMS/ERP** |
| **Filament v3 Management Panel** | ❌ | ❌ | ❌ | ✅ **Pre-built devices & logs viewer plugin** |

---

## 🚀 Key Modern Architecture Features

1. **Unified Driver Engine**: One contract (`DeviceDriverInterface`) handles LAN sockets, WAN push, REST APIs, IoT bridges, and virtual punches.
2. **Zero-Hardware Simulation (`AttendanceHub::fake()`)**: Evaluate the complete workflow, run CI tests, and assert terminal interactions without owning physical hardware.
3. **GDPR & Biometric Privacy by Design**: All device passwords, communication keys, and raw biometric templates are encrypted at rest using Laravel's cryptographic store.
4. **Built-in ADMS Cloud Listener**: Zero-config endpoints (`GET/POST /iclock/cdata`) receive logs from remote branches behind NAT/firewalls without VPNs.
5. **Generic IoT / Wiegand Webhook Bridge**: Ingest badge scans from cheap ESP32 or Raspberry Pi microcontrollers bridging legacy Wiegand 26/34-bit door panels.
6. **Shift & Working Hours Interpreter (`AttendanceCalculator`)**: Resolves raw timestamps into business insights: **First-In**, **Last-Out**, **Late Minutes**, **Early Departure**, **Overtime**, and Status (**Present**, **Late**, **Half-Day**, **Absent**).
7. **Biometric Template Vault (`DeviceTemplateVault`)**: Backup and restore fingerprint and face templates to migrate terminals without re-enrolling staff.
8. **Punch Pipeline Middleware**: Built-in anti-passback checks and rapid-tap debouncing (filters accidental double-scans within 2 minutes).
9. **Multi-Tenancy Support**: Native tenant scoping (`BelongsToTenant`) for multi-tenant SaaS applications.
10. **HMAC-Signed Outgoing Webhooks**: Real-time notifications dispatched to external HRMS/ERP systems (e.g., SAP, Odoo, Slack).
11. **Network Auto-Detection**: Scans subnets and fingerprints hardware banners to automatically discover and configure terminals.
12. **Filament v3 Admin Panel Plugin**: Live device status dots, single-click sync triggers, and filterable attendance log viewers.

---

## 📋 Compatibility & Requirements

| Component | Minimum Version | Recommended | Notes |
|---|---|---|---|
| **PHP** | `^8.2` | `8.3` / `8.4` | Strict typing, enums, readonly properties |
| **Laravel** | `10.x`, `11.x`, `12.x` | `12.x` | Supported on all active Laravel releases |
| **`ext-sockets`** | Required for ZKTeco | Required | **Important:** ZKTeco native driver uses raw TCP/UDP sockets (Port 4370). Ensure `extension=sockets` is enabled in `php.ini`. *(Shared hosting environments with sockets disabled should use the ADMS push or Webhook bridge drivers instead).* |
| **`ext-curl` / `ext-openssl`** | Required | Required | For ISAPI/BioStar REST calls and encryption at rest |

---

## 🛠️ Complete Installation Guide

### Step 1: Install via Composer

```bash
composer require imrandevbd/laravel-attendance-hub
```

### Step 2: Publish & Run Migrations

```bash
# Publish database migrations
php artisan vendor:publish --tag="attendance-hub-migrations"

# Run database migrations
php artisan migrate
```

This creates the following tables:
- `attendance_devices`: Registered hardware terminals, credentials, and connection settings.
- `attendance_logs`: Normalized, deduplicated attendance punches.
- `device_user_maps`: Maps hardware PINs/badge numbers to your application's `employee_id`.
- `device_biometric_templates`: Biometric template backup vault.

### Step 3: Publish Configuration File

```bash
php artisan vendor:publish --tag="attendance-hub-config"
```

---

## ⚙️ Complete Configuration Sample (`config/attendance-hub.php`)

```php
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Attendance Hardware Provider
    |--------------------------------------------------------------------------
    | Supported providers: "zkteco", "adms", "hikvision", "suprema", "dahua",
    |                      "webhook_bridge", "virtual", "fake"
    */
    'default_provider' => env('ATTENDANCE_DEFAULT_PROVIDER', 'zkteco'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone for Attendance Logs
    |--------------------------------------------------------------------------
    | Timestamps pulled from hardware will be normalized to this timezone.
    */
    'timezone' => env('ATTENDANCE_TIMEZONE', config('app.timezone', 'UTC')),

    /*
    |--------------------------------------------------------------------------
    | Device Local Timezone
    |--------------------------------------------------------------------------
    | Fallback timezone configured on the physical terminal hardware.
    */
    'device_timezone' => env('ATTENDANCE_DEVICE_TIMEZONE', 'UTC'),

    /*
    |--------------------------------------------------------------------------
    | ADMS / iClock HTTP Push Configuration
    |--------------------------------------------------------------------------
    | Built-in push endpoint parameters for ZKTeco, eSSL, Anviz, and Realtime.
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
    | Secret token required from ESP32, Raspberry Pi, or relay bridges.
    */
    'bridge' => [
        'token' => env('ATTENDANCE_BRIDGE_TOKEN', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sync Retry, Backoff & Rate-Limiting Knobs
    |--------------------------------------------------------------------------
    | Protects budget hardware from crash loops caused by aggressive polling.
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
    | Punch Processing Pipeline Middleware
    |--------------------------------------------------------------------------
    | Middleware pipes executed for every punch before database persistence.
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
    | Evaluates late minutes, overtime, and daily presence status.
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
    | Automatically scopes devices, logs, and maps to the current tenant ID.
    */
    'tenancy' => [
        'enabled' => env('ATTENDANCE_TENANCY_ENABLED', false),
        'tenant_resolver' => null, // e.g. fn() => auth()->user()?->tenant_id
    ],

    /*
    |--------------------------------------------------------------------------
    | Outgoing Attendance Webhooks
    |--------------------------------------------------------------------------
    | Dispatches HMAC-signed HTTP POST webhooks to external HRMS or ERP endpoints.
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
        'zkteco' => ['port' => 4370, 'protocol' => 'tcp', 'timeout' => 5],
        'hikvision' => ['port' => 80, 'protocol' => 'http', 'timeout' => 5],
        'suprema' => ['port' => 443, 'protocol' => 'https', 'timeout' => 5],
        'dahua' => ['port' => 80, 'protocol' => 'http', 'timeout' => 5],
    ],

    /*
    |--------------------------------------------------------------------------
    | Filament Admin Panel Integration
    |--------------------------------------------------------------------------
    */
    'filament' => [
        'enabled' => env('ATTENDANCE_FILAMENT_ENABLED', true),
        'navigation_group' => 'Attendance Management',
        'navigation_sort' => 10,
    ],

];
```

---

## 🔒 GDPR & Biometric Data Compliance Notice

> [!IMPORTANT]
> **Biometric Privacy & Legal Regulations (GDPR Article 9, Illinois BIPA, UK GDPR)**
> Raw biometric templates (fingerprint minutiae blobs, facial geometry feature vectors) and device administrative passwords constitute **Special Category Data** under GDPR and are strictly regulated under privacy frameworks like the Illinois Biometric Information Privacy Act (BIPA).
>
> `laravel-attendance-hub` enforces **Encryption at Rest** by default:
> - `attendance_devices.connection_settings` (passwords, comm keys, tokens) is cast to `'encrypted:array'`.
> - `device_biometric_templates.template_data` (biometric vectors) is cast to `'encrypted'`.
>
> In the underlying database storage, sensitive vectors and passwords are never visible as plaintext strings. Ensure your application's `APP_KEY` is securely rotated and backed up.

---

## 🧪 Zero-Hardware Testing & Simulation

Evaluating attendance software usually stalls because nobody has a physical biometric reader on their development desk. `laravel-attendance-hub` solves this completely.

### 1. The `AttendanceHub::fake()` Mock Driver

In your unit and feature tests, call `AttendanceHub::fake()` to swap real sockets with an in-memory test driver:

```php
use ImranDevBd\AttendanceHub\Facades\AttendanceHub;
use ImranDevBd\AttendanceHub\Models\AttendanceDevice;

public function test_attendance_sync_workflow(): void
{
    // Enable fake mode
    $fake = AttendanceHub::fake();

    $device = AttendanceDevice::create([
        'name' => 'HQ Gate Terminal',
        'provider' => 'zkteco',
        'ip' => '192.168.1.200',
    ]);

    // Perform sync (intercepted by FakeDeviceDriver)
    $logs = AttendanceHub::sync($device);

    $this->assertNotEmpty($logs);
    $this->assertDatabaseHas('attendance_logs', [
        'device_user_id' => '101',
    ]);

    // Built-in Assertions
    $fake->assertConnected();
    $fake->assertUserPushed('101');
    $fake->assertLogsCleared();
}
```

#### Simulating Connection Failures

Test how your application responds to network outages or unresponsive terminals:

```php
$fake = AttendanceHub::fake();
$fake->shouldFailConnection();

$this->expectException(\ImranDevBd\AttendanceHub\Exceptions\DeviceConnectionException::class);
AttendanceHub::sync($device);
```

### 2. Instant Filament & Database Simulation (`attendance:simulate`)

Want to demo the Filament admin panel or seed your database with realistic attendance trends without typing mock data? Run:

```bash
php artisan attendance:simulate --employees=25 --days=14 --device-name="HQ Turnstile #1"
```

Options:
- `--employees=N`: Number of distinct employees to simulate (default: `10`).
- `--days=N`: How many past days of check-in and check-out records to populate (default: `7`).
- `--device-name="Name"`: Name of the simulated demo terminal.
- `--tenant="ID"`: Optional tenant ID for multi-tenant setups.

The command automatically generates realistic office hours, normalizes punches through the pipeline, maps badge numbers, and calculates arrival times.

---

## 🚨 Custom Exception Hierarchy

The package surfaces specific, catchable exceptions so your background jobs and controllers can handle hardware failures gracefully:

```
AttendanceHubException (Base)
 ├── DeviceConnectionException (Network timeout, unreachable IP, closed socket)
 ├── DeviceAuthenticationException (Invalid Comm Key, 401 Unauthorized ISAPI)
 ├── UnsupportedOperationException (e.g. pushing face templates to a card-only reader)
 └── SyncFailedException (Corrupted attlog buffer, unexpected device state)
```

### Handling Failures in Production

```php
use ImranDevBd\AttendanceHub\Exceptions\DeviceConnectionException;
use ImranDevBd\AttendanceHub\Exceptions\DeviceAuthenticationException;
use ImranDevBd\AttendanceHub\Exceptions\UnsupportedOperationException;
use ImranDevBd\AttendanceHub\Facades\AttendanceHub;

try {
    AttendanceHub::sync($device);
} catch (DeviceConnectionException $e) {
    Log::warning("Terminal [{$device->name}] is unreachable: {$e->getMessage()}");
    $device->markOffline();
} catch (DeviceAuthenticationException $e) {
    Log::error("Terminal [{$device->name}] rejected credentials. Check Comm Key/Password.");
} catch (UnsupportedOperationException $e) {
    Log::notice("Device does not support this operation: {$e->getMessage()}");
}
```

---

## 💻 Usage & Code Examples

### 1. Connecting Directly by Hardware Model (166+ Models)

Instead of manually checking whether a terminal uses ZKTeco TCP, ADMS Push, ISAPI, or Wiegand relays, pass the model name directly:

```php
use ImranDevBd\AttendanceHub\Facades\AttendanceHub;

// Automatically routes to ZKTeco driver on Port 4370
$driver = AttendanceHub::model('UA860')
    ->connect(['ip' => '192.168.1.201']);

// Automatically routes to Hikvision ISAPI driver
$hikDriver = AttendanceHub::model('DS-K1T341AMF')
    ->connect(['ip' => '192.168.1.150', 'username' => 'admin', 'password' => 'secret']);

// Works across all 166 models (FingerTec TA500, eSSL X990, Suprema BioStation 2, etc.)
```

### 2. Direct Hardware Connection by Provider Key (LAN Pull Mode)

```php
use ImranDevBd\AttendanceHub\Facades\AttendanceHub;

// Pull logs directly from ZKTeco on LAN (Port 4370)
$punches = AttendanceHub::provider('zkteco')
    ->connect(['ip' => '192.168.1.201', 'port' => 4370])
    ->pullAttendanceLogs(since: now()->subDay());

foreach ($punches as $punch) {
    echo "Employee {$punch->deviceUserId} punched at {$punch->punchedAt} via {$punch->verifyMode->value}\n";
}
```

### 2. Live Capture & Real-Time Punch Streaming

Stream punches in real time as employees badge through turnstiles:

```php
use ImranDevBd\AttendanceHub\Facades\AttendanceHub;
use App\Events\PunchReceived;

AttendanceHub::provider('zkteco')
    ->connect(['ip' => '192.168.1.201', 'port' => 4370])
    ->liveCapture(function ($punch) {
        // Broadcast over WebSockets or dispatch an event
        broadcast(new PunchReceived($punch));
    });
```

### 3. Auto-Detect Hardware from IP

Automatically probe and detect what brand of terminal is at an unknown IP:

```php
$driver = AttendanceHub::autoDetect('192.168.1.50');

if ($driver) {
    $info = $driver->getDeviceInfo();
    echo "Identified: {$info->deviceName} (Firmware: {$info->firmwareVersion})";
    
    // Pull users enrolled on device
    $users = $driver->pullUsers();
}
```

### 4. High-Level Sync with Deduplication & Events

When managing devices registered in your database:

```php
use ImranDevBd\AttendanceHub\Models\AttendanceDevice;
use ImranDevBd\AttendanceHub\Facades\AttendanceHub;

$device = AttendanceDevice::firstOrCreate([
    'name' => 'HQ Factory Gate',
    'provider' => 'zkteco',
    'ip' => '192.168.1.100',
    'port' => 4370,
    'auto_clear_logs' => false,
]);

// Syncs logs, deduplicates via SHA-256 punch hashes, debounces rapid taps,
// converts timezones, maps employee IDs, and fires AttendanceRecorded events
$logs = AttendanceHub::sync($device);
```

### 5. Shift & Daily Attendance Interpretation (`AttendanceCalculator`)

Transform raw punch logs into daily summaries (Late, Overtime, Total Hours):

```php
use ImranDevBd\AttendanceHub\Facades\AttendanceHub;
use ImranDevBd\AttendanceHub\DTOs\AttendanceShift;

$shift = new AttendanceShift(
    name: 'Day Shift',
    startTime: '09:00',
    endTime: '18:00',
    gracePeriodMinutes: 15,
    halfDayThresholdMinutes: 240, // 4 hours
    fullDayThresholdMinutes: 480  // 8 hours
);

$summary = AttendanceHub::calculator($shift)->calculate('EMP-101', '2026-09-17');

echo "Status: " . $summary->status;                   // 'PRESENT', 'LATE', or 'HALF_DAY'
echo "Late Minutes: " . $summary->lateMinutes;         // e.g. 35
echo "Total Worked: " . $summary->totalWorkedMinutes; // e.g. 480 (8 hours)
echo "Overtime: " . $summary->overtimeMinutes;         // e.g. 60
```

To calculate daily summaries for **all employees** on a specific date:

```php
$summaries = AttendanceHub::calculator()->calculateDay('2026-09-17');

foreach ($summaries as $employeeId => $summary) {
    echo "{$employeeId}: {$summary->status} ({$summary->totalWorkedMinutes} mins)\n";
}
```

### 6. Generic IoT / Wiegand-Bridge Webhook Endpoint

Have dumb Wiegand 26/34-bit RFID readers hooked up to an ESP32 or Raspberry Pi relay? Point your microcontroller's HTTP client to:

```http
POST /api/attendance/webhook-bridge
Content-Type: application/json
X-Bridge-Token: your-secret-token-from-config

{
    "device_id": "WIEGAND_PANEL_01",
    "card_number": "10045892",
    "user_id": "104",
    "punched_at": "2026-09-17 08:58:12",
    "punch_type": "check_in",
    "verify_mode": "card"
}
```

The bridge driver automatically validates the token, maps the card number or user ID, runs the punch through the debouncing pipeline, and persists it into `attendance_logs`.

### 7. Virtual Check-In (Mobile GPS Geofence & QR)

Ideal for field staff, delivery drivers, or remote offices without physical hardware:

```php
use ImranDevBd\AttendanceHub\Facades\AttendanceHub;

$log = AttendanceHub::provider('virtual')->recordCheckIn([
    'employee_id' => 'EMP-502',
    'lat' => 23.7925,
    'lng' => 90.4078,
    'target_lat' => 23.7924,
    'target_lng' => 90.4077,
    'radius_meters' => 100, // Throws ValidationException if outside 100m
    'method' => 'gps',
    'punch_type' => 'check_in',
]);
```

Or consume the REST API from mobile apps (Flutter, React Native, Swift):

```bash
POST /api/attendance/virtual/check-in
Content-Type: application/json

{
    "employee_id": "EMP-502",
    "lat": 23.7925,
    "lng": 90.4078,
    "target_lat": 23.7924,
    "target_lng": 90.4077,
    "radius_meters": 100,
    "method": "gps"
}
```

### 8. Biometric Template Vault & Hardware Migration

Never lose enrolled fingerprints or faces when a physical terminal is replaced:

```bash
# Backup all fingerprint and face templates from old terminal into the encrypted vault
php artisan attendance:backup-templates 1

# Restore / push backed-up templates to the new replacement terminal
php artisan attendance:restore-templates 2
```

Or programmatically:

```php
use ImranDevBd\AttendanceHub\Facades\AttendanceHub;

// Backup templates from Device 1
$count = AttendanceHub::vault()->backupFromDevice($oldDevice);

// Push stored templates to Device 2
AttendanceHub::vault()->restoreToDevice($newDevice);
```

### 9. Multi-Tenancy Scoping (SaaS Support)

Enable multi-tenancy in `config/attendance-hub.php`:

```php
'tenancy' => [
    'enabled' => true,
    'tenant_resolver' => fn () => auth()->user()?->tenant_id,
],
```

All device registrations, attendance logs, biometric templates, and user mappings will automatically be scoped by `tenant_id`.

### 10. HMAC-Signed Outgoing Webhooks

Notify external microservices, payroll systems, or Slack when attendance is recorded:

```php
'webhooks' => [
    'enabled' => true,
    'secret' => env('ATTENDANCE_WEBHOOK_SECRET', 'your-signing-secret'),
    'endpoints' => [
        'https://hrms.yourcompany.com/api/attendance/webhook',
    ],
],
```

The receiving server can verify authenticity using the `X-Attendance-Signature` header:

```php
$signature = $request->header('X-Attendance-Signature');
$computed = hash_hmac('sha256', $request->getContent(), config('attendance-hub.webhooks.secret'));

if (hash_equals($signature, $computed)) {
    // Webhook is authentic
}
```

---

## ⚙️ Production Setup & Scheduling

### 1. Crontab / Task Scheduler

In your `routes/console.php` (Laravel 11+) or `app/Console/Kernel.php` (Laravel 10), configure periodic syncing and device health monitoring:

```php
use Illuminate\Support\Facades\Schedule;

// Pull logs from all registered devices every 15 minutes (queued)
Schedule::command('attendance:sync --all --queue')->everyFifteenMinutes();

// Ping devices hourly and fire offline/online events
Schedule::command('attendance:health')->hourly();
```

### 2. Supervisor / Queue Worker Configuration

Ensure your queue workers are running to process background device sync jobs:

```ini
[program:attendance-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/storage/logs/attendance-worker.log
```

### 3. Nginx Configuration for ADMS / iClock Push

If using **ADMS push devices** (ZKTeco cloud, eSSL, Anviz, Realtime), configure Nginx to forward requests and accept raw text payloads:

```nginx
location /iclock/ {
    try_files $uri $uri/ /index.php?$query_string;
    client_max_body_size 10M;
}
```

---

## 🖥️ Filament v3 Admin Panel Integration

Register the plugin in your Filament panel provider (e.g. `app/Providers/Filament/AdminPanelProvider.php`):

```php
use ImranDevBd\AttendanceHub\Filament\AttendanceHubPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugin(AttendanceHubPlugin::make());
}
```

### Features:
- **Live Device Status**: Real-time status indicators (Online / Offline / Warning), IP, port, last seen timestamp, and single-click **"Sync Now"** and **"Ping"** action buttons.
- **Attendance Logs Explorer**: Filterable records by Date Range, Employee, Source Terminal, Verification Mode (Fingerprint/Face/Card/GPS), and Punch Type.

---

## ⌨️ Artisan CLI Reference

| Command | Description | Example |
|---|---|---|
| `attendance:catalog` | Browse supported models, biometrics, ports, and driver mappings | `php artisan attendance:catalog --provider=zkteco` |
| `attendance:simulate` | Generate realistic test employees and punches without hardware | `php artisan attendance:simulate --employees=20 --days=7` |
| `attendance:sync` | Pull attendance logs from a device or all devices | `php artisan attendance:sync 1 --since="2026-09-01"` |
| `attendance:sync --all --queue` | Dispatch sync jobs across all devices to background workers | `php artisan attendance:sync --all --queue` |
| `attendance:discover` | Scan a network subnet to auto-detect attendance terminals | `php artisan attendance:discover 192.168.1.0/24 --register` |
| `attendance:health` | Probe ping status and update online/offline states | `php artisan attendance:health` |
| `attendance:enroll` | Push an employee profile to a terminal | `php artisan attendance:enroll 1 --user-id=101 --name="Imran Ahmed"` |
| `attendance:backup-templates` | Backup biometric templates into the encrypted vault | `php artisan attendance:backup-templates 1` |
| `attendance:restore-templates` | Push vault templates to a target device | `php artisan attendance:restore-templates 2` |
| `attendance:export` | Export daily calculated attendance reports | `php artisan attendance:export --from=2026-09-01 --to=2026-09-30 --format=csv --output=report.csv` |

---

## 🔧 Hardware Troubleshooting Guide

### 1. ZKTeco Socket Timeout (Port 4370)
- **Check Connectivity**: Run `Test-NetConnection -ComputerName <IP> -Port 4370` on Windows or `nc -zv <IP> 4370` on Linux.
- **Firewall Rule**: Ensure port 4370 (TCP & UDP) is open on the router / hardware subnet.
- **Device Comm Key**: Standalone ZKTeco terminals have a communication password (`Comm Key`). Default is `0`. If changed, set `'comm_key' => '12345'` in connection settings.
- **Shared Hosting**: If your PHP host blocks raw socket creation, use the **ADMS Push** mode or **Webhook Bridge** instead.

### 2. eSSL / Anviz ADMS Push Not Ingesting
- **Server Address**: On the physical device menu (`Comm` -> `Ethernet` / `Cloud Server`), enter your server IP/domain.
- **Server Port**: Set port `80` (or `443` for HTTPS).
- **Check Handshake**: Ensure the device serial number (`SN`) is registered in your `attendance_devices` table.
- **Review Access Logs**: Tail your access log (`tail -f /var/log/nginx/access.log | grep iclock`) to confirm incoming requests.

### 3. Hikvision ISAPI 401 Unauthorized
- In the Hikvision web portal (`Configuration` -> `System` -> `Security`), enable **Digest/Basic Authentication** for WEB/ISAPI requests.
- Ensure the configured user account has access control permissions.

---

## 🧪 Testing

The package includes a comprehensive test suite with Orchestra Testbench covering socket protocols, ADMS controllers, shift calculators, geofencing, pipelines, webhooks, mock drivers, and encryption at rest.

```bash
composer test
# or: vendor/bin/phpunit
```

---

## 🤝 Community & Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details on code standards, adding new device drivers, and running test suites. Review [SECURITY.md](SECURITY.md) for vulnerability disclosure protocols and biometric compliance guidelines. All notable changes are documented in [CHANGELOG.md](CHANGELOG.md).

---

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

---

<p align="center">
  Crafted with ❤️ by <a href="https://imrandev.bd">Imran Ahmed</a> (<a href="mailto:me@imrandev.bd">me@imrandev.bd</a>).
</p>
