<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CatalogSeeder::class,
            SettingsSeeder::class,
            PromotionalOfferSeeder::class,
            DemoUsersSeeder::class,
        ]);

        $this->command->info('');
        $this->command->info('EduBook demo data is ready.');
        $this->command->table(
            ['Role', 'Email', 'Password'],
            [
                ['Admin', 'admin@edubook.test', 'admin@1234'],
                ['Parent', 'parent@edubook.test', 'parent@1234'],
                ['Teacher', 'lakshmi@edubook.test', 'teacher@1234'],
            ]
        );
    }
}
