<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->foreignId('device_id')->nullable()->constrained('attendance_devices')->nullOnDelete();
            $table->string('device_user_id')->index();
            $table->string('employee_id')->nullable()->index();
            $table->dateTime('punched_at')->index();
            $table->string('verify_mode')->default('other');
            $table->string('punch_type')->default('auto');
            $table->string('punch_hash')->unique();
            $table->json('location')->nullable(); // lat, lng, radius, address
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->index(['device_id', 'punched_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_logs');
    }
};
