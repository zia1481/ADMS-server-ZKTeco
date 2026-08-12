<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->bigInteger('attlog_stamp')->nullable()->after('comm_key');
        });

        Schema::table('device_handshake_configs', function (Blueprint $table) {
            $table->integer('stamp')->default(0)->change();
        });

        DB::table('device_handshake_configs')
            ->where('stamp', 9999)
            ->update(['stamp' => 0]);
    }

    public function down(): void
    {
        Schema::table('device_handshake_configs', function (Blueprint $table) {
            $table->integer('stamp')->default(9999)->change();
        });

        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn('attlog_stamp');
        });
    }
};
