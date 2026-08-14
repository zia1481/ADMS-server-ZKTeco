<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('status')->default('active')->after('code');
        });

        DB::table('companies')->where('is_active', false)->update(['status' => 'disabled']);

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('code');
        });

        DB::table('companies')->where('status', 'active')->update(['is_active' => true]);
        DB::table('companies')->where('status', '!=', 'active')->update(['is_active' => false]);

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
