<?php

namespace Database\Seeders;

use App\Models\Board;
use App\Models\ClassStandard;
use App\Models\Subject;
use App\Models\User;
use App\Services\RegistrationService;
use Illuminate\Database\Seeder;

/**
 * Creates the demo administrator, demo parents and demo teachers with
 * realistic Indian names, subjects, boards, classes and fee structures.
 */
class DemoUsersSeeder extends Seeder
{
    public function __construct(private readonly RegistrationService $registration) {}

    public function run(): void
    {
        $this->createAdmin();
        $this->createParents();
        $this->createTeachers();
    }

    private function createAdmin(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@edubook.test'],
            [
                'name' => 'Platform Administrator',
                'mobile' => '9000000001',
                'password' => bcrypt('admin@1234'),
                'role' => 'admin',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );
    }

    private function createParents(): void
    {
        $parents = [
            [
                'name' => 'Ramesh Iyer',
                'email' => 'parent@edubook.test',
                'mobile' => '9812345670',
                'password' => 'parent@1234',
                'city' => 'Chennai',
                'state' => 'Tamil Nadu',
                'pincode' => '600020',
                'occupation' => 'Engineer',
            ],
            [
                'name' => 'Ananya Deshmukh',
                'email' => 'parent2@edubook.test',
                'mobile' => '9812345671',
                'password' => 'parent@1234',
                'city' => 'Pune',
                'state' => 'Maharashtra',
                'pincode' => '411001',
                'occupation' => 'Doctor',
            ],
            [
                'name' => 'Suresh Nair',
                'email' => 'parent3@edubook.test',
                'mobile' => '9812345672',
                'password' => 'parent@1234',
                'city' => 'Kochi',
                'state' => 'Kerala',
                'pincode' => '682001',
                'occupation' => 'Business owner',
            ],
        ];

        foreach ($parents as $parent) {
            $existing = User::where('email', $parent['email'])->first();

            if ($existing) {
                continue;
            }

            $this->registration->registerParent($parent);
        }
    }

