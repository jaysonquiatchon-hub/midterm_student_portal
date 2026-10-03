<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Program;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        // Get program IDs from the database
        $programs = Program::pluck('id', 'code');

        $coursesByProgram = [
            'BSIT' => [
                ['code' => 'IT101', 'title' => 'Introduction to Computing', 'units' => 3, 'year_level' => 1],
                ['code' => 'IT102', 'title' => 'Computer Programming 1', 'units' => 3, 'year_level' => 1],
                ['code' => 'GE101', 'title' => 'Understanding the Self', 'units' => 3, 'year_level' => 1],
                ['code' => 'GE102', 'title' => 'Mathematics in the Modern World', 'units' => 3, 'year_level' => 1],
                ['code' => 'GE103', 'title' => 'Purposive Communication', 'units' => 3, 'year_level' => 1],
                ['code' => 'PE101', 'title' => 'PATHFit 1', 'units' => 2, 'year_level' => 1],
                ['code' => 'NSTP101', 'title' => 'NSTP 1', 'units' => 3, 'year_level' => 1],
                ['code' => 'IT201', 'title' => 'Information Management', 'units' => 3, 'year_level' => 2],
                ['code' => 'IT202', 'title' => 'Object-Oriented Programming', 'units' => 3, 'year_level' => 2],
                ['code' => 'IT203', 'title' => 'Platform Technologies', 'units' => 3, 'year_level' => 2],
                ['code' => 'IT204', 'title' => 'Human-Computer Interaction', 'units' => 3, 'year_level' => 2],
                ['code' => 'IT205', 'title' => 'Discrete Mathematics', 'units' => 3, 'year_level' => 2],
                ['code' => 'IT206', 'title' => 'Database Systems', 'units' => 3, 'year_level' => 2],
                ['code' => 'IT207', 'title' => 'Networking 1', 'units' => 3, 'year_level' => 2],
                ['code' => 'PE201', 'title' => 'PATHFit 2', 'units' => 2, 'year_level' => 2],
                ['code' => 'IT301', 'title' => 'Networking 2', 'units' => 3, 'year_level' => 3],
                ['code' => 'IT302', 'title' => 'Web Systems and Technologies', 'units' => 3, 'year_level' => 3],
                ['code' => 'IT303', 'title' => 'Integrative Programming', 'units' => 3, 'year_level' => 3],
                ['code' => 'IT304', 'title' => 'Mobile Application Development', 'units' => 3, 'year_level' => 3],
                ['code' => 'IT305', 'title' => 'Systems Administration', 'units' => 3, 'year_level' => 3],
                ['code' => 'IT306', 'title' => 'Cloud Computing', 'units' => 3, 'year_level' => 3],
                ['code' => 'IT307', 'title' => 'IT Project Management', 'units' => 3, 'year_level' => 3],
                ['code' => 'IT401', 'title' => 'Capstone Project 1', 'units' => 3, 'year_level' => 4],
                ['code' => 'IT402', 'title' => 'Capstone Project 2', 'units' => 3, 'year_level' => 4],
                ['code' => 'IT403', 'title' => 'Emerging Technologies', 'units' => 3, 'year_level' => 4],
                ['code' => 'IT404', 'title' => 'IT Elective', 'units' => 3, 'year_level' => 4],
                ['code' => 'IT405', 'title' => 'Professional Issues in IT', 'units' => 3, 'year_level' => 4],
                ['code' => 'IT406', 'title' => 'Practicum / Internship', 'units' => 6, 'year_level' => 4],
            ],
            'BSCS' => [
                ['code' => 'CS101', 'title' => 'Introduction to Computer Science', 'units' => 3, 'year_level' => 1],
                ['code' => 'CS102', 'title' => 'Computer Programming 1', 'units' => 3, 'year_level' => 1],
                ['code' => 'CS103', 'title' => 'College Algebra', 'units' => 3, 'year_level' => 1],
                ['code' => 'CS201', 'title' => 'Data Structures', 'units' => 3, 'year_level' => 2],
                ['code' => 'CS202', 'title' => 'Algorithms and Complexity', 'units' => 3, 'year_level' => 2],
                ['code' => 'CS203', 'title' => 'Object-Oriented Programming', 'units' => 3, 'year_level' => 2],
                ['code' => 'CS204', 'title' => 'Computer Organization', 'units' => 3, 'year_level' => 2],
                ['code' => 'CS205', 'title' => 'Database Systems', 'units' => 3, 'year_level' => 2],
                ['code' => 'CS206', 'title' => 'Calculus', 'units' => 3, 'year_level' => 2],
                ['code' => 'CS301', 'title' => 'Operating Systems', 'units' => 3, 'year_level' => 3],
                ['code' => 'CS302', 'title' => 'Computer Networks', 'units' => 3, 'year_level' => 3],
                ['code' => 'CS303', 'title' => 'Artificial Intelligence', 'units' => 3, 'year_level' => 3],
                ['code' => 'CS304', 'title' => 'Machine Learning', 'units' => 3, 'year_level' => 3],
                ['code' => 'CS305', 'title' => 'Theory of Computation', 'units' => 3, 'year_level' => 3],
                ['code' => 'CS306', 'title' => 'Software Engineering', 'units' => 3, 'year_level' => 3],
                ['code' => 'CS307', 'title' => 'Information Security', 'units' => 3, 'year_level' => 3],
                ['code' => 'CS401', 'title' => 'Thesis / Capstone 1', 'units' => 3, 'year_level' => 4],
                ['code' => 'CS402', 'title' => 'Thesis / Capstone 2', 'units' => 3, 'year_level' => 4],
                ['code' => 'CS403', 'title' => 'Advanced Algorithms', 'units' => 3, 'year_level' => 4],
                ['code' => 'CS404', 'title' => 'Computer Science Elective 1', 'units' => 3, 'year_level' => 4],
                ['code' => 'CS405', 'title' => 'Computer Science Elective 2', 'units' => 3, 'year_level' => 4],
                ['code' => 'CS406', 'title' => 'Internship', 'units' => 6, 'year_level' => 4],
            ],
            'BSBA' => [
                ['code' => 'BA101', 'title' => 'Fundamentals of Business', 'units' => 3, 'year_level' => 1],
                ['code' => 'BA102', 'title' => 'Business Mathematics', 'units' => 3, 'year_level' => 1],
                ['code' => 'BA103', 'title' => 'Financial Accounting', 'units' => 3, 'year_level' => 1],
                ['code' => 'BA201', 'title' => 'Principles of Management', 'units' => 3, 'year_level' => 2],
                ['code' => 'BA202', 'title' => 'Marketing Principles', 'units' => 3, 'year_level' => 2],
                ['code' => 'BA203', 'title' => 'Human Resource Management', 'units' => 3, 'year_level' => 2],
                ['code' => 'BA204', 'title' => 'Business Law', 'units' => 3, 'year_level' => 2],
                ['code' => 'BA205', 'title' => 'Economics', 'units' => 3, 'year_level' => 2],
                ['code' => 'BA206', 'title' => 'Managerial Accounting', 'units' => 3, 'year_level' => 2],
                ['code' => 'BA301', 'title' => 'Strategic Management', 'units' => 3, 'year_level' => 3],
                ['code' => 'BA302', 'title' => 'Consumer Behavior', 'units' => 3, 'year_level' => 3],
                ['code' => 'BA303', 'title' => 'Financial Management', 'units' => 3, 'year_level' => 3],
                ['code' => 'BA304', 'title' => 'Business Analytics', 'units' => 3, 'year_level' => 3],
                ['code' => 'BA305', 'title' => 'E-Commerce', 'units' => 3, 'year_level' => 3],
                ['code' => 'BA306', 'title' => 'Digital Marketing', 'units' => 3, 'year_level' => 3],
                ['code' => 'BA307', 'title' => 'Project Management', 'units' => 3, 'year_level' => 3],
                ['code' => 'BA401', 'title' => 'Business Policy', 'units' => 3, 'year_level' => 4],
                ['code' => 'BA402', 'title' => 'Feasibility Study', 'units' => 3, 'year_level' => 4],
                ['code' => 'BA403', 'title' => 'Business Elective 1', 'units' => 3, 'year_level' => 4],
                ['code' => 'BA404', 'title' => 'Business Elective 2', 'units' => 3, 'year_level' => 4],
                ['code' => 'BA405', 'title' => 'Business Integration', 'units' => 3, 'year_level' => 4],
                ['code' => 'BA406', 'title' => 'Internship / Practicum', 'units' => 6, 'year_level' => 4],
            ],
            'BSA' => [
                ['code' => 'AC101', 'title' => 'Fundamentals of Accounting', 'units' => 3, 'year_level' => 1],
                ['code' => 'AC102', 'title' => 'Accounting 1', 'units' => 3, 'year_level' => 1],
                ['code' => 'AC103', 'title' => 'Business Mathematics', 'units' => 3, 'year_level' => 1],
                ['code' => 'AC201', 'title' => 'Intermediate Accounting 1', 'units' => 3, 'year_level' => 2],
                ['code' => 'AC202', 'title' => 'Intermediate Accounting 2', 'units' => 3, 'year_level' => 2],
                ['code' => 'AC203', 'title' => 'Cost Accounting', 'units' => 3, 'year_level' => 2],
                ['code' => 'AC204', 'title' => 'Taxation', 'units' => 3, 'year_level' => 2],
                ['code' => 'AC205', 'title' => 'Business Statistics', 'units' => 3, 'year_level' => 2],
                ['code' => 'AC206', 'title' => 'Financial Management', 'units' => 3, 'year_level' => 2],
                ['code' => 'AC301', 'title' => 'Auditing', 'units' => 3, 'year_level' => 3],
                ['code' => 'AC302', 'title' => 'Advanced Accounting', 'units' => 3, 'year_level' => 3],
                ['code' => 'AC303', 'title' => 'Financial Accounting and Reporting', 'units' => 3, 'year_level' => 3],
                ['code' => 'AC304', 'title' => 'Income Taxation', 'units' => 3, 'year_level' => 3],
                ['code' => 'AC305', 'title' => 'Accounting Information Systems', 'units' => 3, 'year_level' => 3],
                ['code' => 'AC306', 'title' => 'Business Finance', 'units' => 3, 'year_level' => 3],
                ['code' => 'AC307', 'title' => 'Accounting Research', 'units' => 3, 'year_level' => 3],
                ['code' => 'AC401', 'title' => 'Advanced Auditing', 'units' => 3, 'year_level' => 4],
                ['code' => 'AC402', 'title' => 'Advanced Taxation', 'units' => 3, 'year_level' => 4],
                ['code' => 'AC403', 'title' => 'Government Accounting', 'units' => 3, 'year_level' => 4],
                ['code' => 'AC404', 'title' => 'Accounting Elective 1', 'units' => 3, 'year_level' => 4],
                ['code' => 'AC405', 'title' => 'Accounting Elective 2', 'units' => 3, 'year_level' => 4],
                ['code' => 'AC406', 'title' => 'Accounting Practicum', 'units' => 6, 'year_level' => 4],
            ],
            'BSED' => [
                ['code' => 'ED101', 'title' => 'The Teaching Profession', 'units' => 3, 'year_level' => 1],
                ['code' => 'ED102', 'title' => 'The Learner and Learning Principles', 'units' => 3, 'year_level' => 1],
                ['code' => 'GE104', 'title' => 'Readings in Philippine History', 'units' => 3, 'year_level' => 1],
                ['code' => 'ED201', 'title' => 'Principles of Teaching', 'units' => 3, 'year_level' => 2],
                ['code' => 'ED202', 'title' => 'Educational Technology', 'units' => 3, 'year_level' => 2],
                ['code' => 'ED203', 'title' => 'Curriculum Development', 'units' => 3, 'year_level' => 2],
                ['code' => 'ED204', 'title' => 'Assessment in Learning', 'units' => 3, 'year_level' => 2],
                ['code' => 'EN201', 'title' => 'Structure of English', 'units' => 3, 'year_level' => 2],
                ['code' => 'EN202', 'title' => 'Literature of the Philippines', 'units' => 3, 'year_level' => 2],
                ['code' => 'ED301', 'title' => 'Classroom Management', 'units' => 3, 'year_level' => 3],
                ['code' => 'ED302', 'title' => 'Assessment and Evaluation', 'units' => 3, 'year_level' => 3],
                ['code' => 'ED303', 'title' => 'Research in Education', 'units' => 3, 'year_level' => 3],
                ['code' => 'EN301', 'title' => 'Teaching English in Secondary Schools', 'units' => 3, 'year_level' => 3],
                ['code' => 'EN302', 'title' => 'English Literature', 'units' => 3, 'year_level' => 3],
                ['code' => 'EN303', 'title' => 'World Literature', 'units' => 3, 'year_level' => 3],
                ['code' => 'EN304', 'title' => 'Language Acquisition', 'units' => 3, 'year_level' => 3],
                ['code' => 'ED401', 'title' => 'Special Topics in Education', 'units' => 3, 'year_level' => 4],
                ['code' => 'ED402', 'title' => 'Teaching Strategies', 'units' => 3, 'year_level' => 4],
                ['code' => 'EN401', 'title' => 'English Language Curriculum', 'units' => 3, 'year_level' => 4],
                ['code' => 'EN402', 'title' => 'Materials Development', 'units' => 3, 'year_level' => 4],
                ['code' => 'ED403', 'title' => 'Educational Research Project', 'units' => 3, 'year_level' => 4],
                ['code' => 'ED404', 'title' => 'Practice Teaching', 'units' => 6, 'year_level' => 4],
            ],
        ];

        foreach ($coursesByProgram as $code => $courses) {
            $programId = $programs[$code] ?? null;

            if ($programId) {
                foreach ($courses as $course) {
                    Course::updateOrCreate(
                        ['code' => $course['code']],
                        [
                            'title' => $course['title'],
                            'units' => $course['units'],
                            'year_level' => $course['year_level'],
                            'program_id' => $programId,
                            'semester' => $course['units'] === 6 && $course['year_level'] === 4
                                ? 'Summer'
                                : ((int) substr($course['code'], -1) % 2 === 0 ? '2nd' : '1st'),
                            'status' => 'active',
                        ]
                    );
                }
            }
        }
    }
}
