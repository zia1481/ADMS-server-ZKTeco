<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pending_devices', function (Blueprint $table) {
            $table->id();
            $table->string('sn')->unique();
            $table->string('ip_address')->nullable();
            $table->string('model')->nullable();
            $table->string('fw_ver')->nullable();
            $table->string('push_ver')->nullable();
            $table->text('options')->nullable();
            $table->dateTime('first_seen');
            $table->dateTime('last_seen');
            $table->string('state')->default('detected');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_devices');
    }
};
