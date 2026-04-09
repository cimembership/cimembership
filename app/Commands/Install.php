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

        // Step 2: Run Migrations
        CLI::newLine();
        CLI::write('Step 2: Database Setup', 'green');
        if (!$this->runMigrations()) {
            return 1;
        }

        // Step 3: Admin Account
        CLI::newLine();
        CLI::write('Step 3: Administrator Account', 'green');
        $adminData = $this->createAdminAccount();
        if (!$adminData) {
            return 1;
        }

        // Step 4: Site Configuration
        CLI::newLine();
        CLI::write('Step 4: Site Configuration', 'green');
        $siteConfig = $this->configureSite();
        if (!$siteConfig) {
            return 1;
        }

        // Step 5: Create .env file
        CLI::newLine();
        CLI::write('Step 5: Creating Environment File', 'green');
        $this->createEnvFile($dbConfig, $siteConfig);

        // Installation complete
        CLI::newLine();
        CLI::write('╔════════════════════════════════════════╗', 'green');
        CLI::write('║     Installation Complete!             ║', 'green');
        CLI::write('╚════════════════════════════════════════╝', 'green');
        CLI::newLine();
        CLI::write('Access your site at: ' . ($siteConfig['base_url'] ?? 'http://localhost:8080'), 'yellow');
        CLI::write('Admin panel: ' . rtrim($siteConfig['base_url'] ?? 'http://localhost:8080', '/') . '/admin', 'yellow');
        CLI::write('Username: ' . $adminData['username'], 'yellow');
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
            return $db->tableExists('users');
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
        $host = CLI::prompt('Database Host', 'localhost');
        $port = CLI::prompt('Database Port', '3306');
        $database = CLI::prompt('Database Name', 'cimembership');
        $username = CLI::prompt('Database Username', 'root');
        $password = CLI::prompt('Database Password', null, 'hidden');
        $prefix = CLI::prompt('Table Prefix', 'ci_');

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
            $migrations = service('migrations');
            $migrations->regress(0); // Reset if reinstalling
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
        $username = CLI::prompt('Admin Username', 'admin');
        $email = CLI::prompt('Admin Email', 'admin@example.com');

        do {
            $password = CLI::prompt('Admin Password', null, 'hidden');
            $passwordConfirm = CLI::prompt('Confirm Password', null, 'hidden');

            if ($password !== $passwordConfirm) {
                CLI::error('Passwords do not match!');
            } elseif (strlen($password) < 8) {
                CLI::error('Password must be at least 8 characters!');
                $password = null;
            }
        } while ($password !== $passwordConfirm || strlen($password) < 8);

        try {
            $userModel = model('UserModel');

            $userData = [
                'username'        => $username,
                'email'           => $email,
                'password'        => $password,
                'group_id'        => 1, // Super Administrator
                'status'          => 'active',
                'first_name'      => 'Super',
                'last_name'       => 'Administrator',
            ];

            $userId = $userModel->insert($userData);

            if (!$userId) {
                CLI::error('Failed to create admin account: ' . implode(', ', $userModel->errors()));
                return null;
            }

            CLI::write('Admin account created successfully!', 'green');

            return [
                'username' => $username,
                'email'    => $email,
            ];
        } catch (\Exception $e) {
            CLI::error('Failed to create admin account: ' . $e->getMessage());
            return null;
        }
    }

    private function configureSite(): ?array
    {
        $siteName = CLI::prompt('Site Name', 'CIMembership');
        $baseUrl = CLI::prompt('Base URL', 'http://localhost:8080/');

        // Ensure trailing slash
        $baseUrl = rtrim($baseUrl, '/') . '/';

        // Update options in database
        $optionModel = model('OptionModel');
        $optionModel->updateOption('site_name', $siteName);
        $optionModel->updateOption('webmaster_email', 'admin@' . parse_url($baseUrl, PHP_URL_HOST));

        return [
            'site_name' => $siteName,
            'base_url'  => $baseUrl,
        ];
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
