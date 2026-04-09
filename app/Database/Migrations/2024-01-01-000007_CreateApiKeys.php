<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateApiKeys extends Migration
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
            'user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'null'       => false,
            ],
            'api_key' => [
                'type'       => 'VARCHAR',
                'constraint' => '128',
                'null'       => false,
                'unique'     => true,
            ],
            'api_secret' => [
                'type'       => 'VARCHAR',
                'constraint' => '128',
                'null'       => false,
            ],
            'permissions' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'allowed_ips' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'last_used_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'status' => [
                'type'       => "ENUM('active', 'inactive', 'revoked')",
                'default'    => 'active',
                'null'       => false,
            ],
            'expires_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addKey('api_key');
        $this->forge->addKey('status');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('api_keys');
    }

    public function down(): void
    {
        $this->forge->dropTable('api_keys');
    }
}
