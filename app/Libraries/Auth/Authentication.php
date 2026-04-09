<?php

declare(strict_types=1);

namespace App\Libraries\Auth;

use App\Models\UserModel;
use App\Models\UserGroupModel;
use App\Models\LoginAttemptModel;
use App\Models\UserProfileModel;
use Config\Services;

class Authentication
{
    protected ?UserModel $userModel = null;
    protected ?UserGroupModel $groupModel = null;
    protected ?LoginAttemptModel $loginAttemptModel = null;
    protected ?UserProfileModel $profileModel = null;
    protected ?\CodeIgniter\Session\Session $session = null;

    public function __construct()
    {
        $this->userModel = model('UserModel');
        $this->groupModel = model('UserGroupModel');
        $this->loginAttemptModel = model('LoginAttemptModel');
        $this->profileModel = model('UserProfileModel');
        $this->session = service('session');
    }

    /**
     * Attempt login
     */
    public function attempt(array $credentials, bool $remember = false): \CodeIgniter\Shield\Auth\Result
    {
        $ipAddress = service('request')->getIPAddress();

        // Check if IP is blocked
        if ($this->loginAttemptModel->isBlocked($ipAddress)) {
            return new \CodeIgniter\Shield\Auth\Result([
                'success' => false,
                'reason'  => 'Too many failed attempts. Please try again later.',
            ]);
        }

        $user = $this->validateCredentials($credentials);

        if (!$user) {
            // Record failed attempt
            $this->loginAttemptModel->record(
                $ipAddress,
                $credentials['username'] ?? $credentials['email'] ?? null,
                null,
                false
            );

            return new \CodeIgniter\Shield\Auth\Result([
                'success' => false,
                'reason'  => 'Invalid credentials.',
            ]);
        }

        // Check user status
        if ($user['status'] === 'banned') {
            return new \CodeIgniter\Shield\Auth\Result([
                'success' => false,
                'reason'  => 'Your account has been banned.',
            ]);
        }

        if ($user['status'] === 'pending') {
            return new \CodeIgniter\Shield\Auth\Result([
                'success' => false,
                'reason'  => 'Please activate your account before logging in.',
            ]);
        }

        // Record successful login
        $this->loginAttemptModel->record($ipAddress, $user['email'], $user['id'], true);

        // Update login info
        $this->userModel->updateLoginInfo($user['id']);

        // Check if password needs rehash (legacy upgrade)
        if ($this->userModel->needsRehash($user['password_hash'])) {
            $hasher = new PasswordHasher();
            $this->userModel->update($user['id'], [
                'password_hash' => $hasher->hash($credentials['password']),
            ]);
        }

        // Log user in
        $this->login($user, $remember);

        return new \CodeIgniter\Shield\Auth\Result([
            'success' => true,
            'user'    => $user,
        ]);
    }

    /**
     * Login user by ID
     */
    public function loginById(int $userId, bool $remember = false): void
    {
        $user = $this->userModel->find($userId);
        if ($user) {
            $this->login($user, $remember);
        }
    }

    /**
     * Login user
     */
    protected function login(array $user, bool $remember = false): void
    {
        // Regenerate session ID
        $this->session->regenerate();

        // Store user data in session
        $this->session->set('user_id', $user['id']);
        $this->session->set('group_id', $user['group_id']);

        // Set remember me cookie if requested
        if ($remember) {
            $this->setRememberCookie($user['id']);
        }

        // Update last active
        $this->userModel->update($user['id'], [
            'last_active_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Logout user
     */
    public function logout(): void
    {
        // Clear remember cookie
        $this->clearRememberCookie();

        // Clear session
        $this->session->remove(['user_id', 'group_id']);
        $this->session->destroy();
    }

    /**
     * Check if user is logged in
     */
    public function isLoggedIn(): bool
    {
        $userId = $this->session->get('user_id');

        if (!$userId) {
            // Try remember cookie
            $userId = $this->getUserFromRememberCookie();
            if ($userId) {
                $this->loginById($userId);
                return true;
            }
            return false;
        }

        // Verify user still exists and is active
        $user = $this->userModel->find($userId);
        if (!$user || $user['status'] !== 'active') {
            $this->logout();
            return false;
        }

        return true;
    }

    /**
     * Get current user
     */
    public function getCurrentUser(): ?array
    {
        if (!$this->isLoggedIn()) {
            return null;
        }

        $userId = $this->session->get('user_id');
        return $this->userModel->find($userId);
    }

    /**
     * Get current user ID
     */
    public function getCurrentUserId(): ?int
    {
        return $this->session->get('user_id');
    }

    /**
     * Check if user has permission
     */
    public function hasPermission(string $permission): bool
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            return false;
        }

        // Super admin always has all permissions
        if ($user['group_id'] === 1) {
            return true;
        }

        return $this->groupModel->hasPermission($user['group_id'], $permission);
    }

    /**
     * Check if user is in group
     */
    public function inGroup(string|int $group): bool
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            return false;
        }

