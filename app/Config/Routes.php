<?php

declare(strict_types=1);

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// Default route
$routes->get('/', 'Home::index');

// Auth Routes
$routes->group('auth', ['namespace' => 'App\Modules\Auth\Controllers'], static function ($routes) {
    $routes->get('login', 'Auth::login');
    $routes->post('login', 'Auth::attemptLogin');
    $routes->get('logout', 'Auth::logout');
    $routes->get('register', 'Auth::register');
    $routes->post('register', 'Auth::attemptRegister');
    $routes->get('forgot-password', 'Auth::forgotPassword');
    $routes->post('forgot-password', 'Auth::attemptForgotPassword');
    $routes->get('reset-password/(:segment)', 'Auth::resetPassword/$1');
    $routes->post('reset-password/(:segment)', 'Auth::attemptResetPassword/$1');
    $routes->get('activate/(:segment)', 'Auth::activate/$1');
    $routes->get('resend-activation', 'Auth::resendActivation');
    $routes->post('resend-activation', 'Auth::attemptResendActivation');

    // Profile routes (authenticated)
    $routes->group('profile', ['filter' => 'auth'], static function ($routes) {
        $routes->get('/', 'Profile::index');
        $routes->post('update', 'Profile::update');
        $routes->post('change-password', 'Profile::changePassword');
        $routes->get('delete-account', 'Profile::deleteAccount');
    });

    // OAuth routes
    $routes->group('oauth', static function ($routes) {
        $routes->get('(:alpha)', 'OAuth::redirect/$1');
        $routes->get('(:alpha)/callback', 'OAuth::callback/$1');
        $routes->get('(:alpha)/unlink', 'OAuth::unlink/$1', ['filter' => 'auth']);
    });
});

// Admin Routes
$routes->group('admin', ['namespace' => 'App\Modules\Admin\Controllers', 'filter' => 'auth:admin'], static function ($routes) {
    $routes->get('/', 'Dashboard::index');
    $routes->get('dashboard', 'Dashboard::index');

    // User Management
    $routes->group('users', static function ($routes) {
        $routes->get('/', 'User::index');
        $routes->get('list', 'User::list');
        $routes->get('create', 'User::create');
        $routes->post('create', 'User::store');
        $routes->get('edit/(:num)', 'User::edit/$1');
        $routes->post('edit/(:num)', 'User::update/$1');
        $routes->get('delete/(:num)', 'User::delete/$1');
        $routes->post('bulk-action', 'User::bulkAction');
        $routes->get('export', 'User::export');
    });

    // User Groups
    $routes->group('groups', static function ($routes) {
        $routes->get('/', 'Usergroups::index');
        $routes->get('list', 'Usergroups::list');
        $routes->get('create', 'Usergroups::create');
        $routes->post('create', 'Usergroups::store');
        $routes->get('edit/(:num)', 'Usergroups::edit/$1');
        $routes->post('edit/(:num)', 'Usergroups::update/$1');
        $routes->get('delete/(:num)', 'Usergroups::delete/$1');
        $routes->get('permissions/(:num)', 'Usergroups::permissions/$1');
        $routes->post('permissions/(:num)', 'Usergroups::updatePermissions/$1');
    });

    // Settings
    $routes->group('settings', static function ($routes) {
        $routes->get('/', 'Settings::index');
        $routes->post('general', 'Settings::updateGeneral');
        $routes->post('auth', 'Settings::updateAuth');
        $routes->post('oauth', 'Settings::updateOAuth');
        $routes->post('email', 'Settings::updateEmail');
        $routes->post('captcha', 'Settings::updateCaptcha');
    });
});

// API Routes
$routes->group('api', ['namespace' => 'App\Controllers\API', 'filter' => 'apiAuth'], static function ($routes) {
    $routes->get('users', 'Users::index');
    $routes->get('user/(:num)', 'Users::show/$1');
    $routes->post('users', 'Users::create');
    $routes->put('user/(:num)', 'Users::update/$1');
    $routes->delete('user/(:num)', 'Users::delete/$1');
});

// CLI Routes (for installer)
$routes->cli('install', 'App\Commands\Install');

// Error routes
$routes->set404Override('App\Controllers\Errors::show404');
