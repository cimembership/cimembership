<?php

declare(strict_types=1);

namespace CIMembership\Install;

use Composer\Script\Event;

/**
 * CIMembership Installer
 *
 * Handles post-install and post-update tasks when installed via Composer.
 *
 * @package CIMembership\Install
 */
class Installer
{
    /**
     * Post-install hook - runs after composer install
     */
    public static function postInstall(Event $event): void
    {
        $io = $event->getIO();
        $io->write('<info>CIMembership installed successfully!</info>');
        $io->write('');
        $io->write('Next steps:');
        $io->write('  1. Run <comment>php spark ci-membership:install</comment> to set up the base');
        $io->write('  2. Configure your OAuth providers in .env');
        $io->write('  3. Run <comment>php spark migrate</comment> to create database tables');
        $io->write('');
        $io->write('See https://github.com/cimembership/cimembership for documentation');
    }

    /**
     * Post-update hook - runs after composer update
     */
    public static function postUpdate(Event $event): void
    {
        $io = $event->getIO();
        $io->write('<info>CIMembership updated!</info>');
        $io->write('Run <comment>php spark migrate</comment> to apply any database updates.');
    }
}
