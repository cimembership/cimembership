<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CIMembership\Libraries\Auth\PasswordHasher;

/**
 * CIMembership Database Seeder
 *
 * Creates initial admin user and sample data.
 *
 * @package CIMembership\Database\Seeds
 */
class CIMembershipSeeder extends Seeder
{
    public function run(): void
    {
        $hasher = new PasswordHasher();

        // Create admin user
        $data = [
            'username'       => 'admin',
            'email'          => 'admin@example.com',
            'password_hash'  => $hasher->hash('admin123'),
            'group_id'       => 1, // Super Administrator
            'status'         => 'active',
            'last_login_at'  => date('Y-m-d H:i:s'),
            'created_at'     => date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s'),
        ];

        // Insert user - CodeIgniter automatically adds DBPrefix
        $this->db->table('users')->insert($data);
        $userId = $this->db->insertID();

        // Create profile - CodeIgniter automatically adds DBPrefix
        $this->db->table('user_profiles')->insert([
            'user_id'     => $userId,
            'first_name'  => 'Super',
            'last_name'   => 'Administrator',
            'timezone'    => 'UTC',
            'locale'      => 'en',
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        echo "Admin user created successfully!\n";
        echo "Username: admin\n";
        echo "Password: admin123\n";
        echo "\nIMPORTANT: Please change the default password immediately!\n";
    }
}
