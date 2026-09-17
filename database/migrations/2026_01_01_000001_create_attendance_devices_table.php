<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_devices', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->string('name');
            $table->string('provider')->default('zkteco'); // zkteco, adms, hikvision, suprema, dahua, virtual
            $table->string('model')->nullable()->default('auto');
            $table->string('ip')->nullable();
            $table->integer('port')->default(4370);
            $table->string('serial_number')->nullable()->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->string('status')->default('offline'); // online, offline, warning, unconfigured
            $table->json('connection_settings')->nullable();
            $table->boolean('auto_clear_logs')->default(false);
            $table->string('timezone')->default('UTC');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_devices');
    }
};
