<?php

namespace ImranDevBd\AttendanceHub\Tests;

use ImranDevBd\AttendanceHub\AttendanceHubServiceProvider;
use ImranDevBd\AttendanceHub\Facades\AttendanceHub;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    protected function getPackageProviders($app): array
    {
        return [
            AttendanceHubServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'AttendanceHub' => AttendanceHub::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('app.key', 'base64:6Cu/Iz94V+8w5z2G+9c38FvR5B5n3e0z1w2x4y6z8A0=');
        $app['config']->set('app.cipher', 'AES-256-CBC');
        $app['config']->set('attendance-hub.adms.enabled', true);
        $app['config']->set('attendance-hub.adms.middleware', []);
    }
}
