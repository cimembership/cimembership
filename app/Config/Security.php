<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Security extends BaseConfig
{
    /**
     * CSRF Token name
     */
    public string $tokenName = 'csrf_token';

    /**
     * CSRF Header name
     */
    public string $headerName = 'X-CSRF-TOKEN';

    /**
     * CSRF Cookie name
     */
    public string $cookieName = 'csrf_cookie';

    /**
     * CSRF Expire time (in seconds)
     * Default: 1 hour
     */
    public int $expire = 3600;

    /**
     * CSRF Regenerate
     * Regenerate token on every submission
     */
    public bool $regenerate = true;

    /**
     * CSRF Redirect
     * Redirect to previous page with error on failure
     */
    public bool $redirect = false;

    /**
     * CSRF SameSite
     */
    public string $samesite = 'Lax';

    /**
     * Content Security Policy settings
     */
    public bool $CSPEnabled = false;
}
