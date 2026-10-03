<?php

namespace Database\Seeders;

use App\Models\Program;
use Illuminate\Database\Seeder;

class ProgramSeeder extends Seeder
{
    public function run(): void
    {
        $programs = [
            ['code' => 'BSIT', 'name' => 'BSIT — Bachelor of Science in Information Technology'],
            ['code' => 'BSCS', 'name' => 'BSCS — Bachelor of Science in Computer Science'],
            ['code' => 'BSBA', 'name' => 'BSBA — Bachelor of Science in Business Administration'],
            ['code' => 'BSA',  'name' => 'BSA — Bachelor of Science in Accountancy'],
            ['code' => 'BSED', 'name' => 'BSED — Bachelor of Secondary Education'],
        ];

        foreach ($programs as $program) {
            Program::updateOrCreate(['code' => $program['code']], $program);
        }
    }
}