<?php

namespace Database\Seeders;

use App\Models\Program;
use App\Models\Student;
use Illuminate\Database\Seeder;

class StudentDemoSeeder extends Seeder
{
    public function run(): void
    {
        $programs = [
            'BSIT' => 'Bachelor of Science in Information Technology',
            'BSCS' => 'Bachelor of Science in Computer Science',
            'BSBA' => 'Bachelor of Science in Business Administration',
            'BSA' => 'Bachelor of Science in Accountancy',
            'BSED' => 'Bachelor of Secondary Education',
        ];

        foreach ($programs as $code => $name) {
            Program::firstOrCreate(
                ['code' => $code],
                ['name' => "{$code} — {$name}"],
            );
        }

        $this->call(CourseSeeder::class);

        $students = [
            ['Chloe', 'Bartoletti', 'BSA', 3, '2004-03-12'],
            ['Rahul', 'Bernier', 'BSED', 1, '2006-08-21'],
            ['Mikel', 'Corkery', 'BSBA', 1, '2006-11-05'],
            ['Nathaniel', 'Cremin', 'BSBA', 3, '2004-06-17'],
            ['Ulises', 'Farrell', 'BSBA', 2, '2005-02-28'],
            ['Katerina', 'Feeney', 'BSCS', 2, '2005-09-09'],
            ['Favian', 'Feest', 'BSBA', 1, '2006-04-14'],
            ['Denick', 'Katlison', 'BSCS', 4, '2003-12-03'],
            ['Maggie', 'Hudson', 'BSIT', 3, '2004-07-25'],
            ['Gail', 'Jaskolski', 'BSA', 4, '2003-10-19'],
            ['Isabella', 'Santos', 'BSIT', 1, '2006-01-30'],
            ['Mateo', 'Reyes', 'BSCS', 2, '2005-05-08'],
            ['Sofia', 'Cruz', 'BSBA', 3, '2004-09-16'],
            ['Liam', 'Bennett', 'BSED', 2, '2005-12-11'],
            ['Amara', 'Villanueva', 'BSA', 1, '2006-06-22'],
            ['Ethan', 'Morgan', 'BSIT', 4, '2003-04-02'],
            ['Nina', 'Flores', 'BSCS', 3, '2004-01-13'],
            ['Caleb', 'Turner', 'BSBA', 4, '2003-08-27'],
            ['Maya', 'Garcia', 'BSED', 1, '2006-10-06'],
            ['Noah', 'Dela Cruz', 'BSA', 2, '2005-03-19'],
        ];

        foreach ($students as $index => [$firstName, $lastName, $programCode, $yearLevel, $birthDate]) {
            $studentNumber = '2026-'.str_pad((string) (90001 + $index), 5, '0', STR_PAD_LEFT);

            Student::firstOrCreate(
                ['student_number' => $studentNumber],
                [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => strtolower("student{$studentNumber}@example.test"),
                    'birth_date' => $birthDate,
                    'year_level' => $yearLevel,
                    'program_id' => Program::where('code', $programCode)->value('id'),
                ],
            );
        }
    }
}
