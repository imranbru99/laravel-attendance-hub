# Changelog

All notable changes to `imrandevbd/laravel-attendance-hub` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-09-17

### Added
- **Unified Hardware Driver Engine**: Single `DeviceDriverInterface` contract across physical and virtual devices.
- **Native ZKTeco Driver**: Low-level TCP/UDP socket communication over port `4370` supporting ZK6 (8-byte) and ZK8 (40-byte) records, live capture, user management, and buffer clearing.
- **ADMS / iClock Cloud Push Listener**: Built-in HTTP endpoints (`GET/POST /iclock/cdata`, `GET /iclock/getrequest`, `POST /iclock/devicecmd`) for ZKTeco cloud, eSSL, Anviz, and Realtime devices behind NAT/firewalls.
- **Enterprise Drivers**:
  - `HikvisionDriver` (ISAPI XML/JSON over HTTP/HTTPS with Digest/Basic auth).
  - `SupremaDriver` (BioStar 2 REST API).
  - `DahuaDriver` (CGI/NetSDK HTTP API).
- **Virtual Check-In Driver**: Mobile, kiosk, QR code, and GPS geofenced check-ins with Haversine boundary calculations and rotating QR token verification.
- **IoT & Wiegand Webhook Bridge Driver**: Generic bridge driver for ESP32 and Raspberry Pi relays reading Wiegand 26/34 bits.
- **Hardware Simulation & Fake Driver**: `AttendanceHub::fake()` and `FakeDeviceDriver` allowing full pipeline testing without hardware.
- **Biometric Template Vault**: Backup and restore fingerprint and face templates across devices (`DeviceTemplateVault`).
- **Shift & Working Hours Interpreter**: `AttendanceCalculator` resolving raw punches into First-In, Last-Out, Total Work Duration, Late Arrival, Early Departure, Overtime, and Status (Present, Late, Half-Day, Absent).
- **Multi-Tenancy Support**: `BelongsToTenant` trait and automatic tenant query scoping.
- **Punch Processing Pipeline**: Debouncing accidental double-taps and anti-passback rule checking.
- **HMAC Outgoing Webhooks**: Real-time HTTP POST notifications with `X-Attendance-Signature` for external systems (SAP, Odoo, Slack).
- **Artisan Commands Suite**:
  - `attendance:sync`
  - `attendance:discover`
  - `attendance:health`
  - `attendance:enroll`
  - `attendance:backup-templates`
  - `attendance:restore-templates`
  - `attendance:export`
  - `attendance:simulate`
- **Filament v3 Admin Panel Integration**: `AttendanceDeviceResource` (with live status indicator and Sync Now trigger) and `AttendanceLogResource`.
- **Encryption at Rest**: Encrypted casts on credentials, connection settings, and raw biometric templates.
