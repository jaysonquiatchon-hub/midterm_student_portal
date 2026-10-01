<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => config('student_portal.admin_email')],
            [
                'name' => 'Portal Administrator',
                'password' => config('student_portal.admin_password'),
                'role' => 'admin',
            ],
        );
    }
}
