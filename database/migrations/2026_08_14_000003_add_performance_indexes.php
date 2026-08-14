<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->index('timestamp');
            $table->index('employee_id');
            $table->index('sn');
            $table->index(['company_id', 'timestamp']);
        });

        Schema::table('attendance_staging', function (Blueprint $table) {
            $table->index('state');
            $table->index(['device_id', 'state']);
        });

        Schema::table('devices', function (Blueprint $table) {
            $table->index('online');
        });

        Schema::table('finger_log', function (Blueprint $table) {
            $table->index('created_at');
        });

        Schema::table('device_log', function (Blueprint $table) {
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex(['timestamp']);
            $table->dropIndex(['employee_id']);
            $table->dropIndex(['sn']);
            $table->dropIndex(['company_id', 'timestamp']);
        });

        Schema::table('attendance_staging', function (Blueprint $table) {
            $table->dropIndex(['state']);
            $table->dropIndex(['device_id', 'state']);
        });

        Schema::table('devices', function (Blueprint $table) {
            $table->dropIndex(['online']);
        });

        Schema::table('finger_log', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });

        Schema::table('device_log', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });
    }
};
