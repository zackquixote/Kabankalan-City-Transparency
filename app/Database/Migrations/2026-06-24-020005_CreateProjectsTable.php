<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProjectsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'vision_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'office_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'budget_cycle_stage_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'created_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'project_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => [
                    'draft',
                    'submitted',
                    'under_review',
                    'approved',
                    'published',
                    'completed',
                    'cancelled',
                ],
                'default' => 'draft',
            ],
            'fiscal_year' => [
                'type'       => 'SMALLINT',
                'constraint' => 4,
                'unsigned'   => true,
            ],
            'barangay' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'allocated_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'default'    => '0.00',
            ],
            'obligated_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'default'    => '0.00',
            ],
            'disbursed_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '15,2',
                'default'    => '0.00',
            ],
            'target_completion_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'published_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('project_code');
        $this->forge->addKey('status');
        $this->forge->addKey('fiscal_year');
        $this->forge->addKey('vision_id');
        $this->forge->addKey('office_id');
        $this->forge->addKey('budget_cycle_stage_id');
        $this->forge->addKey('created_by');
        $this->forge->addKey(['status', 'fiscal_year']);
        $this->forge->addKey('published_at');

        $dbDriver = $this->db->DBDriver;
        $isSqlite = ($dbDriver === 'SQLite3');

        $this->forge->addForeignKey(
            'vision_id',
            'visions',
            'id',
            'CASCADE',
            'RESTRICT',
            $isSqlite ? '' : 'projects_vision_id_foreign',
        );
        $this->forge->addForeignKey(
            'office_id',
            'offices',
            'id',
            'CASCADE',
            'RESTRICT',
            $isSqlite ? '' : 'projects_office_id_foreign',
        );
        $this->forge->addForeignKey(
            'budget_cycle_stage_id',
            'budget_cycle_stages',
            'id',
            'CASCADE',
            'RESTRICT',
            $isSqlite ? '' : 'projects_budget_cycle_stage_id_foreign',
        );
        $this->forge->addForeignKey(
            'created_by',
            'users',
            'id',
            'CASCADE',
            'SET NULL',
            $isSqlite ? '' : 'projects_created_by_foreign',
        );

        $this->forge->createTable('projects');
    }

    public function down(): void
    {
        $this->forge->dropTable('projects', true);
    }
}
