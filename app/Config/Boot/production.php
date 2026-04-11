<?php

declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_USER_NOTICE & ~E_USER_DEPRECATED);

define('CI_DEBUG', false);
define('CI_ENVIRONMENT', 'production');

// Exit status constants
if (!defined('EXIT_SUCCESS')) {
    define('EXIT_SUCCESS', 0);
}
if (!defined('EXIT_ERROR')) {
    define('EXIT_ERROR', 1);
}
if (!defined('EXIT_CONFIG')) {
    define('EXIT_CONFIG', 3);
}
if (!defined('EXIT_UNKNOWN_FILE')) {
    define('EXIT_UNKNOWN_FILE', 4);
}
if (!defined('EXIT_UNKNOWN_CLASS')) {
    define('EXIT_UNKNOWN_CLASS', 5);
}
if (!defined('EXIT_UNKNOWN_METHOD')) {
    define('EXIT_UNKNOWN_METHOD', 6);
}
if (!defined('EXIT_USER_INPUT')) {
    define('EXIT_USER_INPUT', 7);
}
if (!defined('EXIT_DATABASE')) {
    define('EXIT_DATABASE', 8);
}
if (!defined('EXIT__AUTO_MIN')) {
    define('EXIT__AUTO_MIN', 9);
}
if (!defined('EXIT__AUTO_MAX')) {
    define('EXIT__AUTO_MAX', 125);
}
