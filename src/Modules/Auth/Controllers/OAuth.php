<?php

declare(strict_types=1);

namespace CIMembership\Modules\Auth\Controllers;

use App\Controllers\BaseController;
use CIMembership\Libraries\Auth\Authentication;
use CIMembership\Libraries\OAuth\OAuthProviderFactory;
use CIMembership\Models\UserModel;
use CIMembership\Models\UserOauthModel;
use CIMembership\Models\UserGroupModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * OAuth Controller
 *
 * Handles OAuth provider authentication and account linking.
 *
 * @package CIMembership\Modules\Auth\Controllers
 */
class OAuth extends BaseController
{
    protected ?Authentication $auth = null;
    protected ?UserModel $userModel = null;
    protected ?UserOauthModel $oauthModel = null;
    protected ?OAuthProviderFactory $providerFactory = null;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->auth = new Authentication();
        $this->userModel = model('UserModel');
        $this->oauthModel = model('UserOauthModel');
        $this->providerFactory = new OAuthProviderFactory();
    }

    /**
     * Redirect to OAuth provider
     */
    public function redirect(string $provider): ResponseInterface
    {
        $oauthProvider = $this->providerFactory->create($provider);

        if (!$oauthProvider) {
            return redirect()->to('/auth/login')
                ->with('error', 'OAuth provider not configured.');
        }

        $authUrl = $oauthProvider->getAuthorizationUrl([
            'scope' => $this->getProviderScopes($provider),
        ]);

        // Store state in session
        $this->session->set('oauth_state', $oauthProvider->getState());
        $this->session->set('oauth_provider', $provider);

        return redirect()->to($authUrl);
    }

    /**
     * Handle OAuth callback
     */
    public function callback(string $provider): ResponseInterface
    {
        $state = $this->request->getGet('state');
        $code = $this->request->getGet('code');
        $error = $this->request->getGet('error');

        // Check for errors
        if ($error) {
            return redirect()->to('/auth/login')
                ->with('error', 'OAuth error: ' . $error);
        }

        // Validate state
        $sessionState = $this->session->get('oauth_state');
        if (empty($state) || $state !== $sessionState) {
            return redirect()->to('/auth/login')
                ->with('error', 'Invalid OAuth state.');
        }

        // Clear session
        $this->session->remove('oauth_state');
        $this->session->remove('oauth_provider');

        $oauthProvider = $this->providerFactory->create($provider);

        if (!$oauthProvider) {
            return redirect()->to('/auth/login')
                ->with('error', 'OAuth provider not configured.');
        }

        try {
            // Get access token
            $token = $oauthProvider->getAccessToken('authorization_code', [
                'code' => $code,
            ]);

            // Get user details
            $ownerDetails = $oauthProvider->getResourceOwner($token);
            $oauthData = $ownerDetails->toArray();

            $providerId = $this->getProviderId($provider, $oauthData);
            $email = $oauthData['email'] ?? null;
            $name = $oauthData['name'] ?? null;

            // Try to find existing OAuth connection
            $existingConnection = $this->oauthModel->findByProvider($provider, $providerId);

            if ($existingConnection) {
                // User already connected, log them in
                $this->auth->loginById($existingConnection['user_id']);

                // Update tokens
                $this->oauthModel->update($existingConnection['id'], [
                    'access_token'     => $token->getToken(),
                    'refresh_token'    => $token->getRefreshToken(),
                    'token_expires_at' => $token->getExpires() ? date('Y-m-d H:i:s', $token->getExpires()) : null,
                    'provider_data'    => $oauthData,
                ]);

                return redirect()->to('/')
                    ->with('success', 'Welcome back!');
            }

            // Check if user is logged in (linking account)
            if ($this->auth->isLoggedIn()) {
                $currentUser = $this->auth->getCurrentUser();

                // Link OAuth to current user
                $this->oauthModel->connect($currentUser['id'], $provider, $providerId, [
                    'access_token'     => $token->getToken(),
                    'refresh_token'    => $token->getRefreshToken(),
                    'expires_at'       => $token->getExpires() ? date('Y-m-d H:i:s', $token->getExpires()) : null,
                    'provider_data'    => $oauthData,
                ]);

                return redirect()->to('/auth/profile')
                    ->with('success', ucfirst($provider) . ' account linked successfully.');
            }

            // Check if email exists
            if ($email) {
                $existingUser = $this->userModel->findByEmail($email);

                if ($existingUser) {
                    // Link OAuth to existing user and log them in
                    $this->oauthModel->connect($existingUser['id'], $provider, $providerId, [
                        'access_token'     => $token->getToken(),
                        'refresh_token'    => $token->getRefreshToken(),
                        'expires_at'       => $token->getExpires() ? date('Y-m-d H:i:s', $token->getExpires()) : null,
                        'provider_data'    => $oauthData,
                    ]);

                    $this->auth->loginById($existingUser['id']);

                    return redirect()->to('/')
                        ->with('success', 'Welcome back!');
                }
            }

            // Create new user
            $defaultGroup = model('UserGroupModel')->getDefault();

            $userData = [
                'username'    => $this->generateUsername($oauthData),
                'email'       => $email,
                'password'    => bin2hex(random_bytes(16)), // Random password
                'group_id'    => $defaultGroup['id'] ?? 5,
                'status'      => 'active',
                'first_name'  => $oauthData['first_name'] ?? ($name ? explode(' ', $name)[0] : null),
                'last_name'   => $oauthData['last_name'] ?? ($name ? (strpos($name, ' ') !== false ? substr($name, strpos($name, ' ') + 1) : null) : null),
            ];

            $userId = $this->userModel->insert($userData);

            if (!$userId) {
                return redirect()->to('/auth/login')
                    ->with('error', 'Failed to create account.');
            }

            // Link OAuth
            $this->oauthModel->connect($userId, $provider, $providerId, [
                'access_token'     => $token->getToken(),
                'refresh_token'    => $token->getRefreshToken(),
                'expires_at'       => $token->getExpires() ? date('Y-m-d H:i:s', $token->getExpires()) : null,
                'provider_data'    => $oauthData,
            ]);

            // Log in the new user
            $this->auth->loginById($userId);

            return redirect()->to('/')
                ->with('success', 'Welcome! Your account has been created.');

        } catch (\Exception $e) {
            log_message('error', 'OAuth error: ' . $e->getMessage());
            return redirect()->to('/auth/login')
                ->with('error', 'Authentication failed. Please try again.');
        }
    }

    /**
     * Unlink OAuth provider
     */
    public function unlink(string $provider): ResponseInterface
    {
        if (!$this->auth->isLoggedIn()) {
            return redirect()->to('/auth/login');
        }

        $user = $this->auth->getCurrentUser();

        // Check if this is the only authentication method
        $connectedProviders = $this->oauthModel->getConnectedProviders($user['id']);

        // Check if user has a password set
        $userData = $this->userModel->find($user['id']);
        $hasPassword = !empty($userData['password_hash']) && strpos($userData['password_hash'], 'legacy:') !== 0;

        if (count($connectedProviders) <= 1 && !$hasPassword) {
            return redirect()->to('/auth/profile')
                ->with('error', 'Cannot unlink: You must have at least one login method.');
        }

        if ($this->oauthModel->disconnect($user['id'], $provider)) {
            return redirect()->to('/auth/profile')
                ->with('success', ucfirst($provider) . ' account unlinked.');
        }

        return redirect()->to('/auth/profile')
            ->with('error', 'Failed to unlink account.');
    }

    /**
     * Get provider ID from response data
     */
    private function getProviderId(string $provider, array $data): string
    {
        return match ($provider) {
            'facebook'  => $data['id'] ?? '',
            'google'    => $data['sub'] ?? $data['id'] ?? '',
            'github'    => (string) ($data['id'] ?? ''),
            'linkedin'  => $data['sub'] ?? $data['id'] ?? '',
            'twitter'   => $data['id'] ?? '',
            'microsoft' => $data['oid'] ?? $data['id'] ?? '',
            default     => $data['id'] ?? '',
        };
    }

    /**
     * Get OAuth scopes for provider
     */
    private function getProviderScopes(string $provider): array
    {
        return match ($provider) {
            'facebook'  => ['email', 'public_profile'],
            'google'    => ['email', 'profile'],
            'github'    => ['user:email'],
            'linkedin'  => ['r_liteprofile', 'r_emailaddress'],
            'twitter'   => [],
            'microsoft' => ['User.Read'],
            default     => ['email', 'profile'],
        };
    }

    /**
     * Generate username from OAuth data
     */
    private function generateUsername(array $data): string
    {
        $base = '';

        if (isset($data['login'])) {
            $base = $data['login'];
        } elseif (isset($data['email'])) {
            $base = strtok($data['email'], '@');
        } elseif (isset($data['name'])) {
            $base = strtolower(str_replace(' ', '_', $data['name']));
        } else {
            $base = 'user';
        }

        // Clean username
        $base = preg_replace('/[^a-zA-Z0-9_]/', '', $base);
        $base = substr($base, 0, 20);

        // Check if exists
        $username = $base;
        $counter = 1;

        while ($this->userModel->where('username', $username)->countAllResults() > 0) {
            $username = $base . $counter;
            $counter++;
        }

        return $username;
    }
}
