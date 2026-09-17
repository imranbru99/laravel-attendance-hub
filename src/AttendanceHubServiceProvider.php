<?php

namespace ImranDevBd\AttendanceHub;

use Illuminate\Support\ServiceProvider;
use ImranDevBd\AttendanceHub\Commands\BackupTemplatesCommand;
use ImranDevBd\AttendanceHub\Commands\DeviceHealthCommand;
use ImranDevBd\AttendanceHub\Commands\DiscoverDevicesCommand;
use ImranDevBd\AttendanceHub\Commands\EnrollUserCommand;
use ImranDevBd\AttendanceHub\Commands\ExportAttendanceCommand;
use ImranDevBd\AttendanceHub\Commands\RestoreTemplatesCommand;
use ImranDevBd\AttendanceHub\Commands\SyncAttendanceCommand;

class AttendanceHubServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/attendance-hub.php',
            'attendance-hub'
        );

        $this->app->singleton('attendance-hub', function ($app) {
            return new AttendanceHubManager($app);
        });

        $this->app->alias('attendance-hub', AttendanceHubManager::class);
    }

    public function boot(): void
    {
        // Publish Configuration
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/attendance-hub.php' => config_path('attendance-hub.php'),
            ], 'attendance-hub-config');

            // Publish Migrations
            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'attendance-hub-migrations');

            // Register Artisan Commands
            $this->commands([
                SyncAttendanceCommand::class,
                DiscoverDevicesCommand::class,
                DeviceHealthCommand::class,
                EnrollUserCommand::class,
                BackupTemplatesCommand::class,
                RestoreTemplatesCommand::class,
                ExportAttendanceCommand::class,
                \ImranDevBd\AttendanceHub\Commands\SimulateAttendanceCommand::class,
                \ImranDevBd\AttendanceHub\Commands\ListDeviceCatalogCommand::class,
            ]);
        }

        // Load Migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Load Routes
        if (config('attendance-hub.adms.enabled', true)) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/adms.php');
        }
    }
}
