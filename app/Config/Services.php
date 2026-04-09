<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseService;
use App\Libraries\Auth\Authentication;
use App\Libraries\Auth\PasswordHasher;
use App\Libraries\OAuth\OAuthProviderFactory;
use App\Libraries\Captcha\CaptchaService;
use App\Libraries\Settings\SettingsService;

class Services extends BaseService
{
    public static function authentication(?string $alias = null, bool $getShared = true): Authentication
    {
        if ($getShared) {
            return static::getSharedInstance('authentication', $alias);
        }

        return new Authentication();
    }

    public static function passwordHasher(bool $getShared = true): PasswordHasher
    {
        if ($getShared) {
            return static::getSharedInstance('passwordHasher');
        }

        return new PasswordHasher();
    }

    public static function oAuthProviderFactory(bool $getShared = true): OAuthProviderFactory
    {
        if ($getShared) {
            return static::getSharedInstance('oAuthProviderFactory');
        }

        return new OAuthProviderFactory();
    }

    public static function captchaService(bool $getShared = true): CaptchaService
    {
        if ($getShared) {
            return static::getSharedInstance('captchaService');
        }

        return new CaptchaService();
    }

    public static function settings(bool $getShared = true): SettingsService
    {
        if ($getShared) {
            return static::getSharedInstance('settings');
        }

        return new SettingsService();
    }
}
