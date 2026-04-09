<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class UserOauthModel extends Model
{
    protected $table            = 'user_oauth_connections';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id',
        'provider',
        'provider_id',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'provider_data',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'user_id'     => 'required|integer',
        'provider'    => 'required|max_length[50]',
        'provider_id' => 'required|max_length[255]',
    ];

    protected $beforeInsert = ['encodeData'];
    protected $beforeUpdate = ['encodeData'];
    protected $afterFind    = ['decodeData'];

    /**
     * Encode provider data
     */
    protected function encodeData(array $data): array
    {
        if (isset($data['data']['provider_data']) && is_array($data['data']['provider_data'])) {
            $data['data']['provider_data'] = json_encode($data['data']['provider_data']);
        }
        return $data;
    }

    /**
     * Decode provider data
     */
    protected function decodeData(array $data): array
    {
        if (isset($data['data'])) {
            if (is_array($data['data'])) {
                foreach ($data['data'] as &$item) {
                    $item = $this->decodeProviderData($item);
                }
            } else {
                $data['data'] = $this->decodeProviderData($data['data']);
            }
        }
        return $data;
    }

    private function decodeProviderData($item)
    {
        if (is_array($item) && isset($item['provider_data']) && is_string($item['provider_data'])) {
            $item['provider_data'] = json_decode($item['provider_data'], true) ?? [];
        }
        return $item;
    }

    /**
     * Find connection by provider and provider ID
     */
    public function findByProvider(string $provider, string $providerId): ?array
    {
        return $this->where('provider', $provider)
            ->where('provider_id', $providerId)
            ->first();
    }

    /**
     * Get connections for user
     */
    public function getByUserId(int $userId): array
    {
        return $this->where('user_id', $userId)
            ->findAll();
    }

    /**
     * Get specific connection
     */
    public function getByUserAndProvider(int $userId, string $provider): ?array
    {
        return $this->where('user_id', $userId)
            ->where('provider', $provider)
            ->first();
    }

    /**
     * Connect OAuth to user
     */
    public function connect(int $userId, string $provider, string $providerId, array $data = []): bool
    {
        // Check if already connected
        $existing = $this->getByUserAndProvider($userId, $provider);
        if ($existing) {
            return $this->update($existing['id'], [
                'provider_id'      => $providerId,
                'access_token'     => $data['access_token'] ?? null,
                'refresh_token'    => $data['refresh_token'] ?? null,
                'token_expires_at' => $data['expires_at'] ?? null,
                'provider_data'    => $data,
            ]);
        }

        return $this->insert([
            'user_id'          => $userId,
            'provider'         => $provider,
            'provider_id'      => $providerId,
            'access_token'     => $data['access_token'] ?? null,
            'refresh_token'    => $data['refresh_token'] ?? null,
            'token_expires_at' => $data['expires_at'] ?? null,
            'provider_data'    => $data,
        ]);
    }

    /**
     * Disconnect OAuth provider
     */
    public function disconnect(int $userId, string $provider): bool
    {
        $connection = $this->getByUserAndProvider($userId, $provider);
        if ($connection) {
            return $this->delete($connection['id']);
        }
        return false;
    }

    /**
     * Check if provider is connected
     */
    public function isConnected(int $userId, string $provider): bool
    {
        return $this->getByUserAndProvider($userId, $provider) !== null;
    }

    /**
     * Get connected providers for user
     */
    public function getConnectedProviders(int $userId): array
    {
        $connections = $this->getByUserId($userId);
        return array_column($connections, 'provider');
    }
}
