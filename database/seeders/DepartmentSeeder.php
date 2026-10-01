<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['code' => 'CIT', 'name' => 'College of Information Technology'],
            ['code' => 'CBA', 'name' => 'College of Business'],
            ['code' => 'COE', 'name' => 'College of Education'],
        ];

        foreach ($departments as $department) {
            Department::updateOrCreate(['code' => $department['code']], [
                'name' => $department['name'],
            ]);
        }
    }
}
