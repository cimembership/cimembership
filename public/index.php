<?php

/**
 * CIMembership v4 - CodeIgniter 4 Entry Point
 */

// Check PHP version
if (version_compare(PHP_VERSION, '8.1', '<')) {
    header('HTTP/1.1 503 Service Unavailable.', true, 503);
    echo 'Your PHP version must be 8.1 or higher. Current version: ' . PHP_VERSION;
    exit(1);
}

// Define paths
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
define('ROOTPATH', realpath(FCPATH . '..') . DIRECTORY_SEPARATOR);
define('WRITEPATH', realpath(ROOTPATH . 'writable') . DIRECTORY_SEPARATOR);
define('SYSTEMPATH', realpath(ROOTPATH . 'vendor/codeigniter4/framework/system') . DIRECTORY_SEPARATOR);

// Load environment variables
require_once ROOTPATH . 'vendor/autoload.php';

// Load environment settings
if (is_file(ROOTPATH . '.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(ROOTPATH);
    $dotenv->load();
}

// Define environment
define('ENVIRONMENT', $_ENV['CI_ENVIRONMENT'] ?? 'production');

// Bootstrap CodeIgniter
require_once SYSTEMPATH . 'bootstrap.php';

// Instantiate CodeIgniter
$paths = require ROOTPATH . 'app/Config/Paths.php';
$app = Config\Services::codeigniter();
$app->initialize();

// Run application
$app->run();
