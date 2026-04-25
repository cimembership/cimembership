<?php

declare(strict_types=1);

namespace Config;

/**
 * Paths
 *
 * Holds the paths that are used by the system to
 * locate the main directories, app, system, etc.
 *
 * NOTE: This class is required prior to Autoloader instantiation,
 *       and does not extend BaseConfig.
 */
class Paths
{
    /**
     * Path to the system directory
     */
    public string $systemDirectory = __DIR__ . '/../../vendor/codeigniter4/framework/system';

    /**
     * Path to the application directory
     */
    public string $appDirectory = __DIR__ . '/..';

    /**
     * Path to the writable directory
     */
    public string $writableDirectory = __DIR__ . '/../../writable';

    /**
     * Path to the tests directory
     */
    public string $testsDirectory = __DIR__ . '/../../tests';

    /**
     * Path to the view directory
     */
    public string $viewDirectory = __DIR__ . '/../Views';

    /**
     * Path to the env directory
     */
    public string $envDirectory = __DIR__ . '/../../';

    /**
     * Minimum PHP version
     */
    public string $minPhpVersion = '8.1';
}
