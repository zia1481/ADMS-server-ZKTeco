<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companyId = DB::table('companies')->where('code', 'DEFAULT')->value('id');

        DB::table('users')->insertOrIgnore([
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'password' => Hash::make('password'),
            'is_admin' => true,
            'role' => User::ROLE_SUPER_ADMIN,
            'company_id' => null,
        ]);

        DB::table('users')->insertOrIgnore([
            'name' => 'John koye',
            'email' => 'john.koye@example.com',
            'password' => Hash::make('password'),
            'is_admin' => false,
            'role' => User::ROLE_COMPANY_ADMIN,
            'company_id' => $companyId,
        ]);
    }
}
