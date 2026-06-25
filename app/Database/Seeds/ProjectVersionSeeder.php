<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

class ProjectVersionSeeder extends Seeder
{
    public function run(): void
    {
        $v1 = Time::now()->subMonths(4)->toDateTimeString();
        $v2 = Time::now()->subMonths(2)->toDateTimeString();
        $v3 = Time::now()->subMonths(1)->toDateTimeString();

        $data = [
            // Project 1: Talubangi Flood Control – budget revised upward
            [
                'project_id'       => 1,
                'version_number'   => 1,
                'created_by'       => 3,
                'change_summary'   => 'Initial submission with original cost estimate.',
                'title'            => 'Talubangi Flood Control Phase II',
                'description'      => 'Reinforced concrete revetment along Binicuil Creek segment in Brgy. Talubangi.',
                'status'           => 'submitted',
                'allocated_amount' => '11000000.00',
                'obligated_amount' => '0.00',
                'disbursed_amount' => '0.00',
                'created_at'       => $v1,
            ],
            [
                'project_id'       => 1,
                'version_number'   => 2,
                'created_by'       => 3,
                'change_summary'   => 'Adjusted allocation after geotechnical survey; added riprap section.',
                'title'            => 'Talubangi Flood Control Phase II',
                'description'      => 'Reinforced concrete revetment and riprap along Binicuil Creek, Brgy. Talubangi.',
                'status'           => 'approved',
                'allocated_amount' => '12500000.00',
                'obligated_amount' => '0.00',
                'disbursed_amount' => '0.00',
                'created_at'       => $v2,
            ],
            [
                'project_id'       => 1,
                'version_number'   => 3,
                'created_by'       => 2,
                'change_summary'   => 'Published version reflecting obligation and partial disbursement.',
                'title'            => 'Talubangi Flood Control Phase II',
                'description'      => 'Reinforced concrete revetment and riprap along Binicuil Creek, Brgy. Talubangi.',
                'status'           => 'published',
                'allocated_amount' => '12500000.00',
                'obligated_amount' => '9800000.00',
                'disbursed_amount' => '6200000.00',
                'created_at'       => $v3,
            ],
            // Project 2: Mechanization – scope clarified
            [
                'project_id'       => 2,
                'version_number'   => 1,
                'created_by'       => 6,
                'change_summary'   => 'Original draft proposal.',
                'title'            => 'Sugarcane Mechanization Support Package',
                'description'      => 'Equipment subsidy for upland farmers.',
                'status'           => 'draft',
                'allocated_amount' => '4000000.00',
                'obligated_amount' => '0.00',
                'disbursed_amount' => '0.00',
                'created_at'       => $v1,
            ],
            [
                'project_id'       => 2,
                'version_number'   => 2,
                'created_by'       => 6,
                'change_summary'   => 'Added training component; increased allocation.',
                'title'            => 'Sugarcane Mechanization Support Package',
                'description'      => 'Subsidized tractor attachments and training for smallholder farmers in upland barangays.',
                'status'           => 'published',
                'allocated_amount' => '4500000.00',
                'obligated_amount' => '4500000.00',
                'disbursed_amount' => '2100000.00',
                'created_at'       => $v2,
            ],
            // Project 4: Portal – single version snapshot at review
            [
                'project_id'       => 4,
                'version_number'   => 1,
                'created_by'       => 2,
                'change_summary'   => 'Submitted to BAC for technical review.',
                'title'            => 'Open Budget Portal & Citizen Feedback Module',
                'description'      => 'Development and hosting of the Kabankalan City budget transparency web portal.',
                'status'           => 'under_review',
                'allocated_amount' => '1500000.00',
                'obligated_amount' => '0.00',
                'disbursed_amount' => '0.00',
                'created_at'       => $v3,
            ],
        ];

        $this->db->table('project_versions')->insertBatch($data);
    }
}