    private function createTeachers(): void
    {
        $teachers = [
            [
                'name' => 'Dr. Lakshmi Narayanan',
                'email' => 'lakshmi@edubook.test',
                'mobile' => '9000000011',
                'password' => 'teacher@1234',
                'headline' => 'Physics & Mathematics Faculty — 12 years in CBSE and State Board',
                'bio' => 'I have been teaching Physics and Mathematics to school students for more than a decade and love making board-exam topics feel simple.',
                'teaching_description' => 'Concept-first teaching with practical examples, weekly revision tests and a focus on Class 10 and Class 12 board patterns.',
                'methodology' => 'Visual explanations, formula sheets, past-paper drills and a monthly progress report shared with parents.',
                'experience_years' => 12,
                'languages' => ['English', 'Tamil'],
                'qualification_summary' => 'M.Sc. Physics, B.Ed.',
                'city' => 'Chennai',
                'state' => 'Tamil Nadu',
                'pincode' => '600020',
                'latitude' => 13.0330,
                'longitude' => 80.2650,
                'service_radius_km' => 15,
                'boards' => ['CBSE', 'STATE_BOARD', 'MATRICULATION'],
                'classes' => ['CLASS_9', 'CLASS_10', 'CLASS_11', 'CLASS_12'],
                'subjects' => ['PHYS', 'MATH'],
                'modes' => ['online', 'offline'],
                'status' => 'approved',
                'fees' => [
                    ['subject' => 'PHYS', 'mode' => 'online', 'rate' => 500, 'minutes' => 60],
                    ['subject' => 'PHYS', 'mode' => 'offline', 'rate' => 550, 'minutes' => 60],
                    ['subject' => 'MATH', 'mode' => 'online', 'rate' => 450, 'minutes' => 60],
                    ['subject' => 'MATH', 'mode' => 'offline', 'rate' => 500, 'minutes' => 60],
                ],
                'availability' => [1, 2, 3, 4, 5, 6],
            ],
            [
                'name' => 'Suresh Kumar',
                'email' => 'suresh@edubook.test',
                'mobile' => '9000000012',
                'password' => 'teacher@1234',
                'headline' => 'Chemistry Specialist for ICSE and CBSE Classes 9 to 12',
                'bio' => 'Chemistry made simple through real-world experiments and clear reaction maps.',
                'teaching_description' => 'Specialise in organic reaction mechanisms, numerical problems and lab safety. Extensive board question practice.',
                'methodology' => 'NCERT line-by-line coverage, weekly tests and doubt-clearing sessions.',
                'experience_years' => 8,
                'languages' => ['English', 'Hindi'],
                'qualification_summary' => 'M.Sc. Chemistry, B.Ed.',
                'city' => 'Pune',
                'state' => 'Maharashtra',
                'pincode' => '411001',
                'latitude' => 18.5204,
                'longitude' => 73.8567,
                'service_radius_km' => 12,
                'boards' => ['CBSE', 'ICSE'],
                'classes' => ['CLASS_9', 'CLASS_10', 'CLASS_11', 'CLASS_12'],
                'subjects' => ['CHEM'],
                'modes' => ['online', 'offline'],
                'status' => 'approved',
                'fees' => [
                    ['subject' => 'CHEM', 'mode' => 'online', 'rate' => 600, 'minutes' => 60],
                    ['subject' => 'CHEM', 'mode' => 'offline', 'rate' => 650, 'minutes' => 60],
                ],
                'availability' => [1, 2, 3, 4, 5],
            ],
            [
                'name' => 'Meera Krishnan',
                'email' => 'meera@edubook.test',
                'mobile' => '9000000013',
                'password' => 'teacher@1234',
                'headline' => 'English & Tamil Language Trainer',
                'bio' => 'Certified English and Tamil language trainer helping students improve grammar, writing and spoken fluency.',
                'teaching_description' => 'Grammar, vocabulary, comprehension and essay writing with a focus on Class 10 board answer presentation.',
                'methodology' => 'Daily practice sheets, vocabulary journals and guided writing tasks.',
                'experience_years' => 6,
                'languages' => ['English', 'Tamil', 'Malayalam'],
                'qualification_summary' => 'M.A. English, B.Ed.',
                'city' => 'Chennai',
                'state' => 'Tamil Nadu',
                'pincode' => '600042',
                'latitude' => 13.0380,
                'longitude' => 80.2340,
                'service_radius_km' => 10,
                'boards' => ['STATE_BOARD', 'CBSE', 'MATRICULATION'],
                'classes' => ['CLASS_6', 'CLASS_7', 'CLASS_8', 'CLASS_9', 'CLASS_10'],
                'subjects' => ['ENG', 'TAMIL'],
                'modes' => ['online', 'offline'],
                'status' => 'approved',
                'fees' => [
                    ['subject' => 'ENG', 'mode' => 'online', 'rate' => 350, 'minutes' => 60],
                    ['subject' => 'ENG', 'mode' => 'offline', 'rate' => 400, 'minutes' => 60],
                    ['subject' => 'TAMIL', 'mode' => 'online', 'rate' => 300, 'minutes' => 60],
                ],
                'availability' => [1, 3, 5, 6],
            ],
            [
                'name' => 'Arjun Verma',
                'email' => 'arjun@edubook.test',
                'mobile' => '9000000014',
                'password' => 'teacher@1234',
                'headline' => 'Mathematics Tutor — Class 1 to 12 and Competitive Exams',
                'bio' => 'Mathematics for school students and JEE/NEET aspirants, with a calm and structured approach.',
                'teaching_description' => 'Complete syllabus coverage, problem-solving speed drills and mock test analysis.',
                'methodology' => 'Concept clarity, graded practice and performance tracking for every student.',
                'experience_years' => 10,
                'languages' => ['English', 'Hindi'],
                'qualification_summary' => 'M.Tech., IIT Delhi',
                'city' => 'New Delhi',
                'state' => 'Delhi',
                'pincode' => '110019',
                'latitude' => 28.5355,
                'longitude' => 77.2410,
                'service_radius_km' => 20,
                'boards' => ['CBSE', 'ICSE'],
                'classes' => ['CLASS_6', 'CLASS_7', 'CLASS_8', 'CLASS_9', 'CLASS_10', 'CLASS_11', 'CLASS_12', 'COMPETITIVE'],
                'subjects' => ['MATH'],
                'modes' => ['online'],
                'status' => 'approved',
                'fees' => [
                    ['subject' => 'MATH', 'mode' => 'online', 'rate' => 700, 'minutes' => 90],
                ],
                'availability' => [1, 2, 3, 4, 5, 6, 0],
            ],
            [
                'name' => 'Priya Menon',
                'email' => 'priya@edubook.test',
                'mobile' => '9000000015',
                'password' => 'teacher@1234',
                'headline' => 'Biology Teacher — NEET & Class 10/12 Boards',
                'bio' => 'Biology taught with diagrams, mnemonics and regular revision to build confidence.',
                'teaching_description' => 'NCERT-based chapters, diagram practice and NEET-level question drills.',
                'methodology' => 'Visual learning, spaced revision and personalised doubt clearing.',
                'experience_years' => 5,
                'languages' => ['English', 'Malayalam'],
                'qualification_summary' => 'M.Sc. Botany, B.Ed.',
                'city' => 'Kochi',
                'state' => 'Kerala',
                'pincode' => '682001',
                'latitude' => 9.9312,
                'longitude' => 76.2673,
                'service_radius_km' => 12,
                'boards' => ['CBSE', 'STATE_BOARD'],
                'classes' => ['CLASS_9', 'CLASS_10', 'CLASS_11', 'CLASS_12'],
                'subjects' => ['BIO'],
                'modes' => ['online', 'offline'],
                'status' => 'approved',
                'fees' => [
                    ['subject' => 'BIO', 'mode' => 'online', 'rate' => 480, 'minutes' => 60],
                    ['subject' => 'BIO', 'mode' => 'offline', 'rate' => 520, 'minutes' => 60],
                ],
                'availability' => [2, 4, 6],
            ],
            [
                'name' => 'Ravi Shankar',
                'email' => 'ravi@edubook.test',
                'mobile' => '9000000016',
                'password' => 'teacher@1234',
                'headline' => 'Computer Science & Accountancy Faculty',
                'bio' => 'Teaching programming, computer science and accountancy with practical project work.',
                'teaching_description' => 'Python, C++, spreadsheets and financial accounting for boards and skill building.',
                'methodology' => 'Project-led learning and workbook practice.',
                'experience_years' => 7,
                'languages' => ['English', 'Tamil'],
                'qualification_summary' => 'M.C.S., CA (Inter)',
                'city' => 'Chennai',
                'state' => 'Tamil Nadu',
                'pincode' => '600042',
                'latitude' => 13.0100,
                'longitude' => 80.2200,
                'service_radius_km' => 14,
                'boards' => ['CBSE', 'STATE_BOARD', 'ICSE'],
                'classes' => ['CLASS_9', 'CLASS_10', 'CLASS_11', 'CLASS_12'],
                'subjects' => ['CS', 'ACCT'],
                'modes' => ['online', 'offline'],
                'status' => 'approved',
                'fees' => [
                    ['subject' => 'CS', 'mode' => 'online', 'rate' => 550, 'minutes' => 60],
                    ['subject' => 'ACCT', 'mode' => 'online', 'rate' => 600, 'minutes' => 60],
                    ['subject' => 'ACCT', 'mode' => 'offline', 'rate' => 650, 'minutes' => 60],
                ],
                'availability' => [1, 2, 3, 4, 5],
            ],
            [
                'name' => 'Nisha Patel',
                'email' => 'nisha@edubook.test',
                'mobile' => '9000000017',
                'password' => 'teacher@1234',
                'headline' => 'Social Science & Economics Tutor',
                'bio' => 'History, geography and economics taught with map work and answer framing.',
                'teaching_description' => 'Answer writing practice, map work and timeline revision for board exams.',
                'methodology' => 'Visual notes, weekly tests and answer-frame drills.',
                'experience_years' => 4,
                'languages' => ['English', 'Gujarati', 'Hindi'],
                'qualification_summary' => 'B.A. Economics, B.Ed.',
                'city' => 'Ahmedabad',
                'state' => 'Gujarat',
                'pincode' => '380015',
                'latitude' => 23.0225,
                'longitude' => 72.5714,
                'service_radius_km' => 15,
                'boards' => ['STATE_BOARD', 'CBSE'],
                'classes' => ['CLASS_8', 'CLASS_9', 'CLASS_10'],
                'subjects' => ['SOCIAL', 'ECON'],
                'modes' => ['online'],
                'status' => 'pending',
                'fees' => [
                    ['subject' => 'SOCIAL', 'mode' => 'online', 'rate' => 380, 'minutes' => 60],
                    ['subject' => 'ECON', 'mode' => 'online', 'rate' => 420, 'minutes' => 60],
                ],
                'availability' => [1, 3, 5],
            ],
        ];

        foreach ($teachers as $data) {
            if (User::where('email', $data['email'])->exists()) {
                continue;
            }

            $user = $this->registration->registerTeacher($data);
            $teacher = $user->teacherProfile;

            $teacher->update([
                'verification_status' => $data['status'],
                'verified_at' => $data['status'] === 'approved' ? now() : null,
                'verified_by' => $data['status'] === 'approved' ? User::where('email', 'admin@edubook.test')->value('id') : null,
                'accepts_online' => in_array('online', $data['modes'], true),
                'accepts_offline' => in_array('offline', $data['modes'], true),
                'default_session_minutes' => 60,
                'cancellation_policy' => 'Free cancellation up to 24 hours before the class. Later cancellations are charged as shown at checkout.',
            ]);

            foreach ($data['boards'] as $boardCode) {
                $teacher->teacherBoards()->create(['board_id' => Board::where('code', $boardCode)->value('id')]);
            }

            foreach ($data['classes'] as $classCode) {
                $teacher->teacherClassStandards()->create([
                    'class_standard_id' => ClassStandard::where('code', $classCode)->value('id'),
                ]);
            }

            foreach ($data['subjects'] as $subjectCode) {
                $teacher->teacherSubjects()->create(['subject_id' => Subject::where('code', $subjectCode)->value('id')]);
            }

            foreach ($data['modes'] as $mode) {
                $teacher->teachingModes()->create(['mode' => $mode]);
            }

            $teacher->locations()->create([
                'label' => $data['city'].' Teaching Centre',
                'type' => 'teacher_location',
                'address_line1' => 'Main Road, '.$data['city'],
                'city' => $data['city'],
                'state' => $data['state'],
                'pincode' => $data['pincode'],
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'travel_charge' => 0,
                'is_default' => true,
                'is_active' => true,
            ]);

            foreach ($data['fees'] as $fee) {
                $teacher->fees()->create([
                    'subject_id' => Subject::where('code', $fee['subject'])->value('id'),
                    'mode' => $fee['mode'],
                    'hourly_rate' => $fee['rate'],
                    'session_minutes' => $fee['minutes'],
                    'travel_charge' => 0,
                    'is_active' => true,
                ]);
            }

            foreach ($data['availability'] as $weekday) {
                foreach ([['17:00', '19:00'], ['19:30', '21:00']] as [$from, $to]) {
                    $teacher->availabilityRules()->create([
                        'weekday' => $weekday,
                        'start_time' => $from,
                        'end_time' => $to,
                        'mode' => 'both',
                        'session_minutes' => 60,
                        'buffer_minutes' => 0,
                        'is_active' => true,
                    ]);
                }
            }

            $teacher->qualifications()->create([
                'degree' => $data['qualification_summary'],
                'institution' => 'University of Chennai',
                'board_university' => 'UGC',
                'year_of_passing' => '2014',
                'specialisation' => $data['headline'],
                'verification_status' => $data['status'] === 'approved' ? 'approved' : 'pending',
                'reviewed_at' => $data['status'] === 'approved' ? now() : null,
            ]);
        }
    }
}
