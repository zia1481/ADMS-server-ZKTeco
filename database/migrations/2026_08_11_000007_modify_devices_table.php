<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('area_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
            $table->string('ip_address')->nullable()->after('lokasi');
            $table->string('model')->nullable()->after('ip_address');
            $table->string('fw_ver')->nullable()->after('model');
            $table->string('push_ver')->nullable()->after('fw_ver');
            $table->string('status')->default('registered')->after('push_ver');
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('area_id');
            $table->dropConstrainedForeignId('company_id');
            $table->dropColumn(['ip_address', 'model', 'fw_ver', 'push_ver', 'status']);
        });
    }
};
