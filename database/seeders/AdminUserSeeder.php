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
        $password = config('student_portal.admin_password');
        if (! is_string($password) || $password === '') {
            return;
        }

        User::firstOrCreate(
            ['email' => config('student_portal.admin_email')],
            [
                'name' => 'Portal Administrator',
                'password' => $password,
                'role' => 'admin',
            ],
        );
    }
}
