<?php

namespace ImranDevBd\AttendanceHub\Tests\Feature;

use ImranDevBd\AttendanceHub\Tests\TestCase;

class CatalogCommandTest extends TestCase
{
    public function test_attendance_catalog_command_lists_all_models(): void
    {
        $this->artisan('attendance:catalog')
            ->expectsOutputToContain('supported hardware models across 10 global providers')
            ->expectsOutputToContain('ZKTeco')
            ->expectsOutputToContain('Hikvision')
            ->expectsOutputToContain('Anviz')
            ->expectsOutputToContain('FingerTec')
            ->expectsOutputToContain('eSSL')
            ->expectsOutputToContain('VIRDI')
            ->expectsOutputToContain('Suprema')
            ->expectsOutputToContain('Deli')
            ->expectsOutputToContain('Granding')
            ->expectsOutputToContain('Soyal')
            ->assertExitCode(0);
    }

    public function test_attendance_catalog_filters_by_provider(): void
    {
        $this->artisan('attendance:catalog', ['--provider' => 'zkteco'])
            ->expectsOutputToContain("Found 20 models for provider 'zkteco'")
            ->expectsOutputToContain('UA860')
            ->expectsOutputToContain('SpeedFace-V5L')
            ->assertExitCode(0);
    }

    public function test_attendance_catalog_searches_by_keyword(): void
    {
        $this->artisan('attendance:catalog', ['--search' => 'BioStation'])
            ->expectsOutputToContain('BioStation 2')
            ->expectsOutputToContain('BioStation 3')
            ->assertExitCode(0);
    }
}
