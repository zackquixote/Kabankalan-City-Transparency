<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddGeoToProjectsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('projects', [
            'latitude' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,7',
                'null'       => true,
                'default'    => null,
                'after'      => 'barangay',
            ],
            'longitude' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,7',
                'null'       => true,
                'default'    => null,
                'after'      => 'latitude',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('projects', ['latitude', 'longitude']);
    }
}
