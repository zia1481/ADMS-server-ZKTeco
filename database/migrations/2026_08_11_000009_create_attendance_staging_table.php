<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_staging', function (Blueprint $table) {
            $table->id();
            $table->string('sn');
            $table->foreignId('device_id')->nullable()->constrained('devices')->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('table')->nullable();
            $table->string('stamp')->nullable();
            $table->text('payload');
            $table->string('employee_id')->nullable();
            $table->dateTime('punch_time')->nullable();
            $table->boolean('status1')->nullable();
            $table->boolean('status2')->nullable();
            $table->boolean('status3')->nullable();
            $table->boolean('status4')->nullable();
            $table->boolean('status5')->nullable();
            $table->string('state')->default('pending');
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_staging');
    }
};
