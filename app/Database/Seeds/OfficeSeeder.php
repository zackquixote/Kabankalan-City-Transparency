<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

class OfficeSeeder extends Seeder
{
    public function run(): void
    {
        $now = Time::now()->toDateTimeString();

        $data = [
            [
                'code'          => 'CMO',
                'name'          => 'City Mayor\'s Office',
                'description'   => 'Executive leadership and cross-department coordination.',
                'contact_email' => 'cmo@kabankalan.gov.ph',
                'is_active'     => 1,
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'code'          => 'CPDO',
                'name'          => 'City Planning & Development Office',
                'description'   => 'Comprehensive land use, AIP preparation, and project monitoring.',
                'contact_email' => 'cpdo@kabankalan.gov.ph',
                'is_active'     => 1,
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'code'          => 'CEO',
                'name'          => 'City Engineering Office',
                'description'   => 'Public works, roads, drainage, and building maintenance.',
                'contact_email' => 'engineering@kabankalan.gov.ph',
                'is_active'     => 1,
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'code'          => 'CHO',
                'name'          => 'City Health Office',
                'description'   => 'Public health programs and rural health unit operations.',
                'contact_email' => 'health@kabankalan.gov.ph',
                'is_active'     => 1,
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'code'          => 'CSWDO',
                'name'          => 'City Social Welfare & Development Office',
                'description'   => 'Social protection, 4Ps coordination, and crisis assistance.',
                'contact_email' => 'cswdo@kabankalan.gov.ph',
                'is_active'     => 1,
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'code'          => 'CAO',
                'name'          => 'City Agriculture Office',
                'description'   => 'Farm extension, seeds distribution, and fishery support.',
                'contact_email' => 'agriculture@kabankalan.gov.ph',
                'is_active'     => 1,
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'code'          => 'CTO',
                'name'          => 'City Treasurer\'s Office',
                'description'   => 'Revenue collection, disbursement, and budget execution tracking.',
                'contact_email' => 'treasurer@kabankalan.gov.ph',
                'is_active'     => 1,
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
        ];

        $this->db->table('offices')->insertBatch($data);
    }
}
