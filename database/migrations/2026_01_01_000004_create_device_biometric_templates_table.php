<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_biometric_templates', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index();
            $table->foreignId('device_id')->nullable()->constrained('attendance_devices')->nullOnDelete();
            $table->string('device_user_id')->index();
            $table->string('employee_id')->nullable()->index();
            $table->string('template_type')->default('fingerprint'); // fingerprint, face, palm
            $table->integer('finger_index')->default(0); // 0-9 for fingers
            $table->longText('template_data'); // base64 encoded template
            $table->string('version')->nullable(); // algorithm version e.g. ZK10
            $table->timestamps();

            $table->unique(['device_user_id', 'template_type', 'finger_index'], 'user_template_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_biometric_templates');
    }
};
