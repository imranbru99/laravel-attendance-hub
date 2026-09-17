<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_user_maps', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->foreignId('device_id')->constrained('attendance_devices')->cascadeOnDelete();
            $table->string('device_user_id');
            $table->string('employee_id')->index();
            $table->timestamp('enrolled_at')->nullable();
            $table->timestamps();

            $table->unique(['device_id', 'device_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_user_maps');
    }
};
