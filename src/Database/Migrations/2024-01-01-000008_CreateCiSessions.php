<?php

declare(strict_types=1);

namespace CIMembership\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Create CI Sessions Migration
 *
 * @package CIMembership\Database\Migrations
 */
class CreateCiSessions extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'       => 'VARCHAR',
                'constraint' => '40',
                'null'       => false,
            ],
            'ip_address' => [
                'type'       => 'VARCHAR',
                'constraint' => '45',
                'null'       => false,
            ],
            'user_agent' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
            ],
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'timestamp' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => false,
                'default'    => 0,
            ],
            'data' => [
                'type' => 'BLOB',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('timestamp');
        $this->forge->addKey('user_id');
        $this->forge->createTable('ci_sessions');
    }

    public function down(): void
    {
        $this->forge->dropTable('ci_sessions');
    }
}
