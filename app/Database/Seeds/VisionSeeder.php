<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

class VisionSeeder extends Seeder
{
    public function run(): void
    {
        $now = Time::now()->toDateTimeString();

        $data = [
            [
                'title'       => 'Resilient Kabankalan 2025–2030',
                'description' => 'Strengthen disaster preparedness, flood control, and climate-adaptive infrastructure across all 32 barangays.',
                'start_year'  => 2025,
                'end_year'    => 2030,
                'is_active'   => 1,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'title'       => 'Prosperous Countryside',
                'description' => 'Boost farm productivity, market access, and livelihood programs for sugarcane and diversified agriculture.',
                'start_year'  => 2024,
                'end_year'    => 2029,
                'is_active'   => 1,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'title'       => 'Healthy Kabankalan',
                'description' => 'Expand primary care, maternal health, and barangay health station upgrades citywide.',
                'start_year'  => 2024,
                'end_year'    => 2028,
                'is_active'   => 1,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'title'       => 'Digital Governance & Transparency',
                'description' => 'Modernize LGU services, open budget data, and citizen engagement platforms.',
                'start_year'  => 2025,
                'end_year'    => 2027,
                'is_active'   => 1,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            [
                'title'       => 'Livable Urban Centers (Legacy)',
                'description' => 'Archived vision from the prior administration covering downtown revitalization.',
                'start_year'  => 2019,
                'end_year'    => 2023,
                'is_active'   => 0,
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
        ];

        $this->db->table('visions')->insertBatch($data);
    }
}
