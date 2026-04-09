<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class UpgradeFromV3 extends BaseCommand
{
    protected $group       = 'CIMembership';
    protected $name        = 'upgrade:fromv3';
    protected $description = 'Upgrade data from CIMembership v3 (CI3) to v4 (CI4)';
    protected $usage       = 'upgrade:fromv3 [options]';
    protected $options     = [
        '--source-db'   => 'Source CI3 database name',
        '--source-host' => 'Source CI3 database host',
        '--source-user' => 'Source CI3 database user',
        '--source-pass' => 'Source CI3 database password',
        '--source-prefix' => 'Source CI3 table prefix (default: ci_)',
        '--dry-run'     => 'Run without making changes',
    ];

    private $dryRun = false;
    private $sourcePrefix = 'ci_';
    private $sourceDb = null;
    private $targetDb = null;

    public function run(array $params): int
    {
        CLI::write('╔══════════════════════════════════════════════════╗', 'cyan');
        CLI::write('║     CIMembership v3 to v4 Data Migration         ║', 'cyan');
        CLI::write('╚══════════════════════════════════════════════════╝', 'cyan');
        CLI::newLine();
        CLI::write('⚠️  IMPORTANT: Backup your databases before proceeding!', 'yellow');
        CLI::newLine();

        $this->dryRun = array_key_exists('dry-run', $params) || CLI::getOption('dry-run');

        if ($this->dryRun) {
            CLI::write('🔍 DRY RUN MODE - No changes will be made', 'cyan');
            CLI::newLine();
        }

        // Get source database connection
        $sourceConfig = $this->getSourceDatabaseConfig($params);
        if (!$sourceConfig) {
            return 1;
        }

        try {
            $this->sourceDb = new \PDO(
                "mysql:host={$sourceConfig['host']};dbname={$sourceConfig['database']};charset=utf8mb4",
                $sourceConfig['username'],
                $sourceConfig['password'],
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );
            $this->sourcePrefix = $sourceConfig['prefix'] ?? 'ci_';
        } catch (\PDOException $e) {
            CLI::error('Failed to connect to source database: ' . $e->getMessage());
            return 1;
        }

        // Target database (CI4)
        $this->targetDb = db_connect();

        // Check target is ready
        if (!$this->targetDb->tableExists('users')) {
            CLI::error('Target database not initialized. Run: php spark migrate');
            return 1;
        }

        // Confirm migration
        if (!$this->dryRun && !CLI::prompt('Ready to migrate? Data will be copied to your CI4 database.', ['y', 'n']) === 'y') {
            CLI::write('Migration cancelled.');
            return 0;
        }

        // Run migrations
        $stats = [
            'user_groups' => $this->migrateUserGroups(),
            'users'       => $this->migrateUsers(),
            'profiles'    => $this->migrateProfiles(),
            'options'     => $this->migrateOptions(),
            'oauth'       => $this->migrateOAuth(),
        ];

        // Summary
        CLI::newLine();
        CLI::write('╔════════════════════════════════════════╗', 'green');
        CLI::write('║     Migration Summary                   ║', 'green');
        CLI::write('╠════════════════════════════════════════╣', 'green');
        foreach ($stats as $type => $count) {
            CLI::write("║  {$type}: {$count} migrated", 'green');
        }
        CLI::write('╚════════════════════════════════════════╝', 'green');

        CLI::newLine();
        CLI::write('⚠️  Next steps:', 'yellow');
        CLI::write('1. Update your .env file with any custom settings from CI3');
        CLI::write('2. Review OAuth settings (format has changed)');
        CLI::write('3. Test login with migrated accounts');
        CLI::write('4. Update your web server configuration');

        return 0;
    }

    private function getSourceDatabaseConfig(array $params): ?array
    {
        CLI::write('Source Database (CI3) Configuration:', 'green');

        $host = CLI::prompt('Source Host', $params['source-host'] ?? 'localhost');
        $database = CLI::prompt('Source Database Name', $params['source-db'] ?? null);
        $username = CLI::prompt('Source Username', $params['source-user'] ?? 'root');
        $password = CLI::prompt('Source Password', $params['source-pass'] ?? null, 'hidden');
        $prefix = CLI::prompt('Source Table Prefix', $params['source-prefix'] ?? 'ci_');

        if (empty($database)) {
            CLI::error('Source database name is required');
            return null;
        }

        return compact('host', 'database', 'username', 'password', 'prefix');
    }

    private function migrateUserGroups(): int
    {
        CLI::write('Migrating user groups...', 'cyan');

        $stmt = $this->sourceDb->prepare(
            "SELECT * FROM {$this->sourcePrefix}users_groups WHERE id > 5"
        );
        $stmt->execute();
        $groups = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $count = 0;
        foreach ($groups as $group) {
            $data = [
                'id'          => $group['id'],
                'name'        => $group['name'],
                'status'      => $group['status'],
                'permissions' => $this->upgradePermissions($group['permissions']),
                'created_at'  => $group['created_at'] ?? date('Y-m-d H:i:s'),
                'updated_at'  => $group['last_updated'] ?? date('Y-m-d H:i:s'),
            ];

            if (!$this->dryRun) {
                // Skip if already exists
                $existing = $this->targetDb->table('users_groups')
                    ->where('id', $data['id'])
                    ->get()
                    ->getRow();

                if ($existing) {
                    $this->targetDb->table('users_groups')
                        ->where('id', $data['id'])
                        ->update($data);
                } else {
                    $this->targetDb->table('users_groups')->insert($data);
                }
            }
            $count++;
        }

        CLI::write("  ✓ Migrated {$count} user groups", 'green');
        return $count;
    }

    private function migrateUsers(): int
    {
        CLI::write('Migrating users...', 'cyan');

        $stmt = $this->sourceDb->prepare(
            "SELECT * FROM {$this->sourcePrefix}users"
        );
        $stmt->execute();
        $users = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $count = 0;
        foreach ($users as $user) {
            // Map old status to new status
            $status = 'active';
            if ($user['banned'] == 1) {
                $status = 'banned';
            } elseif ($user['activated'] == 0) {
                $status = 'pending';
            }

            // Convert phpass hash to new format (will be upgraded on next login)
            $passwordHash = $user['password'];
            if (strlen($passwordHash) < 60) {
                // Mark as legacy hash - will be rehashed on login
                $passwordHash = 'legacy:' . $passwordHash;
            }

            $data = [
                'id'              => $user['id'],
                'username'        => $user['username'],
                'email'           => $user['email'],
                'password_hash'   => $passwordHash,
                'group_id'        => $user['gid'],
                'status'          => $status,
                'ban_reason'      => $user['ban_reason'],
                'last_login_at'   => $user['last_login'] ?? null,
                'last_login_ip'   => $user['last_ip'] ?? null,
                'created_at'      => $user['created'] ?? date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s', strtotime($user['modified'])) ?? date('Y-m-d H:i:s'),
            ];

            if (!$this->dryRun) {
                $existing = $this->targetDb->table('users')
                    ->where('id', $data['id'])
                    ->get()
                    ->getRow();

                if ($existing) {
                    $this->targetDb->table('users')
                        ->where('id', $data['id'])
                        ->update($data);
                } else {
                    $this->targetDb->table('users')->insert($data);
                }
            }
            $count++;
        }

        CLI::write("  ✓ Migrated {$count} users", 'green');
        return $count;
    }

    private function migrateProfiles(): int
    {
        CLI::write('Migrating user profiles...', 'cyan');

        $stmt = $this->sourceDb->prepare(
            "SELECT * FROM {$this->sourcePrefix}user_profiles"
        );
        $stmt->execute();
        $profiles = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $count = 0;
        foreach ($profiles as $profile) {
            $data = [
                'id'          => $profile['id'],
                'user_id'     => $profile['user_id'],
                'first_name'  => $profile['first_name'] ?? null,
                'last_name'   => $profile['last_name'] ?? null,
                'phone'       => $profile['phone'] ?? null,
                'company'     => $profile['company'] ?? null,
                'website'     => $profile['website'] ?? null,
                'country'     => $profile['country'] ?? null,
                'address'     => $profile['address'] ?? null,
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ];

            if (!$this->dryRun) {
                $existing = $this->targetDb->table('user_profiles')
                    ->where('id', $data['id'])
                    ->get()
                    ->getRow();

                if ($existing) {
                    $this->targetDb->table('user_profiles')
                        ->where('id', $data['id'])
                        ->update($data);
                } else {
                    $this->targetDb->table('user_profiles')->insert($data);
                }
            }
            $count++;
        }

        CLI::write("  ✓ Migrated {$count} profiles", 'green');
        return $count;
    }

    private function migrateOptions(): int
    {
        CLI::write('Migrating options...', 'cyan');

        $stmt = $this->sourceDb->prepare(
            "SELECT * FROM {$this->sourcePrefix}options"
        );
        $stmt->execute();
        $options = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $skipOptions = ['license_key', 'admin_template', 'site_template'];
        $mapOptions = [
            'website_name'     => 'site_name',
            'captcha_registration' => 'captcha_enabled',
        ];

        $count = 0;
        foreach ($options as $option) {
            $name = $option['option_name'];

            // Skip certain options
            if (in_array($name, $skipOptions)) {
                continue;
            }

            // Map old option names to new
            if (isset($mapOptions[$name])) {
                $name = $mapOptions[$name];
            }

            $data = [
                'option_name'   => $name,
                'option_value'    => $option['option_value'],
                'autoload'        => $option['autoload'] === 'yes' ? 1 : 0,
                'created_at'      => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ];

            if (!$this->dryRun) {
                $existing = $this->targetDb->table('options')
                    ->where('option_name', $name)
                    ->get()
                    ->getRow();

                if (!$existing) {
                    $this->targetDb->table('options')->insert($data);
                }
            }
            $count++;
        }

        CLI::write("  ✓ Migrated {$count} options", 'green');
        return $count;
    }

    private function migrateOAuth(): int
    {
        CLI::write('Migrating OAuth connections...', 'cyan');

        $providerMap = [
            'facebook_id'   => 'facebook',
            'twitter_id'    => 'twitter',
            'google_id'     => 'google',
            'linkedin_id'   => 'linkedin',
            'github_id'     => 'github',
            'instagram_id'  => 'instagram',
            'microsoft_id'  => 'microsoft',
            'envato_id'     => 'envato',
            'paypal_id'     => 'paypal',
            'yandex_id'     => 'yandex',
        ];

        $stmt = $this->sourceDb->prepare(
            "SELECT * FROM {$this->sourcePrefix}user_profiles WHERE " .
            implode(' IS NOT NULL OR ', array_keys($providerMap)) . " IS NOT NULL"
        );
        $stmt->execute();
        $profiles = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $count = 0;
        foreach ($profiles as $profile) {
            foreach ($providerMap as $column => $provider) {
                if (!empty($profile[$column])) {
                    $data = [
                        'user_id'       => $profile['user_id'],
                        'provider'      => $provider,
                        'provider_id'   => $profile[$column],
                        'created_at'    => date('Y-m-d H:i:s'),
                        'updated_at'    => date('Y-m-d H:i:s'),
                    ];

                    if (!$this->dryRun) {
                        $existing = $this->targetDb->table('user_oauth_connections')
                            ->where(['user_id' => $data['user_id'], 'provider' => $data['provider']])
                            ->get()
                            ->getRow();

                        if (!$existing) {
                            $this->targetDb->table('user_oauth_connections')->insert($data);
                        }
                    }
                    $count++;
                }
            }
        }

        CLI::write("  ✓ Migrated {$count} OAuth connections", 'green');
        return $count;
    }

    private function upgradePermissions(?string $permissions): ?string
    {
        if (empty($permissions)) {
            return null;
        }

        // Convert from phpass serialized or JSON
        $perms = @json_decode($permissions, true);
        if (!$perms) {
            $perms = @unserialize($permissions);
        }

        if (!is_array($perms)) {
            return null;
        }

        // Map old permissions to new
        $permissionMap = [
            'access_backend'     => 'access_backend',
            'view_users'         => 'view_users',
            'edit_users'         => 'edit_users',
            'delete_users'       => 'delete_users',
            'view_user_groups'   => 'view_user_groups',
            'edit_user_groups'   => 'edit_user_groups',
            'delete_user_groups' => 'delete_user_groups',
            'general_settings'   => 'general_settings',
            'login_to_frontend'  => 'login_to_frontend',
        ];

        $newPerms = [];
        foreach ($perms as $oldKey => $value) {
            if (isset($permissionMap[$oldKey])) {
                $newPerms[$permissionMap[$oldKey]] = $value;
            }
        }

        return json_encode($newPerms);
    }
}
