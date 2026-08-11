<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompanySeeder extends Seeder
{
    /**
     * Seed the default company.
     */
    public function run(): void
    {
        DB::table('companies')->updateOrInsert(
            ['code' => 'DEFAULT'],
            [
                'name' => 'Default Company',
                'description' => 'Company created during initial setup for existing data.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
