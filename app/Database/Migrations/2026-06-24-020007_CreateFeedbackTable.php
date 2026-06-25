<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFeedbackTable extends Migration
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
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'author_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'author_email' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'body' => [
                'type' => 'TEXT',
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'reviewed', 'addressed', 'dismissed'],
                'default'    => 'pending',
            ],
            'admin_response' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'responded_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'responded_at' => [
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
        $this->forge->addKey('project_id');
        $this->forge->addKey('status');
        $this->forge->addKey('user_id');
        $this->forge->addKey('created_at');

        $dbDriver = $this->db->DBDriver;
        $isSqlite = ($dbDriver === 'SQLite3');

        $this->forge->addForeignKey(
            'project_id',
            'projects',
            'id',
            'CASCADE',
            'CASCADE',
            $isSqlite ? '' : 'feedback_project_id_foreign',
        );
        $this->forge->addForeignKey(
            'user_id',
            'users',
            'id',
            'CASCADE',
            'SET NULL',
            $isSqlite ? '' : 'feedback_user_id_foreign',
        );
        $this->forge->addForeignKey(
            'responded_by',
            'users',
            'id',
            'CASCADE',
            'SET NULL',
            $isSqlite ? '' : 'feedback_responded_by_foreign',
        );

        $this->forge->createTable('feedback');
    }

    public function down(): void
    {
        $this->forge->dropTable('feedback', true);
    }
}
