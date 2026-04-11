<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\Config as DBConfig;

class Install extends BaseCommand
{
    protected $group       = 'CIMembership';
    protected $name        = 'install:app';
    protected $description = 'Installs CIMembership application';
    protected $usage       = 'install:app [options]';
    protected $arguments   = [];
    protected $options     = [
        '--env'  => 'Environment file to use (default: .env)',
        '--force' => 'Force installation even if already installed',
    ];

    public function run(array $params): int
    {
        CLI::write('╔════════════════════════════════════════╗', 'cyan');
        CLI::write('║     CIMembership v4 Installation       ║', 'cyan');
        CLI::write('╚════════════════════════════════════════╝', 'cyan');
        CLI::newLine();

        // Check if already installed
        if (!$this->isForce($params) && $this->isInstalled()) {
            CLI::error('CIMembership is already installed!');
            CLI::write('Use --force to reinstall (this will delete existing data).');
            return 1;
        }

        // Step 1: Database Configuration
        CLI::write('Step 1: Database Configuration', 'green');
        $dbConfig = $this->configureDatabase();
        if (!$dbConfig) {
            return 1;
        }

        // Step 2: Site Configuration (get values before creating .env)
        CLI::newLine();
        CLI::write('Step 2: Site Configuration', 'green');
        $siteConfig = $this->promptSiteConfig();
        if (!$siteConfig) {
            return 1;
        }

        // Step 3: Create .env file (needed before migrations)
        CLI::newLine();
        CLI::write('Step 3: Creating Environment File', 'green');
        $this->createEnvFile($dbConfig, $siteConfig);

        // Step 4: Run Migrations (after .env is created)
        CLI::newLine();
        CLI::write('Step 4: Database Setup', 'green');
        if (!$this->runMigrations()) {
            return 1;
        }

        // Step 5: Admin Account
        CLI::newLine();
        CLI::write('Step 5: Administrator Account', 'green');
        $adminData = $this->createAdminAccount();
        if (!$adminData) {
            return 1;
        }

        // Step 6: Update Site Config in Database
        CLI::newLine();
        CLI::write('Step 6: Finalizing Site Configuration', 'green');
        if (!$this->updateSiteConfig($siteConfig)) {
            return 1;
        }

        // Installation complete
        CLI::newLine();
        CLI::write('╔════════════════════════════════════════╗', 'green');
        CLI::write('║     Installation Complete!             ║', 'green');
        CLI::write('╚════════════════════════════════════════╝', 'green');
        CLI::newLine();
        CLI::write('Access your site at: ' . ($siteConfig['base_url'] ?? 'http://localhost:8080'), 'yellow');
        CLI::write('Admin panel: ' . rtrim($siteConfig['base_url'] ?? 'http://localhost:8080', '/') . '/admin', 'yellow');
        CLI::write('Username: ' . $adminData['username'], 'yellow');
        CLI::write('Password: ' . ($adminData['password'] ?? 'admin123'), 'yellow');
        CLI::newLine();
        CLI::write('⚠ IMPORTANT: Please change the default password immediately!', 'red');
        CLI::newLine();
        CLI::write('For security, remove write permissions from the following directories:', 'cyan');
        CLI::write('  - app/Config/');
        CLI::write('  - public/uploads/ (keep write permission)');
        CLI::newLine();

        return 0;
    }

    private function isInstalled(): bool
    {
        // Check if .env exists and database has tables
        if (!file_exists(ROOTPATH . '.env')) {
            return false;
        }

        try {
            $db = \Config\Database::connect();
            // Check for users table with or without prefix
            return $db->tableExists('users') || $db->tableExists('ci_users');
        } catch (\Exception $e) {
            return false;
        }
    }

    private function isForce(array $params): bool
    {
        return array_key_exists('force', $params) || CLI::getOption('force');
    }

