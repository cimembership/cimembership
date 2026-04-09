<?php

declare(strict_types=1);

namespace CIMembership\Libraries\Auth;

/**
 * Secure Password Hashing using PHP's native password_hash()
 *
 * @package CIMembership\Libraries\Auth
 */
class PasswordHasher
{
    private string $algorithm = PASSWORD_DEFAULT;
    private array $options;

    public function __construct()
    {
        // Use bcrypt with a cost factor of 12
        $this->options = [
            'cost' => 12,
        ];
    }

    /**
     * Hash a password
     */
    public function hash(string $password): string
    {
        $hash = password_hash($password, $this->algorithm, $this->options);

        if ($hash === false) {
            throw new \RuntimeException('Failed to hash password');
        }

        return $hash;
    }

    /**
     * Verify a password against a hash
     */
    public function verify(string $password, string $hash): bool
    {
        // Handle legacy phpass hashes from CI3
        if ($this->isLegacyHash($hash)) {
            return $this->verifyLegacyHash($password, $hash);
        }

        return password_verify($password, $hash);
    }

    /**
     * Check if password needs rehash
     */
    public function needsRehash(string $hash): bool
    {
        // Legacy hashes always need rehash
        if ($this->isLegacyHash($hash) || strpos($hash, 'legacy:') === 0) {
            return true;
        }

        return password_needs_rehash($hash, $this->algorithm, $this->options);
    }

    /**
     * Check if hash is a legacy phpass hash
     */
    private function isLegacyHash(string $hash): bool
    {
        // phpass hashes start with $H$ or $P$
        return str_starts_with($hash, '$H$') || str_starts_with($hash, '$P$');
    }

    /**
     * Verify legacy phpass hash
     * Note: This requires the phpass library or a compatibility implementation
     */
    private function verifyLegacyHash(string $password, string $hash): bool
    {
        // For CI3 migration - return false to force password reset
        // Or implement proper phpass verification if needed
        return false;
    }

    /**
     * Generate a secure random password
     */
    public function generatePassword(int $length = 16): string
    {
        return bin2hex(random_bytes(max(8, $length / 2)));
    }

    /**
     * Check password strength
     */
    public function checkStrength(string $password): array
    {
        $strength = 0;
        $feedback = [];

        // Length check
        if (strlen($password) >= 12) {
            $strength += 2;
        } elseif (strlen($password) >= 8) {
            $strength += 1;
        } else {
            $feedback[] = 'Password should be at least 8 characters';
        }

        // Uppercase check
        if (preg_match('/[A-Z]/', $password)) {
            $strength += 1;
        } else {
            $feedback[] = 'Add uppercase letters';
        }

        // Lowercase check
        if (preg_match('/[a-z]/', $password)) {
            $strength += 1;
        } else {
            $feedback[] = 'Add lowercase letters';
        }

        // Number check
        if (preg_match('/[0-9]/', $password)) {
            $strength += 1;
        } else {
            $feedback[] = 'Add numbers';
        }

        // Special character check
        if (preg_match('/[^A-Za-z0-9]/', $password)) {
            $strength += 1;
        } else {
            $feedback[] = 'Add special characters';
        }

        return [
            'score'    => $strength,
            'max'      => 7,
            'strength' => match (true) {
                $strength >= 6 => 'strong',
                $strength >= 4 => 'medium',
                default    => 'weak',
            },
            'feedback' => $feedback,
        ];
    }
}
