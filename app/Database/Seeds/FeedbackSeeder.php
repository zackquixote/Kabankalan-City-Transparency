<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

class FeedbackSeeder extends Seeder
{
    public function run(): void
    {
        $weekAgo  = Time::now()->subDays(7)->toDateTimeString();
        $twoWeeks = Time::now()->subDays(14)->toDateTimeString();
        $monthAgo = Time::now()->subDays(30)->toDateTimeString();
        $now      = Time::now()->toDateTimeString();

        $data = [
            [
                'project_id'     => 1,
                'user_id'        => null,
                'author_name'    => 'Pedro Santillan',
                'author_email'   => 'pedro.santillan@example.com',
                'body'           => 'Maayo nga project, pero naa bay schedule sa night work? Daghan kaayo traffic during rush hour near the creek.',
                'status'         => 'addressed',
                'admin_response' => 'Night work is limited to 9 PM; signage posted at Talubangi crossing starting March.',
                'responded_by'   => 3,
                'responded_at'   => $weekAgo,
                'created_at'     => $monthAgo,
                'updated_at'     => $weekAgo,
            ],
            [
                'project_id'     => 1,
                'user_id'        => null,
                'author_name'    => 'Ana Marie Golez',
                'author_email'   => 'ana.golez@example.com',
                'body'           => 'Asa makita ang detailed bill of materials for this flood control project?',
                'status'         => 'reviewed',
                'admin_response' => 'BOM will be attached in the next portal update once procurement documents are cleared.',
                'responded_by'   => 2,
                'responded_at'   => $twoWeeks,
                'created_at'     => $twoWeeks,
                'updated_at'     => $twoWeeks,
            ],
            [
                'project_id'     => 2,
                'user_id'        => null,
                'author_name'    => 'Ricardo Abello',
                'author_email'   => 'r.abello@example.com',
                'body'           => 'Unsa nga criteria ang gigamit para sa farmer beneficiaries sa Camansi?',
                'status'         => 'pending',
                'admin_response' => null,
                'responded_by'   => null,
                'responded_at'   => null,
                'created_at'     => $weekAgo,
                'updated_at'     => $weekAgo,
            ],
            [
                'project_id'     => 2,
                'user_id'        => null,
                'author_name'    => 'Citizen Watch Negros',
                'author_email'   => 'contact@citizenwatch.example.org',
                'body'           => 'Please publish the list of awarded suppliers and delivery receipts for transparency.',
                'status'         => 'pending',
                'admin_response' => null,
                'responded_by'   => null,
                'responded_at'   => null,
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
            [
                'project_id'     => 7,
                'user_id'        => null,
                'author_name'    => 'Fisherfolk Association',
                'author_email'   => null,
                'body'           => 'Salamat sa completed landing facility. Ice plant is operational na.',
                'status'         => 'dismissed',
                'admin_response' => 'Acknowledged — no further action required for completed project.',
                'responded_by'   => 6,
                'responded_at'   => $monthAgo,
                'created_at'     => $monthAgo,
                'updated_at'     => $monthAgo,
            ],
        ];

        $this->db->table('feedback')->insertBatch($data);
    }
}
