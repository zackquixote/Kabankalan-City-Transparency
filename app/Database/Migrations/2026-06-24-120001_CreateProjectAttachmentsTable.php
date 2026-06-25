<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProjectAttachmentsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 10,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'project_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
            ],
            'uploaded_by' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => true,
                'default'    => null,
            ],
            // Original filename as uploaded (display only — never used for disk access)
            'original_filename' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            // UUID-based name on disk: e.g. "550e8400-e29b-41d4-a716-446655440000.pdf"
            'stored_filename' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'mime_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'file_size' => [
                'type'     => 'INT',
                'unsigned' => true,
                'comment'  => 'Bytes',
            ],
            // Optional human-readable label set by the uploader
            'label' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'default'    => null,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('project_id');
        $this->forge->addUniqueKey('stored_filename');
        $this->forge->addForeignKey('project_id', 'projects', 'id', 'CASCADE', 'CASCADE');

        $this->forge->createTable('project_attachments');
    }

    public function down(): void
    {
        $this->forge->dropTable('project_attachments', true);
    }
}
