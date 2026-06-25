<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

class BudgetCycleStageSeeder extends Seeder
{
    public function run(): void
    {
        $now = Time::now()->toDateTimeString();

        $data = [
            [
                'slug'        => 'planning',
                'name'        => 'Planning',
                'description' => 'Offices draft proposals and cost estimates for the upcoming fiscal year.',
                'sort_order'  => 1,
                'is_active'   => 1,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'slug'        => 'submission',
                'name'        => 'Submission',
                'description' => 'Proposals are formally submitted to CPDO for consolidation.',
                'sort_order'  => 2,
                'is_active'   => 1,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'slug'        => 'review',
                'name'        => 'Review',
                'description' => 'Technical and financial review by department heads and BAC.',
                'sort_order'  => 3,
                'is_active'   => 1,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'slug'        => 'approval',
                'name'        => 'Approval',
                'description' => 'Sanggunian approval and executive certification.',
                'sort_order'  => 4,
                'is_active'   => 1,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'slug'        => 'publication',
                'name'        => 'Publication',
                'description' => 'Approved projects published for public transparency.',
                'sort_order'  => 5,
                'is_active'   => 1,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'slug'        => 'monitoring',
                'name'        => 'Monitoring',
                'description' => 'Ongoing tracking of obligations, disbursements, and physical accomplishment.',
                'sort_order'  => 6,
                'is_active'   => 1,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
        ];

        $this->db->table('budget_cycle_stages')->insertBatch($data);
    }
}
