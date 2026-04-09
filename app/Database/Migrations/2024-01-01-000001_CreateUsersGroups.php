<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUsersGroups extends Migration
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
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'null'       => false,
            ],
            'description' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
            ],
            'status' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
                'null'       => false,
                'comment'    => '1=active, 0=inactive',
            ],
            'permissions' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'is_default' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'null'       => false,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('status');
        $this->forge->createTable('users_groups');

        // Insert default groups
        $data = [
            [
                'id'          => 1,
                'name'        => 'Super Administrator',
                'description' => 'Full system access with all permissions',
                'status'      => 1,
                'permissions' => json_encode([
                    'access_backend'        => '1',
                    'view_users'            => '1',
                    'create_users'          => '1',
                    'edit_users'            => '1',
                    'delete_users'          => '1',
                    'view_user_groups'      => '1',
                    'create_user_groups'    => '1',
                    'edit_user_groups'      => '1',
                    'delete_user_groups'    => '1',
                    'general_settings'      => '1',
                    'login_to_frontend'     => '1',
                ]),
                'is_default'   => 0,
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ],
            [
                'id'          => 2,
                'name'        => 'Administrator',
                'description' => 'Admin access with most permissions',
                'status'      => 1,
                'permissions' => json_encode([
                    'access_backend'        => '1',
                    'view_users'            => '1',
                    'create_users'          => '1',
                    'edit_users'            => '1',
                    'view_user_groups'      => '1',
                    'general_settings'    => '1',
                    'login_to_frontend'     => '1',
                ]),
                'is_default'   => 0,
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ],
            [
                'id'          => 3,
                'name'        => 'Manager',
                'description' => 'Can manage users but not groups',
                'status'      => 1,
                'permissions' => json_encode([
                    'access_backend'        => '1',
                    'view_users'            => '1',
                    'edit_users'            => '1',
                    'login_to_frontend'     => '1',
                ]),
                'is_default'   => 0,
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ],
            [
                'id'          => 4,
                'name'        => 'Editor',
                'description' => 'Content editor with limited access',
                'status'      => 1,
                'permissions' => json_encode([
                    'login_to_frontend'     => '1',
                ]),
                'is_default'   => 0,
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ],
            [
                'id'          => 5,
                'name'        => 'Member',
                'description' => 'Standard member with frontend access only',
                'status'      => 1,
                'permissions' => json_encode([
                    'login_to_frontend'     => '1',
                ]),
                'is_default'   => 1,
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ],
        ];

        $this->db->table('users_groups')->insertBatch($data);
    }

    public function down(): void
    {
        $this->forge->dropTable('users_groups');
    }
}
