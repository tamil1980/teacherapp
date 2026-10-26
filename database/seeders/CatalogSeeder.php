<?php

namespace Database\Seeders;

use App\Models\Board;
use App\Models\BoardVariant;
use App\Models\ClassStandard;
use App\Models\Subject;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $boards = [
            [
                'name' => 'State Board',
                'code' => 'STATE_BOARD',
                'slug' => 'state-board',
                'description' => 'State-level boards such as Tamil Nadu, Maharashtra, Karnataka and Kerala boards.',
                'sort_order' => 1,
            ],
            [
                'name' => 'Matriculation',
                'code' => 'MATRICULATION',
                'slug' => 'matriculation',
                'description' => 'Matriculation (Class 10) and Pre-Board (Class 12) certification for open schooling and NIOS learners.',
                'sort_order' => 2,
            ],
            [
                'name' => 'CBSE',
                'code' => 'CBSE',
                'slug' => 'cbse',
                'description' => 'Central Board of Secondary Education, covering Classes 1 to 12 across India.',
                'sort_order' => 3,
            ],
            [
                'name' => 'ICSE',
                'code' => 'ICSE',
                'slug' => 'icse',
                'description' => 'Council for the Indian School Certificate Examinations.',
                'sort_order' => 4,
            ],
        ];

        foreach ($boards as $board) {
            Board::updateOrCreate(['code' => $board['code']], $board + ['is_active' => true]);
        }

        $variants = [
            ['board' => 'STATE_BOARD', 'name' => 'Tamil Nadu State Board', 'code' => 'TN_STATE_BOARD', 'state' => 'Tamil Nadu'],
            ['board' => 'STATE_BOARD', 'name' => 'Maharashtra State Board', 'code' => 'MH_STATE_BOARD', 'state' => 'Maharashtra'],
            ['board' => 'STATE_BOARD', 'name' => 'Karnataka State Board', 'code' => 'KA_STATE_BOARD', 'state' => 'Karnataka'],
            ['board' => 'STATE_BOARD', 'name' => 'Kerala State Board', 'code' => 'KL_STATE_BOARD', 'state' => 'Kerala'],
            ['board' => 'CBSE', 'name' => 'CBSE (Delhi)', 'code' => 'CBSE_DELHI', 'state' => 'Delhi'],
            ['board' => 'ICSE', 'name' => 'CISCE (Goa)', 'code' => 'ICSE_GOA', 'state' => 'Goa'],
            ['board' => 'MATRICULATION', 'name' => 'NIOS Matriculation', 'code' => 'NIOS_MATRIC', 'state' => null],
        ];

        foreach ($variants as $variant) {
            BoardVariant::updateOrCreate(
                ['code' => $variant['code']],
                [
                    'board_id' => Board::where('code', $variant['board'])->value('id'),
                    'name' => $variant['name'],
                    'state' => $variant['state'],
                    'is_active' => true,
                ]
            );
        }

        $stages = [
            'Primary' => [1, 2],
            'Upper Primary' => [3, 4, 5],
            'Middle' => [6, 7, 8],
            'Secondary' => [9, 10],
            'Senior Secondary' => [11, 12],
        ];

        foreach ($stages as $stage => $levels) {
            foreach ($levels as $level) {
                ClassStandard::updateOrCreate(
                    ['code' => 'CLASS_'.$level],
                    [
                        'name' => 'Class '.$level,
                        'level_order' => $level,
                        'stage' => $stage,
                        'is_active' => true,
                        'sort_order' => $level,
                    ]
                );
            }
        }

        // Additional non-standard levels that administrators can enable.
        ClassStandard::updateOrCreate(
            ['code' => 'PREP'],
            ['name' => 'Pre-Primary', 'level_order' => 0, 'stage' => 'Pre-Primary', 'is_active' => true, 'sort_order' => 0]
        );
        ClassStandard::updateOrCreate(
            ['code' => 'COMPETITIVE'],
            ['name' => 'Competitive Exams (JEE/NEET)', 'level_order' => 13, 'stage' => 'Competitive', 'is_active' => true, 'sort_order' => 13]
        );

        $subjects = [
            ['Mathematics', 'MATH', 'mathematics', '📐', 'Arithmetic, algebra, geometry, trigonometry and calculus for school and board preparation.'],
            ['Physics', 'PHYS', 'physics', '🔭', 'Mechanics, optics, electricity, magnetism and modern physics with practical problem solving.'],
            ['Chemistry', 'CHEM', 'chemistry', '🧪', 'Physical, organic and inorganic chemistry including laboratory techniques and board exam preparation.'],
            ['Biology', 'BIO', 'biology', '🧬', 'Botany, zoology, physiology and genetics for board and competitive examinations.'],
            ['English', 'ENG', 'english', '📚', 'Grammar, comprehension, writing skills, literature and competitive exam English.'],
            ['Tamil', 'TAMIL', 'tamil', '🗣️', 'Tamil literature, grammar, composition and spoken skills.'],
            ['Hindi', 'HINDI', 'hindi', '🗣️', 'Hindi grammar, literature, comprehension and writing practice.'],
            ['Computer Science', 'CS', 'computer-science', '💻', 'Programming fundamentals, data structures, algorithms and computer literacy.'],
            ['Social Science', 'SOCIAL', 'social-science', '🌍', 'History, geography, civics and economics for Classes 6 to 12.'],
            ['Accountancy', 'ACCT', 'accountancy', '📊', 'Book-keeping, accounting standards, company accounts and cost accounting.'],
            ['Economics', 'ECON', 'economics', '📈', 'Micro and macro economics, national income and Indian economic development.'],
            ['Commerce', 'COMM', 'commerce', '🏛️', 'Business organisation, accountancy, economics and commercial law.'],
            ['Environmental Science', 'ENVS', 'environmental-science', '🌱', 'Ecosystems, pollution, sustainability and environmental issues.'],
            ['Psychology', 'PSYCH', 'psychology', '🧠', 'Development, cognition, behaviour and preparation for psychology entrance exams.'],
            ['Other', 'OTHER', 'other', '➕', 'Any other subject not listed above — configure from the admin panel.'],
        ];

        foreach ($subjects as $index => $subject) {
            Subject::updateOrCreate(
                ['code' => $subject[1]],
                [
                    'name' => $subject[0],
                    'slug' => $subject[2],
                    'icon' => $subject[3],
                    'description' => $subject[4],
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]
            );
        }
    }
}
