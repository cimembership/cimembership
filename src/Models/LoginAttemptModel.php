<?php

declare(strict_types=1);

namespace CIMembership\Models;

use CodeIgniter\Model;

/**
 * Login Attempt Model
 *
 * Tracks login attempts for rate limiting and security.
 *
 * @package CIMembership\Models
 */
class LoginAttemptModel extends Model
{
    protected $table            = 'login_attempts';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'ip_address',
        'identifier',
        'user_id',
        'success',
        'user_agent',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';

    protected $validationRules = [
        'ip_address' => 'required',
    ];

    /**
     * Record login attempt
     */
    public function record(string $ipAddress, ?string $identifier = null, ?int $userId = null, bool $success = false): void
    {
        $this->insert([
            'ip_address' => $ipAddress,
            'identifier' => $identifier,
            'user_id'    => $userId,
            'success'    => $success ? 1 : 0,
            'user_agent' => service('request')->getUserAgent()->getAgentString(),
        ]);
    }

    /**
     * Get failed attempts count
     */
    public function getFailedAttempts(string $ipAddress, int $minutes = 15): int
    {
        return $this->where('ip_address', $ipAddress)
            ->where('success', 0)
            ->where('created_at >', date('Y-m-d H:i:s', strtotime("-{$minutes} minutes")))
            ->countAllResults();
    }

    /**
     * Get attempts for user
     */
    public function getUserAttempts(int $userId, int $limit = 10): array
    {
        return $this->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    /**
     * Clear old attempts
     */
    public function clearOld(int $days = 30): int
    {
        return $this->where('created_at <', date('Y-m-d H:i:s', strtotime("-{$days} days")))
            ->delete();
    }

    /**
     * Is IP blocked
     */
    public function isBlocked(string $ipAddress, int $maxAttempts = 5, int $minutes = 15): bool
    {
        return $this->getFailedAttempts($ipAddress, $minutes) >= $maxAttempts;
    }

    /**
     * Get recent activity
     */
    public function getRecentActivity(int $limit = 50): array
    {
        return $this->select('login_attempts.*, users.username')
            ->join('users', 'users.id = login_attempts.user_id', 'LEFT')
            ->orderBy('login_attempts.created_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }
}
