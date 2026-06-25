<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRateLimitTable extends Migration
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
            'identifier' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'comment'    => 'IP address or IP+email combination for tracking attempts',
            ],
            'attempts' => [
                'type'       => 'TINYINT',
                'constraint' => 3,
                'unsigned'   => true,
                'default'    => 1,
                'comment'    => 'Number of failed attempts',
            ],
            'first_attempt_at' => [
                'type'    => 'DATETIME',
                'comment' => 'Timestamp of first attempt in current window',
            ],
            'blocked_until' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Timestamp when block expires, null if not blocked',
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
        $this->forge->addUniqueKey('identifier');
        $this->forge->addKey('blocked_until');
        $this->forge->addKey(['first_attempt_at', 'attempts']);

        $this->forge->createTable('rate_limits');
    }

    public function down(): void
    {
        $this->forge->dropTable('rate_limits', true);
    }
}
