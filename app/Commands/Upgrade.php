<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\ConnectionInterface;

/**
 * CIMembership Upgrade Command
 *
 * Upgrades the database schema while preserving existing data.
 * Safe to run on existing v4 installations.
 *
 * For v3 to v4 migration, use: php spark upgrade:fromv3
 */
class Upgrade extends BaseCommand
{
    protected $group       = 'CIMembership';
    protected $name        = 'upgrade:app';
    protected $description = 'Upgrade CIMembership v4 database schema';
    protected $usage       = 'upgrade:app [options]';
    protected $arguments   = [];
    protected $options     = [
        '--dry-run' => 'Show what would be done without making changes',
        '--force'   => 'Force upgrade even if not installed',
    ];

    private ConnectionInterface $db;
    private array $log = [];

    public function run(array $params): int
    {
        CLI::write('╔════════════════════════════════════════╗', 'cyan');
        CLI::write('║     CIMembership v4 Upgrade            ║', 'cyan');
        CLI::write('╚════════════════════════════════════════╝', 'cyan');
        CLI::newLine();

        $this->db = \Config\Database::connect();
        $isDryRun = array_key_exists('dry-run', $params) || CLI::getOption('dry-run');
        $isForce = array_key_exists('force', $params) || CLI::getOption('force');

        // Check if installed
        if (!$isForce && !$this->isInstalled()) {
            CLI::error('CIMembership is not installed!');
            CLI::write('Please run: php spark install:app');
            return 1;
        }

        // Show current version info
        $this->showCurrentVersion();

        // Check for pending migrations
        $pendingMigrations = $this->getPendingMigrations();

        if (empty($pendingMigrations)) {
            CLI::write('✓ Database is already up to date!', 'green');
            return 0;
        }

        CLI::write('Pending migrations:', 'yellow');
        foreach ($pendingMigrations as $migration) {
            CLI::write('  - ' . $migration['name'], 'cyan');
        }
        CLI::newLine();

        if ($isDryRun) {
            CLI::write('🔍 DRY RUN MODE - No changes will be made.', 'cyan');
            return 0;
        }

        // Confirm upgrade
        if (!CLI::prompt('Do you want to proceed with the upgrade?', ['y', 'n']) === 'y') {
            CLI::write('Upgrade cancelled.', 'yellow');
            return 0;
        }

        // Backup reminder
        CLI::newLine();
        CLI::write('⚠ IMPORTANT: Ensure you have a database backup before proceeding!', 'red');
        CLI::newLine();

        // Run upgrade
        $this->runUpgrade();

        // Show results
        CLI::newLine();
        CLI::write('╔════════════════════════════════════════╗', 'green');
        CLI::write('║     Upgrade Complete!                  ║', 'green');
        CLI::write('╚════════════════════════════════════════╝', 'green');
        CLI::newLine();

        if (!empty($this->log)) {
            CLI::write('Upgrade log:', 'cyan');
            foreach ($this->log as $entry) {
                CLI::write('  ' . $entry);
            }
        }

        return 0;
    }

    private function isInstalled(): bool
    {
        try {
            return $this->db->tableExists('users') || $this->db->tableExists('ci_users');
        } catch (\Exception $e) {
            return false;
        }
    }

    private function showCurrentVersion(): void
    {
        try {
            // Get current migration version
            $lastMigration = $this->db->table('migrations')
                ->orderBy('id', 'DESC')
                ->limit(1)
                ->get()
                ->getRow();

            if ($lastMigration) {
                CLI::write('Current database version: ' . $lastMigration->version, 'cyan');
            }

            // Get user count
            $userCount = $this->db->table('users')->countAll();
            CLI::write('Users in database: ' . $userCount, 'cyan');
            CLI::newLine();
        } catch (\Exception $e) {
            CLI::write('Unable to determine current version.', 'yellow');
        }
    }

    private function getPendingMigrations(): array
    {
        $migrations = [];
        $migrationPaths = [
            ROOTPATH . 'src/Database/Migrations/',
        ];

        foreach ($migrationPaths as $path) {
            if (!is_dir($path)) {
                continue;
            }

            $files = glob($path . '*.php');
            foreach ($files as $file) {
                $basename = basename($file, '.php');
                $version = $this->extractVersion($basename);

                if (!$this->isMigrated($version)) {
                    $migrations[] = [
                        'file'    => $file,
                        'name'    => $basename,
                        'version' => $version,
                    ];
                }
            }
        }

        // Sort by version
        usort($migrations, fn($a, $b) => strcmp($a['version'], $b['version']));

        return $migrations;
    }

    private function extractVersion(string $filename): string
    {
        // Extract version from filename like "2024-01-01-000001_CreateUsersGroups"
        if (preg_match('/^(\d{4}-\d{2}-\d{2}-\d{6})/', $filename, $matches)) {
            return $matches[1];
        }
        return $filename;
    }

    private function isMigrated(string $version): bool
    {
        try {
            return $this->db->table('migrations')
                ->where('version', $version)
                ->countAllResults() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function runUpgrade(): void
    {
        try {
            $migrations = service('migrations');
            $migrations->latest();

            $this->log[] = 'Migrations completed successfully';
            CLI::write('✓ Database migrations completed', 'green');
        } catch (\Exception $e) {
            CLI::error('Migration failed: ' . $e->getMessage());
            throw $e;
        }
    }
}
