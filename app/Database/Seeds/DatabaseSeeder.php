<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(VisionSeeder::class);
        $this->call(OfficeSeeder::class);
        $this->call(BudgetCycleStageSeeder::class);
        $this->call(UserSeeder::class);
        $this->call(ProjectSeeder::class);
        $this->call(ProjectVersionSeeder::class);
        $this->call(FeedbackSeeder::class);
    }
}
