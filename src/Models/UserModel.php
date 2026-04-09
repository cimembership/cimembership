<?php

declare(strict_types=1);

namespace CIMembership\Models;

use CodeIgniter\Database\Exceptions\DataException;
use CodeIgniter\Model;
use CIMembership\Libraries\Auth\PasswordHasher;

/**
 * User Model
 *
 * Handles user data, authentication, and related operations.
 *
 * @package CIMembership\Models
 */
class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'username',
        'email',
        'password_hash',
        'group_id',
        'status',
        'ban_reason',
        'activation_token',
        'activation_expires',
        'reset_token',
        'reset_expires',
        'remember_token',
        'last_login_at',
        'last_login_ip',
        'last_active_at',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules = [
        'id'            => 'permit_empty|integer',
        'username'      => 'required|min_length[3]|max_length[50]|is_unique[users.username,id,{id}]',
        'email'         => 'required|valid_email|max_length[100]|is_unique[users.email,id,{id}]',
        'password'      => 'permit_empty|min_length[8]', // Only for create, not update
        'group_id'      => 'required|integer',
        'status'        => 'required|in_list[active,inactive,banned,pending]',
    ];

    protected $validationMessages = [
        'username' => [
            'required'   => 'Username is required',
            'min_length' => 'Username must be at least 3 characters',
            'max_length' => 'Username cannot exceed 50 characters',
            'is_unique'  => 'This username is already taken',
        ],
        'email' => [
            'required'    => 'Email is required',
            'valid_email' => 'Please enter a valid email address',
            'is_unique'   => 'This email is already registered',
        ],
        'password' => [
            'min_length' => 'Password must be at least 8 characters',
        ],
    ];

    protected $skipValidation = false;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = ['hashPassword'];
    protected $afterInsert    = ['createProfile'];
    protected $beforeUpdate   = ['hashPassword'];
    protected $afterFind      = ['withProfile', 'withGroup'];

    /**
     * Automatically hash password before insert/update
     */
    protected function hashPassword(array $data): array
    {
        if (isset($data['data']['password']) && !empty($data['data']['password'])) {
            $hasher = new PasswordHasher();
            $data['data']['password_hash'] = $hasher->hash($data['data']['password']);
            unset($data['data']['password']);
        }
        return $data;
    }

    /**
     * Create user profile after insert
     */
    protected function createProfile(array $data): array
    {
        if (!isset($data['id'])) {
            return $data;
        }

        $profileModel = model('UserProfileModel');
        $profileData = [
            'user_id'     => $data['id'],
            'first_name'  => $data['data']['first_name'] ?? null,
            'last_name'   => $data['data']['last_name'] ?? null,
            'created_at'  => date('Y-m-d H:i:s'),
        ];

        $profileModel->insert($profileData);
        return $data;
    }

    /**
     * Load profile with user data
     */
    protected function withProfile(array $data): array
    {
        if (!$this->shouldJoin()) {
            return $data;
        }

        if (isset($data['data'])) {
            $data['data'] = $this->attachProfile($data['data']);
        } elseif (is_array($data)) {
            $data = $this->attachProfile($data);
        }

        return $data;
    }

    /**
     * Load group with user data
     */
    protected function withGroup(array $data): array
    {
        if (!$this->shouldJoin()) {
            return $data;
        }

        if (isset($data['data'])) {
            $data['data'] = $this->attachGroup($data['data']);
        } elseif (is_array($data)) {
            $data = $this->attachGroup($data);
        }

        return $data;
    }

    private function attachProfile($user)
    {
        if (is_array($user) && isset($user['id'])) {
            $profileModel = model('UserProfileModel');
            $user['profile'] = $profileModel->where('user_id', $user['id'])->first();
        }
        return $user;
    }

    private function attachGroup($user)
    {
        if (is_array($user) && isset($user['group_id'])) {
            $groupModel = model('UserGroupModel');
            $user['group'] = $groupModel->find($user['group_id']);
        }
        return $user;
    }

    private function shouldJoin(): bool
    {
        // Check if we're in a context where joining is appropriate
        return true;
    }

    /**
     * Find user by username
     */
    public function findByUsername(string $username): ?array
    {
        return $this->where('username', $username)->first();
    }

    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?array
    {
        return $this->where('email', $email)->first();
    }

    /**
     * Find user by activation token
     */
    public function findByActivationToken(string $token): ?array
    {
        return $this->where('activation_token', $token)
            ->where('activation_expires >', date('Y-m-d H:i:s'))
            ->first();
    }

    /**
     * Find user by reset token
     */
    public function findByResetToken(string $token): ?array
    {
        return $this->where('reset_token', $token)
            ->where('reset_expires >', date('Y-m-d H:i:s'))
            ->first();
    }

    /**
     * Activate user account
     */
    public function activate(int $userId): bool
    {
        return $this->update($userId, [
            'status'            => 'active',
            'activation_token'    => null,
            'activation_expires'  => null,
        ]);
    }

    /**
     * Ban user
     */
    public function ban(int $userId, ?string $reason = null): bool
    {
        return $this->update($userId, [
            'status'     => 'banned',
            'ban_reason' => $reason,
        ]);
    }

    /**
     * Update login info
     */
    public function updateLoginInfo(int $userId): bool
    {
        return $this->update($userId, [
            'last_login_at'   => date('Y-m-d H:i:s'),
            'last_login_ip'   => service('request')->getIPAddress(),
            'last_active_at'  => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Verify password
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        $hasher = new PasswordHasher();
        return $hasher->verify($password, $hash);
    }

    /**
     * Needs rehash
     */
    public function needsRehash(string $hash): bool
    {
        $hasher = new PasswordHasher();
        return $hasher->needsRehash($hash);
    }

    /**
     * Get users with pagination
     */
    public function getPaginated(array $filters = [], int $perPage = 20): array
    {
        $builder = $this->builder();

        // Apply filters
        if (!empty($filters['search'])) {
            $builder->groupStart()
                ->like('username', $filters['search'], 'both')
                ->orLike('email', $filters['search'], 'both')
                ->groupEnd();
        }

        if (!empty($filters['status'])) {
            $builder->where('status', $filters['status']);
        }

        if (!empty($filters['group_id'])) {
            $builder->where('group_id', $filters['group_id']);
        }

        // Join with profile for search
        if (!empty($filters['search'])) {
            $builder->join('user_profiles', 'user_profiles.user_id = users.id', 'LEFT');
            $builder->orLike('user_profiles.first_name', $filters['search'], 'both');
            $builder->orLike('user_profiles.last_name', $filters['search'], 'both');
        }

        return [
            'items' => $this->paginate($perPage),
            'pager' => $this->pager,
        ];
    }
}
