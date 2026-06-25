<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProjectVersionsTable extends Migration
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
            'project_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'version_number' => [
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
            'change_summary' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
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
                'type'       => 'VARCHAR',
                'constraint' => 50,
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
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('project_id');
        $this->forge->addUniqueKey(['project_id', 'version_number']);

        $dbDriver = $this->db->DBDriver;
        $isSqlite = ($dbDriver === 'SQLite3');

        $this->forge->addForeignKey(
            'project_id',
            'projects',
            'id',
            'CASCADE',
            'CASCADE',
            $isSqlite ? '' : 'project_versions_project_id_foreign',
        );
        $this->forge->addForeignKey(
            'created_by',
            'users',
            'id',
            'CASCADE',
            'SET NULL',
            $isSqlite ? '' : 'project_versions_created_by_foreign',
        );

        $this->forge->createTable('project_versions');
    }

    public function down(): void
    {
        $this->forge->dropTable('project_versions', true);
    }
}
