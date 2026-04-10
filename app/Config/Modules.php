<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Modules\Modules as BaseModules;

class Modules extends BaseModules
{
    /**
     * -------------------------------------------------------------------
     * Enable Auto-Discovery?
     * -------------------------------------------------------------------
     * If true, then auto-discovery will happen in all environments.
     * If false, auto-discovery will only happen in development.
     */
    public $enabled = true;

    /**
     * -------------------------------------------------------------------
     * Enable Auto-Discovery Within Composer Packages?
     * -------------------------------------------------------------------
     */
    public $enabledComposerPackages = true;

    /**
     * -------------------------------------------------------------------
     * Auto-Discovery Rules
     * -------------------------------------------------------------------
     * Aliases for directories to be scanned during auto-discovery.
     */
    public $aliases = [
        'Events'        => APPPATH . 'Events',
        'Filters'       => APPPATH . 'Filters',
        'Registrars'    => APPPATH . 'Config/Registrars',
        'Routes'        => APPPATH . 'Config/Routes',
        'Services'      => APPPATH . 'Config/Services',
        'Language'      => APPPATH . 'Language',
        'Migrations'    => APPPATH . 'Database/Migrations',
        'Seeds'         => APPPATH . 'Database/Seeds',
    ];

    /**
     * -------------------------------------------------------------------
     * Auto-Discovery Within Namespace
     * -------------------------------------------------------------------
     * Defines whether auto-discovery should search within the namespace.
     */
    public $composerPackages = [
        'only' => [],
    ];
}
