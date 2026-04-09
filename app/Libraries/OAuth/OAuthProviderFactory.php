<?php

declare(strict_types=1);

namespace App\Libraries\OAuth;

use App\Models\OptionModel;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Facebook;
use League\OAuth2\Client\Provider\Google;
use League\OAuth2\Client\Provider\Github;
use League\OAuth2\Client\Provider\LinkedIn;
use League\OAuth2\Client\Provider\Twitter;
use League\OAuth2\Client\Provider\GenericProvider;

class OAuthProviderFactory
{
    protected ?OptionModel $optionModel = null;

    public function __construct()
    {
        $this->optionModel = model('OptionModel');
    }

    /**
     * Create OAuth provider instance
     */
    public function create(string $provider): ?AbstractProvider
    {
        $config = $this->getConfig($provider);

        if (!$config || empty($config['clientId']) || empty($config['clientSecret'])) {
            return null;
        }

        return match ($provider) {
            'facebook'  => $this->createFacebookProvider($config),
            'google'    => $this->createGoogleProvider($config),
            'github'    => $this->createGithubProvider($config),
            'linkedin'  => $this->createLinkedInProvider($config),
            'twitter'   => $this->createTwitterProvider($config),
            default     => null,
        };
    }

    /**
     * Check if provider is enabled
     */
    public function isEnabled(string $provider): bool
    {
        $config = $this->getConfig($provider);
        return $config !== null
            && !empty($config['clientId'])
            && !empty($config['clientSecret'])
            && ($config['enabled'] ?? false);
    }

    /**
     * Get enabled providers
     */
    public function getEnabledProviders(): array
    {
        $providers = ['facebook', 'google', 'github', 'linkedin', 'twitter', 'microsoft'];
        $enabled = [];

        foreach ($providers as $provider) {
            if ($this->isEnabled($provider)) {
                $enabled[] = $provider;
            }
        }

        return $enabled;
    }

    /**
     * Get provider configuration
     */
    protected function getConfig(string $provider): ?array
    {
        return [
            'enabled'      => $this->optionModel->getOption("oauth_{$provider}_enabled", false),
            'clientId'     => $this->optionModel->getOption("oauth_{$provider}_client_id", ''),
            'clientSecret' => $this->optionModel->getOption("oauth_{$provider}_client_secret", ''),
            'redirectUri'  => base_url("auth/oauth/{$provider}/callback"),
        ];
    }

    /**
     * Create Facebook provider
     */
    protected function createFacebookProvider(array $config): Facebook
    {
        return new Facebook([
            'clientId'     => $config['clientId'],
            'clientSecret' => $config['clientSecret'],
            'redirectUri'  => $config['redirectUri'],
            'graphApiVersion' => 'v18.0',
        ]);
    }

    /**
     * Create Google provider
     */
    protected function createGoogleProvider(array $config): Google
    {
        return new Google([
            'clientId'     => $config['clientId'],
            'clientSecret' => $config['clientSecret'],
            'redirectUri'  => $config['redirectUri'],
        ]);
    }

    /**
     * Create GitHub provider
     */
    protected function createGithubProvider(array $config): Github
    {
        return new Github([
            'clientId'     => $config['clientId'],
            'clientSecret' => $config['clientSecret'],
            'redirectUri'  => $config['redirectUri'],
        ]);
    }

    /**
     * Create LinkedIn provider
     */
    protected function createLinkedInProvider(array $config): LinkedIn
    {
        return new LinkedIn([
            'clientId'     => $config['clientId'],
            'clientSecret' => $config['clientSecret'],
            'redirectUri'  => $config['redirectUri'],
        ]);
    }

    /**
     * Create Twitter provider (OAuth 2.0)
     */
    protected function createTwitterProvider(array $config): GenericProvider
    {
        return new GenericProvider([
            'clientId'                => $config['clientId'],
            'clientSecret'            => $config['clientSecret'],
            'redirectUri'             => $config['redirectUri'],
            'urlAuthorize'            => 'https://twitter.com/i/oauth2/authorize',
            'urlAccessToken'          => 'https://api.twitter.com/2/oauth2/token',
            'urlResourceOwnerDetails' => 'https://api.twitter.com/2/users/me',
            'scopes'                  => ['tweet.read', 'users.read', 'offline.access'],
        ]);
    }

    /**
     * Get provider display name
     */
    public function getDisplayName(string $provider): string
    {
        return match ($provider) {
            'facebook'  => 'Facebook',
            'google'    => 'Google',
            'github'    => 'GitHub',
            'linkedin'  => 'LinkedIn',
            'twitter'   => 'Twitter',
            'microsoft' => 'Microsoft',
            default     => ucfirst($provider),
        };
    }

    /**
     * Get provider icon class
     */
    public function getIconClass(string $provider): string
    {
        return match ($provider) {
            'facebook'  => 'fab fa-facebook',
            'google'    => 'fab fa-google',
            'github'    => 'fab fa-github',
            'linkedin'  => 'fab fa-linkedin',
            'twitter'   => 'fab fa-twitter',
            'microsoft' => 'fab fa-microsoft',
            default     => 'fas fa-user',
        };
    }
}
