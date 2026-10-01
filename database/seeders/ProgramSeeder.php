<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Program;
use Illuminate\Database\Seeder;

class ProgramSeeder extends Seeder
{
    public function run(): void
    {
        $departmentIds = Department::query()->pluck('id', 'code');
        $programs = [
            ['code' => 'BSIT', 'name' => 'BSIT — Bachelor of Science in Information Technology', 'department_code' => 'CIT'],
            ['code' => 'BSCS', 'name' => 'BSCS — Bachelor of Science in Computer Science', 'department_code' => 'CIT'],
            ['code' => 'BSBA', 'name' => 'BSBA — Bachelor of Science in Business Administration', 'department_code' => 'CBA'],
            ['code' => 'BSA', 'name' => 'BSA — Bachelor of Science in Accountancy', 'department_code' => 'CBA'],
            ['code' => 'BSED', 'name' => 'BSED — Bachelor of Secondary Education', 'department_code' => 'COE'],
        ];

        foreach ($programs as $program) {
            Program::updateOrCreate(
                ['code' => $program['code']],
                [
                    'name' => $program['name'],
                    'department_id' => $departmentIds[$program['department_code']] ?? null,
                ],
            );
        }
    }
}