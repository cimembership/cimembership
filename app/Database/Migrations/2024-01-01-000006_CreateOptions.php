<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOptions extends Migration
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
            'option_name' => [
                'type'       => 'VARCHAR',
                'constraint' => '64',
                'null'       => false,
                'unique'     => true,
            ],
            'option_value' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'is_serialized' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'null'       => false,
            ],
            'autoload' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
                'null'       => false,
                'comment'    => 'Load on every request',
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
        // option_name already has unique index from field definition
        $this->forge->addKey('autoload');
        $this->forge->createTable('options');

        // Insert default options
        $data = [
            [
                'option_name'   => 'site_name',
                'option_value'    => 'CIMembership',
                'is_serialized'   => 0,
                'autoload'        => 1,
                'created_at'      => date('Y-m-d H:i:s'),
            ],
            [
                'option_name'   => 'site_description',
                'option_value'    => 'CodeIgniter Membership Management System',
                'is_serialized'   => 0,
                'autoload'        => 1,
                'created_at'      => date('Y-m-d H:i:s'),
            ],
            [
                'option_name'   => 'webmaster_email',
                'option_value'    => 'admin@example.com',
                'is_serialized'   => 0,
                'autoload'        => 1,
                'created_at'      => date('Y-m-d H:i:s'),
            ],
            [
                'option_name'   => 'allow_registration',
                'option_value'    => '1',
                'is_serialized'   => 0,
                'autoload'        => 1,
                'created_at'      => date('Y-m-d H:i:s'),
            ],
            [
                'option_name'   => 'require_activation',
                'option_value'    => '1',
                'is_serialized'   => 0,
                'autoload'        => 1,
                'created_at'      => date('Y-m-d H:i:s'),
            ],
            [
                'option_name'   => 'captcha_enabled',
                'option_value'    => '0',
                'is_serialized'   => 0,
                'autoload'        => 1,
                'created_at'      => date('Y-m-d H:i:s'),
            ],
            [
                'option_name'   => 'recaptcha_enabled',
                'option_value'    => '0',
                'is_serialized'   => 0,
                'autoload'        => 1,
                'created_at'      => date('Y-m-d H:i:s'),
            ],
            [
                'option_name'   => 'login_attempts_limit',
                'option_value'    => '5',
                'is_serialized'   => 0,
                'autoload'        => 1,
                'created_at'      => date('Y-m-d H:i:s'),
            ],
            [
                'option_name'   => 'lockout_duration',
                'option_value'    => '900',
                'is_serialized'   => 0,
                'autoload'        => 1,
                'created_at'      => date('Y-m-d H:i:s'),
            ],
            [
                'option_name'   => 'password_min_length',
                'option_value'    => '8',
                'is_serialized'   => 0,
                'autoload'        => 1,
                'created_at'      => date('Y-m-d H:i:s'),
            ],
        ];

        $this->db->table('options')->insertBatch($data);
    }

    public function down(): void
    {
        $this->forge->dropTable('options');
    }
}