    private function configureDatabase(): ?array
    {
        // Support non-interactive mode via environment variables
        $host = (getenv('CI_DB_HOST') !== false) ? getenv('CI_DB_HOST') : CLI::prompt('Database Host', 'localhost');
        $port = (getenv('CI_DB_PORT') !== false) ? getenv('CI_DB_PORT') : CLI::prompt('Database Port', '3306');
        $database = (getenv('CI_DB_NAME') !== false) ? getenv('CI_DB_NAME') : CLI::prompt('Database Name', 'cimembership');
        $username = (getenv('CI_DB_USER') !== false) ? getenv('CI_DB_USER') : CLI::prompt('Database Username', 'root');
        $password = (getenv('CI_DB_PASS') !== false) ? getenv('CI_DB_PASS') : CLI::prompt('Database Password');
        $prefix = (getenv('CI_DB_PREFIX') !== false) ? getenv('CI_DB_PREFIX') : CLI::prompt('Table Prefix', 'ci_');

        CLI::write('Testing database connection...');

        try {
            $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
            $pdo = new \PDO($dsn, $username, $password, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);

            // Create database if not exists
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$database}`");

            CLI::write('Database connection successful!', 'green');

            return [
                'hostname' => $host,
                'port'     => $port,
                'database' => $database,
                'username' => $username,
                'password' => $password,
                'prefix'   => $prefix,
            ];
        } catch (\PDOException $e) {
            CLI::error('Database connection failed: ' . $e->getMessage());
            return null;
        }
    }

    private function runMigrations(): bool
    {
        try {
            // Load .env values directly into CodeIgniter's config
            $dbConfig = config('Database');

            // Parse .env file directly for database values
            $envPath = ROOTPATH . '.env';
            $dbValues = [];
            if (file_exists($envPath)) {
                $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                        list($key, $value) = explode('=', $line, 2);
                        $key = trim($key);
                        $value = trim($value);
                        // Remove surrounding quotes if present
                        if ((strpos($value, "'") === 0 || strpos($value, '"') === 0) && substr($value, -1) === $value[0]) {
                            $value = substr($value, 1, -1);
                        }
                        $dbValues[$key] = $value;
                    }
                }
            }

            // Apply values to database config
            $dbConfig->default['hostname'] = $dbValues['database.default.hostname'] ?? 'localhost';
            $dbConfig->default['database'] = $dbValues['database.default.database'] ?? '';
            $dbConfig->default['username'] = $dbValues['database.default.username'] ?? '';
            $dbConfig->default['password'] = $dbValues['database.default.password'] ?? '';
            $dbConfig->default['DBPrefix'] = $dbValues['database.default.DBPrefix'] ?? '';
            $dbConfig->default['port'] = (int) ($dbValues['database.default.port'] ?? 3306);

            // Get database connection
            $db = \Config\Database::connect();

            // Run migrations from CIMembership namespace
            $migrations = service('migrations');

            // Regress only if force flag is set and tables exist
            try {
                if ($db->tableExists('migrations')) {
                    $hasMigrations = $db->table('migrations')->countAllResults() > 0;
                    if ($hasMigrations) {
                        CLI::write('Found existing migrations, upgrading...', 'yellow');
                    }
                }
            } catch (\Exception $e) {
                // Table might not exist, that's okay
            }

            // Run all pending migrations
            $migrations->latest();

            CLI::write('All migrations completed successfully!', 'green');
            return true;
        } catch (\Exception $e) {
            CLI::error('Migration failed: ' . $e->getMessage());
            return false;
        }
    }

    private function createAdminAccount(): ?array
    {
        // Support non-interactive mode via environment variables
        $username = (getenv('CI_ADMIN_USER') !== false) ? getenv('CI_ADMIN_USER') : CLI::prompt('Admin Username', 'admin');
        $email = (getenv('CI_ADMIN_EMAIL') !== false) ? getenv('CI_ADMIN_EMAIL') : CLI::prompt('Admin Email', 'admin@example.com');

        // In non-interactive mode, use env var or default password
        $envPassword = getenv('CI_ADMIN_PASS');
        if ($envPassword !== false) {
            $password = $envPassword;
        } else {
            do {
                $password = CLI::prompt('Admin Password');
                $passwordConfirm = CLI::prompt('Confirm Password');

                if ($password !== $passwordConfirm) {
                    CLI::error('Passwords do not match!');
                } elseif (strlen($password) < 8) {
                    CLI::error('Password must be at least 8 characters!');
                    $password = null;
                }
            } while ($password !== $passwordConfirm || strlen($password) < 8);
        }

        try {
            $db = \Config\Database::connect();

            // Create admin user directly with database - CodeIgniter adds DBPrefix automatically
            $data = [
                'username'        => $username,
                'email'           => $email,
                'password_hash'   => password_hash($password, PASSWORD_DEFAULT),
                'group_id'        => 1, // Super Administrator
                'status'          => 'active',
                'last_login_at'   => date('Y-m-d H:i:s'),
                'created_at'      => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
            ];

            $db->table('users')->insert($data);
            $userId = $db->insertID();

            // Create profile
            $db->table('user_profiles')->insert([
                'user_id'     => $userId,
                'first_name'  => 'Super',
                'last_name'   => 'Administrator',
                'timezone'    => 'UTC',
                'locale'      => 'en',
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ]);

            CLI::write('Admin account created successfully!', 'green');

            return [
                'username' => $username,
                'email'    => $email,
                'password' => $password, // Return for display only
            ];
        } catch (\Exception $e) {
            CLI::error('Failed to create admin account: ' . $e->getMessage());
            return null;
        }
    }

    private function promptSiteConfig(): ?array
    {
        // Support non-interactive mode via environment variables
        $siteName = (getenv('CI_SITE_NAME') !== false) ? getenv('CI_SITE_NAME') : CLI::prompt('Site Name', 'CIMembership');
        $baseUrl = (getenv('CI_BASE_URL') !== false) ? getenv('CI_BASE_URL') : CLI::prompt('Base URL', 'http://localhost:8080/');

        // Ensure trailing slash
        $baseUrl = rtrim($baseUrl, '/') . '/';

        return [
            'site_name' => $siteName,
            'base_url'  => $baseUrl,
        ];
    }

    private function updateSiteConfig(array $siteConfig): bool
    {
        try {
            $db = \Config\Database::connect();

            // Check if options table exists and has data
            $optionsExist = $db->tableExists('options');

            if ($optionsExist) {
                // Update options in database - CodeIgniter adds DBPrefix automatically
                $db->table('options')->where('option_name', 'site_name')->update([
                    'option_value' => $siteConfig['site_name'],
                    'updated_at'   => date('Y-m-d H:i:s'),
                ]);

                $db->table('options')->where('option_name', 'webmaster_email')->update([
                    'option_value' => 'admin@' . parse_url($siteConfig['base_url'], PHP_URL_HOST),
                    'updated_at'   => date('Y-m-d H:i:s'),
                ]);
            }

            return true;
        } catch (\Exception $e) {
            CLI::error('Failed to update site config: ' . $e->getMessage());
            return false;
        }
    }

    private function createEnvFile(array $dbConfig, array $siteConfig): void
    {
        $envContent = "#--------------------------------------------------------------------\n";
        $envContent .= "# Environment Configuration\n";
        $envContent .= "#--------------------------------------------------------------------\n\n";

        $envContent .= "CI_ENVIRONMENT = production\n\n";

        $envContent .= "#--------------------------------------------------------------------\n";
        $envContent .= "# Application\n";
        $envContent .= "#--------------------------------------------------------------------\n\n";
        $envContent .= "app.baseURL = '{$siteConfig['base_url']}'\n";
        $envContent .= "app.timezone = 'UTC'\n";
        $envContent .= "app.CSPEnabled = false\n\n";

        $envContent .= "#--------------------------------------------------------------------\n";
        $envContent .= "# Database\n";
        $envContent .= "#--------------------------------------------------------------------\n\n";
        $envContent .= "database.default.hostname = {$dbConfig['hostname']}\n";
        $envContent .= "database.default.database = {$dbConfig['database']}\n";
        $envContent .= "database.default.username = {$dbConfig['username']}\n";
        $envContent .= "database.default.password = {$dbConfig['password']}\n";
        $envContent .= "database.default.DBDriver = MySQLi\n";
        $envContent .= "database.default.DBPrefix = {$dbConfig['prefix']}\n";
        $envContent .= "database.default.port = {$dbConfig['port']}\n\n";

        $envContent .= "#--------------------------------------------------------------------\n";
        $envContent .= "# Security\n";
        $envContent .= "#--------------------------------------------------------------------\n\n";
        $envContent .= "security.tokenName = csrf_token\n";
        $envContent .= "security.samesite = Lax\n\n";

        $envContent .= "#--------------------------------------------------------------------\n";
        $envContent .= "# CIMembership Settings\n";
        $envContent .= "#--------------------------------------------------------------------\n\n";
        $envContent .= "cimembership.siteName = {$siteConfig['site_name']}\n";
        $envContent .= "# cimembership.recaptcha.siteKey =\n";
        $envContent .= "# cimembership.recaptcha.secretKey =\n";

        $envPath = ROOTPATH . '.env';

        if (file_exists($envPath)) {
            rename($envPath, $envPath . '.backup.' . date('YmdHis'));
        }

        if (file_put_contents($envPath, $envContent)) {
            CLI::write("Environment file created at: {$envPath}", 'green');
        } else {
            CLI::error("Failed to create environment file.");
            CLI::write("Please create .env file manually with the following content:", 'yellow');
            CLI::write($envContent);
        }
    }
}
