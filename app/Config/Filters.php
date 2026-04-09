<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Filters\CSRF;
use CodeIgniter\Filters\DebugToolbar;
use CodeIgniter\Filters\ForceSecureAccess;
use CodeIgniter\Filters\InvalidChars;
use CodeIgniter\Filters\PageCache;
use CodeIgniter\Filters\PerformanceMetrics;
use CodeIgniter\Filters\SecureHeaders;
use App\Filters\AuthFilter;
use App\Filters\ApiAuthFilter;
use App\Filters\ThrottleFilter;

class Filters extends BaseConfig
{
    public array $aliases = [
        'csrf'          => CSRF::class,
        'toolbar'       => DebugToolbar::class,
        'honeypot'      => InvalidChars::class,
        'secureheaders' => SecureHeaders::class,
        'pagecache'     => PageCache::class,
        'performance'   => PerformanceMetrics::class,
        'forcehttps'    => ForceSecureAccess::class,
        'auth'          => AuthFilter::class,
        'apiAuth'       => ApiAuthFilter::class,
        'throttle'      => ThrottleFilter::class,
    ];

    public array $globals = [
        'before' => [
            // 'honeypot',
            // 'csrf',
            // 'invalidchars',
        ],
        'after' => [
            'toolbar',
            // 'honeypot',
            // 'secureheaders',
        ],
    ];

    public array $methods = [];

    public array $filters = [
        'auth' => ['before' => ['admin/*', 'auth/profile/*', 'auth/oauth/unlink/*']],
        'throttle' => ['before' => ['auth/login', 'auth/register', 'auth/forgot-password']],
    ];
}
