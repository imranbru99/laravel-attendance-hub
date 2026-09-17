<?php

namespace ImranDevBd\AttendanceHub\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use ImranDevBd\AttendanceHub\Filament\Resources\AttendanceDeviceResource;
use ImranDevBd\AttendanceHub\Filament\Resources\AttendanceLogResource;

class AttendanceHubPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'attendance-hub';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            AttendanceDeviceResource::class,
            AttendanceLogResource::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        // Boot hooks
    }
}
