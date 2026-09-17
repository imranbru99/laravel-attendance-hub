<?php

namespace ImranDevBd\AttendanceHub\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use ImranDevBd\AttendanceHub\DTOs\DeviceConnection;
use ImranDevBd\AttendanceHub\Enums\DeviceStatus;
use ImranDevBd\AttendanceHub\Traits\BelongsToTenant;

class AttendanceDevice extends Model
{
    use BelongsToTenant;
    protected $table = 'attendance_devices';

    protected $guarded = ['id'];

    protected $casts = [
        'connection_settings' => 'encrypted:array',
        'auto_clear_logs' => 'boolean',
        'last_seen_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'port' => 'integer',
    ];

    public function attendanceLogs(): HasMany
    {
        return $this->hasMany(AttendanceLog::class, 'device_id');
    }

    public function userMaps(): HasMany
    {
        return $this->hasMany(DeviceUserMap::class, 'device_id');
    }

    public function toConnection(): DeviceConnection
    {
        $settings = $this->connection_settings ?? [];

        return new DeviceConnection(
            ip: $this->ip,
            port: $this->port ?? 4370,
            protocol: $settings['protocol'] ?? 'tcp',
            username: $settings['username'] ?? null,
            password: $settings['password'] ?? null,
            token: $settings['token'] ?? null,
            serialNumber: $this->serial_number,
            timeout: (int) ($settings['timeout'] ?? 5),
            options: $settings['options'] ?? []
        );
    }

    public function isOnline(): bool
    {
        return $this->status === DeviceStatus::ONLINE->value;
    }

    public function markOnline(): void
    {
        $this->update([
            'status' => DeviceStatus::ONLINE->value,
            'last_seen_at' => now(),
        ]);
    }

    public function markOffline(): void
    {
        $this->update([
            'status' => DeviceStatus::OFFLINE->value,
        ]);
    }
}
