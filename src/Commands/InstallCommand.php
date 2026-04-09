<?php

declare(strict_types=1);

namespace CIMembership\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * CIMembership Install Command
 *
 * Publishes CIMembership files to the consumer's application.
 *
 * @package CIMembership\Commands
 */
class InstallCommand extends BaseCommand
{
    protected $group       = 'CIMembership';
    protected $name        = 'ci-membership:install';
    protected $description = 'Install CIMembership base platform';

    public function run(array $params)
    {
        CLI::write('Installing CIMembership Base Platform...', 'green');
        CLI::newLine();

        $this->publishMigrations();
        $this->publishSeeds();
        $this->publishConfig();
        $this->createDirectories();

        CLI::newLine();
        CLI::write('Installation complete!', 'green');
        CLI::newLine();
        CLI::write('Next steps:', 'yellow');
        CLI::write('  1. Run migrations: php spark migrate --namespace CIMembership');
        CLI::write('  2. Run seeders: php spark db:seed CIMembershipSeeder');
        CLI::write('  3. Add routes to app/Config/Routes.php (see documentation)');
        CLI::newLine();

        return EXIT_SUCCESS;
    }

    /**
     * Publish migrations to the app's Database/Migrations folder
     */
    protected function publishMigrations(): void
    {
        CLI::write('Publishing migrations...', 'yellow');

        $sourceDir = ROOTPATH . 'vendor/cimembership/cimembership/src/Database/Migrations/';
        $targetDir = APPPATH . 'Database/Migrations/';

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        // We'll create stub files that extend CIMembership migrations
        $migrations = [
            '2024-01-01-000001_CreateUsersGroups',
            '2024-01-01-000002_CreateUsers',
            '2024-01-01-000003_CreateUserProfiles',
            '2024-01-01-000004_CreateUserOauthConnections',
            '2024-01-01-000005_CreateLoginAttempts',
            '2024-01-01-000006_CreateOptions',
            '2024-01-01-000007_CreateApiKeys',
            '2024-01-01-000008_CreateCiSessions',
        ];

        foreach ($migrations as $migration) {
            $targetFile = $targetDir . $migration . '.php';
            if (!file_exists($targetFile)) {
                $stub = $this->getMigrationStub($migration);
                file_put_contents($targetFile, $stub);
                CLI::write("  Created: {$migration}.php", 'green');
            } else {
                CLI::write("  Skipped: {$migration}.php (already exists)", 'yellow');
            }
        }
    }

    /**
     * Publish seeders
     */
    protected function publishSeeds(): void
    {
        CLI::write('Publishing seeders...', 'yellow');

        $targetDir = APPPATH . 'Database/Seeds/';

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        // Create a seeder that extends CIMembership seeder
        $stub = <<<STUB
<?php

namespace App\Database\Seeds;

use CIMembership\Database\Seeds\CIMembershipSeeder as BaseSeeder;

/**
 * CIMembership Database Seeder
 */
class CIMembershipSeeder extends BaseSeeder
{
    // Override methods here if needed
}
STUB;

        $targetFile = $targetDir . 'CIMembershipSeeder.php';
        if (!file_exists($targetFile)) {
            file_put_contents($targetFile, $stub);
            CLI::write('  Created: CIMembershipSeeder.php', 'green');
        } else {
            CLI::write('  Skipped: CIMembershipSeeder.php (already exists)', 'yellow');
        }
    }

    /**
     * Publish config file
     */
    protected function publishConfig(): void
    {
        CLI::write('Publishing configuration...', 'yellow');

        $targetDir = APPPATH . 'Config/';
        $configFile = $targetDir . 'CIMembership.php';

        $config = <<<CONFIG
<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * CIMembership Configuration
 *
 * @package Config
 */
class CIMembership extends BaseConfig
{
    /**
     * Site settings
     */
    public string \$siteName = 'CIMembership';
    public string \$siteDescription = '';

    /**
     * Registration settings
     */
    public bool \$allowRegistration = true;
    public bool \$requireActivation = true;

    /**
     * OAuth settings
     */
    public array \$oauth = [
        'google'    => [
            'clientId'     => '',
            'clientSecret' => '',
            'enabled'      => false,
        ],
        'facebook'  => [
            'clientId'     => '',
            'clientSecret' => '',
            'enabled'      => false,
        ],
        'github'    => [
            'clientId'     => '',
            'clientSecret' => '',
            'enabled'      => false,
        ],
        'linkedin'  => [
            'clientId'     => '',
            'clientSecret' => '',
            'enabled'      => false,
        ],
    ];

    /**
     * Security settings
     */
    public int \$maxLoginAttempts = 5;
    public int \$loginAttemptWindow = 15; // minutes
    public int \$activationExpiry = 24; // hours
    public int \$resetTokenExpiry = 1; // hours
}
CONFIG;

        if (!file_exists($configFile)) {
            file_put_contents($configFile, $config);
            CLI::write('  Created: CIMembership.php', 'green');
        } else {
            CLI::write('  Skipped: CIMembership.php (already exists)', 'yellow');
        }
    }

    /**
     * Create required directories
     */
    protected function createDirectories(): void
    {
        CLI::write('Creating directories...', 'yellow');

        $directories = [
            WRITEPATH . 'uploads/avatars/',
            WRITEPATH . 'uploads/exports/',
        ];

        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
                CLI::write("  Created: {$dir}", 'green');
            }
        }
    }

    /**
     * Get migration stub content
     */
    protected function getMigrationStub(string $className): string
    {
        return <<<STUB
<?php

namespace App\Database\Migrations;

use CIMembership\Database\Migrations\{$className} as BaseMigration;

/**
 * {$className}
 *
 * This migration extends the CIMembership base migration.
 * You can override methods here if needed.
 */
class {$className} extends BaseMigration
{
    // Override methods here if needed
}
STUB;
    }
}
