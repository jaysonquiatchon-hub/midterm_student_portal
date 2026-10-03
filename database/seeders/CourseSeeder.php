<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Program;
use Illuminate\Database\Seeder;
use RuntimeException;

class CourseSeeder extends Seeder
{
    private const CURRICULA = [
        'BSIT' => <<<'COURSES'
1|1st|IT101|3|Introduction to Computing
1|1st|IT102|3|Computer Programming 1
1|1st|GE101|3|Understanding the Self
1|1st|GE102|3|Mathematics in the Modern World
1|1st|GE103|3|Purposive Communication
1|1st|PE101|2|PATHFit 1
1|1st|NSTP101|3|NSTP 1
1|2nd|IT103|3|Computer Programming 2
1|2nd|IT104|3|Data Structures and Algorithms
1|2nd|IT105|3|Web Development
1|2nd|GE104|3|Readings in Philippine History
1|2nd|GE105|3|The Contemporary World
1|2nd|GE106|3|Art Appreciation
1|2nd|PE102|2|PATHFit 2
1|2nd|NSTP102|3|NSTP 2
2|1st|IT201|3|Information Management
2|1st|IT202|3|Object-Oriented Programming
2|1st|IT203|3|Platform Technologies
2|1st|IT204|3|Human-Computer Interaction
2|1st|IT205|3|Discrete Mathematics
2|1st|GE201|3|Science, Technology and Society
2|1st|PE201|2|PATHFit 3
2|2nd|IT206|3|Fundamentals of Database Systems
2|2nd|IT207|3|Networking 1
2|2nd|IT208|3|Systems Analysis and Design
2|2nd|IT209|3|Application Development
2|2nd|IT210|3|Information Assurance and Security
2|2nd|GE202|3|Ethics
2|2nd|GE203|3|Life and Works of Rizal
2|2nd|PE202|2|PATHFit 4
3|1st|IT301|3|Networking 2
3|1st|IT302|3|Web Systems and Technologies
3|1st|IT303|3|Integrative Programming
3|1st|IT304|3|Mobile Application Development
3|1st|IT305|3|Quantitative Methods
3|1st|IT306|3|Systems Administration
3|2nd|IT307|3|Advanced Database Systems
3|2nd|IT308|3|Information Assurance and Security 2
3|2nd|IT309|3|Cloud Computing
3|2nd|IT310|3|IT Project Management
3|2nd|IT311|3|Technopreneurship
3|2nd|IT312|3|Capstone Project 1
4|1st|IT401|3|Capstone Project 2
4|1st|IT402|3|Emerging Technologies
4|1st|IT403|3|IT Elective 1
4|1st|IT404|3|IT Elective 2
4|1st|IT405|3|Professional Issues in IT
4|2nd|IT406|6|Practicum / Internship
4|2nd|IT407|3|IT Elective 3
4|2nd|IT408|3|IT Service Management
COURSES,
        'BSCS' => <<<'COURSES'
1|1st|CS101|3|Introduction to Computer Science
1|1st|CS102|3|Computer Programming 1
1|1st|CS103|3|College Algebra
1|1st|GE101|3|Understanding the Self
1|1st|GE102|3|Purposive Communication
1|1st|PE101|2|PATHFit 1
1|1st|NSTP101|3|NSTP 1
1|2nd|CS104|3|Computer Programming 2
1|2nd|CS105|3|Discrete Structures
1|2nd|CS106|3|Data Structures
1|2nd|GE103|3|Mathematics in the Modern World
1|2nd|GE104|3|Readings in Philippine History
1|2nd|GE105|3|The Contemporary World
1|2nd|PE102|2|PATHFit 2
1|2nd|NSTP102|3|NSTP 2
2|1st|CS201|3|Algorithms and Complexity
2|1st|CS202|3|Object-Oriented Programming
2|1st|CS203|3|Computer Organization
2|1st|CS204|3|Calculus 1
2|1st|CS205|3|Database Systems
2|1st|GE201|3|Art Appreciation
2|1st|PE201|2|PATHFit 3
2|2nd|CS206|3|Operating Systems
2|2nd|CS207|3|Computer Architecture
2|2nd|CS208|3|Software Engineering
2|2nd|CS209|3|Linear Algebra
2|2nd|CS210|3|Probability and Statistics
2|2nd|GE202|3|Science, Technology and Society
2|2nd|PE202|2|PATHFit 4
3|1st|CS301|3|Theory of Computation
3|1st|CS302|3|Programming Languages
3|1st|CS303|3|Artificial Intelligence
3|1st|CS304|3|Computer Networks
3|1st|CS305|3|Numerical Methods
3|1st|CS306|3|Web Technologies
3|2nd|CS307|3|Machine Learning
3|2nd|CS308|3|Compiler Design
3|2nd|CS309|3|Information Security
3|2nd|CS310|3|Human-Computer Interaction
3|2nd|CS311|3|Software Development
3|2nd|CS312|3|Research Methods in Computing
4|1st|CS401|3|Thesis / Capstone Project 1
4|1st|CS402|3|Advanced Algorithms
4|1st|CS403|3|Computer Science Elective 1
4|1st|CS404|3|Computer Science Elective 2
4|1st|CS405|3|Technopreneurship
4|2nd|CS406|3|Thesis / Capstone Project 2
4|2nd|CS407|6|Internship
4|2nd|CS408|3|Computer Science Elective 3
COURSES,
        'BSBA' => <<<'COURSES'
1|1st|BA101|3|Fundamentals of Business
1|1st|BA102|3|Business Mathematics
1|1st|BA103|3|Financial Accounting
1|1st|GE101|3|Understanding the Self
1|1st|GE102|3|Purposive Communication
1|1st|GE103|3|Mathematics in the Modern World
1|1st|PE101|2|PATHFit 1
1|1st|NSTP101|3|NSTP 1
1|2nd|BA104|3|Principles of Management
1|2nd|BA105|3|Marketing Principles
1|2nd|BA106|3|Business Statistics
1|2nd|GE104|3|Readings in Philippine History
1|2nd|GE105|3|The Contemporary World
1|2nd|GE106|3|Art Appreciation
1|2nd|PE102|2|PATHFit 2
1|2nd|NSTP102|3|NSTP 2
2|1st|BA201|3|Human Resource Management
2|1st|BA202|3|Operations Management
2|1st|BA203|3|Managerial Accounting
2|1st|BA204|3|Business Law
2|1st|BA205|3|Economics
2|1st|GE201|3|Science, Technology and Society
2|1st|PE201|2|PATHFit 3
2|2nd|BA206|3|Financial Management
2|2nd|BA207|3|Organizational Behavior
2|2nd|BA208|3|Business Research
2|2nd|BA209|3|Entrepreneurship
2|2nd|BA210|3|Business Communication
2|2nd|GE202|3|Ethics
2|2nd|PE202|2|PATHFit 4
3|1st|BA301|3|Strategic Management
3|1st|BA302|3|Consumer Behavior
3|1st|BA303|3|Sales Management
3|1st|BA304|3|Investment Management
3|1st|BA305|3|E-Commerce
3|1st|BA306|3|Business Analytics
3|2nd|BA307|3|International Business
3|2nd|BA308|3|Supply Chain Management
3|2nd|BA309|3|Digital Marketing
3|2nd|BA310|3|Project Management
3|2nd|BA311|3|Business Ethics
3|2nd|BA312|3|Business Research Project
4|1st|BA401|3|Business Policy
4|1st|BA402|3|Strategic Marketing
4|1st|BA403|3|Business Elective 1
4|1st|BA404|3|Business Elective 2
4|1st|BA405|3|Feasibility Study
4|2nd|BA406|6|Internship / Practicum
4|2nd|BA407|3|Business Elective 3
4|2nd|BA408|3|Business Integration
COURSES,
        'BSA' => <<<'COURSES'
1|1st|AC101|3|Fundamentals of Accounting
1|1st|AC102|3|Accounting 1
1|1st|AC103|3|Business Mathematics
1|1st|GE101|3|Understanding the Self
1|1st|GE102|3|Purposive Communication
1|1st|GE103|3|Mathematics in the Modern World
1|1st|PE101|2|PATHFit 1
1|1st|NSTP101|3|NSTP 1
1|2nd|AC104|3|Accounting 2
1|2nd|AC105|3|Financial Accounting and Reporting 1
1|2nd|AC106|3|Business Law
1|2nd|GE104|3|Readings in Philippine History
1|2nd|GE105|3|The Contemporary World
1|2nd|GE106|3|Art Appreciation
1|2nd|PE102|2|PATHFit 2
1|2nd|NSTP102|3|NSTP 2
2|1st|AC201|3|Intermediate Accounting 1
2|1st|AC202|3|Intermediate Accounting 2
2|1st|AC203|3|Cost Accounting
2|1st|AC204|3|Taxation
2|1st|AC205|3|Business Statistics
2|1st|GE201|3|Science, Technology and Society
2|1st|PE201|2|PATHFit 3
2|2nd|AC206|3|Intermediate Accounting 3
2|2nd|AC207|3|Management Accounting
2|2nd|AC208|3|Financial Management
2|2nd|AC209|3|Accounting Information Systems
2|2nd|AC210|3|Auditing Theory
2|2nd|GE202|3|Ethics
2|2nd|PE202|2|PATHFit 4
3|1st|AC301|3|Auditing Problems
3|1st|AC302|3|Financial Accounting and Reporting 2
3|1st|AC303|3|Advanced Accounting
3|1st|AC304|3|Income Taxation
3|1st|AC305|3|Business Finance
3|1st|AC306|3|Accounting Research
3|2nd|AC307|3|Advanced Financial Accounting
3|2nd|AC308|3|Strategic Cost Management
3|2nd|AC309|3|Auditing Applications
3|2nd|AC310|3|Partnership and Corporation Accounting
3|2nd|AC311|3|Accounting Information Systems 2
3|2nd|AC312|3|Accounting Research Project
4|1st|AC401|3|Advanced Auditing
4|1st|AC402|3|Advanced Taxation
4|1st|AC403|3|Government Accounting
4|1st|AC404|3|Accounting Elective 1
4|1st|AC405|3|Accounting Elective 2
4|2nd|AC406|6|Accounting Practicum
4|2nd|AC407|3|Accounting Elective 3
4|2nd|AC408|3|Professional Accounting Practice
COURSES,
        'BSED' => <<<'COURSES'
1|1st|ED101|3|The Teaching Profession
1|1st|ED102|3|The Learner and Learning Principles
1|1st|GE101|3|Understanding the Self
1|1st|GE102|3|Purposive Communication
1|1st|GE103|3|Mathematics in the Modern World
1|1st|GE104|3|Readings in Philippine History
1|1st|PE101|2|PATHFit 1
1|1st|NSTP101|3|NSTP 1
1|2nd|ED103|3|Child and Adolescent Development
1|2nd|ED104|3|Facilitating Learning
1|2nd|ED105|3|Educational Technology 1
1|2nd|GE105|3|The Contemporary World
1|2nd|GE106|3|Art Appreciation
1|2nd|EN101|3|Introduction to Language Studies
1|2nd|PE102|2|PATHFit 2
1|2nd|NSTP102|3|NSTP 2
2|1st|ED201|3|Principles of Teaching 1
2|1st|ED202|3|Educational Technology 2
2|1st|ED203|3|Curriculum Development
2|1st|ED204|3|Assessment in Learning 1
2|1st|EN201|3|Structure of English
2|1st|EN202|3|Literature of the Philippines
2|1st|PE201|2|PATHFit 3
2|2nd|ED205|3|Principles of Teaching 2
2|2nd|ED206|3|Assessment in Learning 2
2|2nd|ED207|3|Facilitating Learner-Centered Teaching
2|2nd|ED208|1|Field Study 1
2|2nd|EN203|3|English Grammar
2|2nd|EN204|3|Introduction to Literature
2|2nd|GE201|3|Science, Technology and Society
2|2nd|PE202|2|PATHFit 4
3|1st|ED301|1|Field Study 2
3|1st|ED302|3|Teaching Internship Preparation
3|1st|ED303|3|Classroom Management
3|1st|ED304|3|Assessment and Evaluation
3|1st|EN301|3|Teaching English in Secondary Schools
3|1st|EN302|3|English Literature
3|1st|EN303|3|World Literature
3|2nd|ED305|1|Field Study 3
3|2nd|ED306|3|Research in Education
3|2nd|ED307|3|Action Research
3|2nd|EN304|3|Language Acquisition
3|2nd|EN305|3|Stylistics
3|2nd|EN306|3|Philippine Literature
3|2nd|EN307|3|Technical Writing
4|1st|ED401|1|Field Study 4
4|1st|ED402|3|Special Topics in Education
4|1st|ED403|3|Teaching Strategies
4|1st|EN401|3|Assessment in English Language Teaching
4|1st|EN402|3|Materials Development
4|1st|EN403|3|English Language Curriculum
4|2nd|ED404|6|Practice Teaching
4|2nd|ED405|3|Seminar in Teaching Profession
4|2nd|ED406|3|Educational Research Project
COURSES,
    ];

    public function run(): void
    {
        $programIds = Program::query()->pluck('id', 'code');

        foreach (self::CURRICULA as $programCode => $rows) {
            $programId = $programIds->get($programCode);
            if (! $programId) {
                throw new RuntimeException("Cannot seed courses because program {$programCode} does not exist.");
            }

            $courseCodes = [];
            foreach (explode("\n", trim($rows)) as $row) {
                [$yearLevel, $semester, $code, $units, $title] = explode('|', $row, 5);
                $courseCodes[] = $code;

                Course::updateOrCreate(
                    ['program_id' => $programId, 'code' => $code],
                    [
                        'title' => $title,
                        'units' => (int) $units,
                        'year_level' => (int) $yearLevel,
                        'semester' => $semester,
                        'status' => 'active',
                    ],
                );
            }

            Course::query()
                ->where('program_id', $programId)
                ->whereNotIn('code', $courseCodes)
                ->where('status', 'active')
                ->update(['status' => 'archived']);
        }
    }
}
