<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE attendance_staging
            MODIFY status1 INT NULL,
            MODIFY status2 INT NULL,
            MODIFY status3 INT NULL,
            MODIFY status4 INT NULL,
            MODIFY status5 INT NULL');

        DB::statement('ALTER TABLE attendances
            MODIFY status1 INT NULL,
            MODIFY status2 INT NULL,
            MODIFY status3 INT NULL,
            MODIFY status4 INT NULL,
            MODIFY status5 INT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE attendance_staging
            MODIFY status1 BOOLEAN NULL,
            MODIFY status2 BOOLEAN NULL,
            MODIFY status3 BOOLEAN NULL,
            MODIFY status4 BOOLEAN NULL,
            MODIFY status5 BOOLEAN NULL');

        DB::statement('ALTER TABLE attendances
            MODIFY status1 BOOLEAN NULL,
            MODIFY status2 BOOLEAN NULL,
            MODIFY status3 BOOLEAN NULL,
            MODIFY status4 BOOLEAN NULL,
            MODIFY status5 BOOLEAN NULL');
    }
};