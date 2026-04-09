<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class UserProfileModel extends Model
{
    protected $table            = 'user_profiles';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id',
        'first_name',
        'last_name',
        'display_name',
        'phone',
        'company',
        'website',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'avatar',
        'timezone',
        'locale',
        'bio',
        'date_of_birth',
        'gender',
        'social_links',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'user_id' => 'required|integer',
    ];

    protected $validationMessages = [];

    protected $beforeInsert = ['prepareData'];
    protected $beforeUpdate = ['prepareData'];

    /**
     * Prepare data for insert/update
     */
    protected function prepareData(array $data): array
    {
        if (isset($data['data']['social_links']) && is_array($data['data']['social_links'])) {
            $data['data']['social_links'] = json_encode($data['data']['social_links']);
        }
        return $data;
    }

    /**
     * Get profile by user ID
     */
    public function getByUserId(int $userId): ?array
    {
        return $this->where('user_id', $userId)->first();
    }

    /**
     * Get full name
     */
    public function getFullName(int $userId): string
    {
        $profile = $this->getByUserId($userId);
        if (!$profile) {
            return '';
        }

        if (!empty($profile['display_name'])) {
            return $profile['display_name'];
        }

        $parts = array_filter([
            $profile['first_name'] ?? null,
            $profile['last_name'] ?? null,
        ]);

        return implode(' ', $parts);
    }

    /**
     * Get avatar URL
     */
    public function getAvatarUrl(int $userId): string
    {
        $profile = $this->getByUserId($userId);

        if ($profile && !empty($profile['avatar'])) {
            return base_url('uploads/avatars/' . $profile['avatar']);
        }

        // Return default avatar (Gravatar or default image)
        $user = model('UserModel')->find($userId);
        if ($user) {
            $email = md5(strtolower(trim($user['email'])));
            return "https://www.gravatar.com/avatar/{$email}?d=mp&s=128";
        }

        return base_url('assets/images/default-avatar.png');
    }

    /**
     * Update profile by user ID
     */
    public function updateByUserId(int $userId, array $data): bool
    {
        $profile = $this->where('user_id', $userId)->first();
        if (!$profile) {
            $data['user_id'] = $userId;
            return $this->insert($data) !== false;
        }
        return $this->update($profile['id'], $data);
    }

    /**
     * Upload avatar
     */
    public function uploadAvatar(int $userId, $file): ?string
    {
        if (!$file->isValid()) {
            return null;
        }

        $newName = $file->getRandomName();
        $file->move(WRITEPATH . 'uploads/avatars/', $newName);

        $this->updateByUserId($userId, ['avatar' => $newName]);

        return $newName;
    }
}