        if (is_numeric($group)) {
            return $user['group_id'] == $group;
        }

        $groupData = $this->groupModel->where('name', $group)->first();
        return $groupData && $user['group_id'] == $groupData['id'];
    }

    /**
     * Validate credentials
     */
    protected function validateCredentials(array $credentials): ?array
    {
        $identifier = $credentials['username'] ?? $credentials['email'] ?? null;
        $password = $credentials['password'] ?? null;

        if (empty($identifier) || empty($password)) {
            return null;
        }

        // Find user by username or email
        $user = filter_var($identifier, FILTER_VALIDATE_EMAIL)
            ? $this->userModel->findByEmail($identifier)
            : $this->userModel->findByUsername($identifier);

        if (!$user) {
            return null;
        }

        // Handle legacy passwords (CI3 upgrade)
        $passwordHash = $user['password_hash'];
        if (strpos($passwordHash, 'legacy:') === 0) {
            $legacyHash = substr($passwordHash, 7);
            if ($this->verifyLegacyPassword($password, $legacyHash)) {
                $user['password'] = $password; // For rehash
                return $user;
            }
            return null;
        }

        // Verify password
        $hasher = new PasswordHasher();
        if (!$hasher->verify($password, $passwordHash)) {
            return null;
        }

        return $user;
    }

    /**
     * Verify legacy CI3 password (phpass)
     */
    protected function verifyLegacyPassword(string $password, string $hash): bool
    {
        // Simple fallback for legacy passwords
        // In production, use the proper phpass library or upgrade immediately
        return false;
    }

    /**
     * Set remember me cookie
     */
    protected function setRememberCookie(int $userId): void
    {
        $token = bin2hex(random_bytes(32));
        $selector = bin2hex(random_bytes(12));

        // Store in database
        $this->userModel->update($userId, [
            'remember_token' => $selector . ':' . hash('sha256', $token),
        ]);

        // Set cookie
        $cookie = [
            'name'   => 'remember',
            'value'  => $selector . ':' . $token,
            'expire' => 30 * DAY,
            'httponly' => true,
            'secure' => service('request')->isSecure(),
        ];

        service('response')->setCookie($cookie);
    }

    /**
     * Clear remember cookie
     */
    protected function clearRememberCookie(): void
    {
        $cookie = service('request')->getCookie('remember');
        if ($cookie) {
            // Clear from database
            $parts = explode(':', $cookie);
            if (count($parts) === 2) {
                $user = $this->userModel->where('remember_token LIKE', $parts[0] . ':%')->first();
                if ($user) {
                    $this->userModel->update($user['id'], ['remember_token' => null]);
                }
            }
        }

        // Delete cookie
        service('response')->deleteCookie('remember');
    }

    /**
     * Get user from remember cookie
     */
    protected function getUserFromRememberCookie(): ?int
    {
        $cookie = service('request')->getCookie('remember');
        if (!$cookie) {
            return null;
        }

        $parts = explode(':', $cookie);
        if (count($parts) !== 2) {
            return null;
        }

        $selector = $parts[0];
        $token = $parts[1];

        $user = $this->userModel->where('remember_token LIKE', $selector . ':%')->first();
        if (!$user) {
            return null;
        }

        // Validate token
        $storedToken = substr($user['remember_token'], strlen($selector) + 1);
        if (!hash_equals($storedToken, hash('sha256', $token))) {
            return null;
        }

        return $user['id'];
    }
}
