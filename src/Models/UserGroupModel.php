<?php

declare(strict_types=1);

namespace CIMembership\Models;

use CodeIgniter\Model;

/**
 * User Group Model
 *
 * Manages user groups and permissions.
 *
 * @package CIMembership\Models
 */
class UserGroupModel extends Model
{
    protected $table            = 'users_groups';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'name',
        'description',
        'status',
        'permissions',
        'is_default',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'id'       => 'permit_empty|integer',
        'name'     => 'required|min_length[2]|max_length[50]',
        'status'   => 'required|in_list[0,1]',
    ];

    protected $validationMessages = [
        'name' => [
            'required'   => 'Group name is required',
            'min_length' => 'Group name must be at least 2 characters',
            'max_length' => 'Group name cannot exceed 50 characters',
        ],
    ];

    protected $afterFind = ['decodePermissions'];
    protected $beforeInsert = ['encodePermissions'];
    protected $beforeUpdate = ['encodePermissions'];

    /**
     * Decode JSON permissions after find
     */
    protected function decodePermissions(array $data): array
    {
        if (isset($data['data'])) {
            if (is_array($data['data'])) {
                foreach ($data['data'] as &$item) {
                    $item = $this->decodePerm($item);
                }
            } else {
                $data['data'] = $this->decodePerm($data['data']);
            }
        }
        return $data;
    }

    /**
     * Encode permissions to JSON before save
     */
    protected function encodePermissions(array $data): array
    {
        if (isset($data['data']['permissions'])) {
            if (is_array($data['data']['permissions'])) {
                $data['data']['permissions'] = json_encode($data['data']['permissions']);
            }
        }
        return $data;
    }

    private function decodePerm($item)
    {
        if (is_array($item) && isset($item['permissions']) && is_string($item['permissions'])) {
            $item['permissions'] = json_decode($item['permissions'], true) ?? [];
        }
        return $item;
    }

    /**
     * Get default group
     */
    public function getDefault(): ?array
    {
        return $this->where('is_default', 1)
            ->where('status', 1)
            ->first();
    }

    /**
     * Get active groups
     */
    public function getActive(): array
    {
        return $this->where('status', 1)
            ->findAll();
    }

    /**
     * Get groups for dropdown
     */
    public function getForDropdown(): array
    {
        $groups = $this->where('status', 1)
            ->findAll();

        $dropdown = [];
        foreach ($groups as $group) {
            $dropdown[$group['id']] = $group['name'];
        }

        return $dropdown;
    }

    /**
     * Check if group has permission
     */
    public function hasPermission(int $groupId, string $permission): bool
    {
        $group = $this->find($groupId);
        if (!$group) {
            return false;
        }

        $permissions = $group['permissions'] ?? [];
        return isset($permissions[$permission]) && $permissions[$permission] === '1';
    }

    /**
     * Get all available permissions
     */
    public function getAvailablePermissions(): array
    {
        return [
            'access_backend'      => 'Access Backend',
            'view_users'          => 'View Users',
            'create_users'        => 'Create Users',
            'edit_users'          => 'Edit Users',
            'delete_users'        => 'Delete Users',
            'view_user_groups'    => 'View User Groups',
            'create_user_groups'  => 'Create User Groups',
            'edit_user_groups'    => 'Edit User Groups',
            'delete_user_groups'  => 'Delete User Groups',
            'general_settings'    => 'General Settings',
            'login_to_frontend'   => 'Login to Frontend',
        ];
    }

    /**
     * Check if group can be deleted
     */
    public function canDelete(int $groupId): bool
    {
        // Check if any users belong to this group
        $userModel = model('UserModel');
        $count = $userModel->where('group_id', $groupId)
            ->where('deleted_at', null)
            ->countAllResults();

        return $count === 0;
    }
}
