<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class OptionModel extends Model
{
    protected $table            = 'options';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'option_name',
        'option_value',
        'is_serialized',
        'autoload',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'option_name' => 'required|max_length[64]',
    ];

    protected $validationMessages = [];

    private static ?array $cachedOptions = null;

    /**
     * Get option value
     */
    public function getOption(string $name, $default = null)
    {
        // Check cache
        if (self::$cachedOptions === null) {
            $this->loadAutoloadOptions();
        }

        if (isset(self::$cachedOptions[$name])) {
            return self::$cachedOptions[$name];
        }

        // Fetch from database
        $option = $this->where('option_name', $name)->first();
        if ($option) {
            $value = $this->decodeValue($option);
            self::$cachedOptions[$name] = $value;
            return $value;
        }

        return $default;
    }

    /**
     * Update or create option
     */
    public function updateOption(string $name, $value): bool
    {
        $existing = $this->where('option_name', $name)->first();

        $isSerialized = false;
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value);
            $isSerialized = true;
        }

        $data = [
            'option_name'   => $name,
            'option_value'    => $value,
            'is_serialized'   => $isSerialized ? 1 : 0,
        ];

        if ($existing) {
            $result = $this->update($existing['id'], $data);
        } else {
            $data['autoload'] = 0;
            $data['created_at'] = date('Y-m-d H:i:s');
            $result = $this->insert($data);
        }

        // Update cache
        self::$cachedOptions[$name] = $this->decodeValue(['option_value' => $value, 'is_serialized' => $isSerialized]);

        return $result;
    }

    /**
     * Delete option
     */
    public function deleteOption(string $name): bool
    {
        $option = $this->where('option_name', $name)->first();
        if ($option) {
            $this->delete($option['id']);
            unset(self::$cachedOptions[$name]);
            return true;
        }
        return false;
    }

    /**
     * Load autoload options into cache
     */
    private function loadAutoloadOptions(): void
    {
        self::$cachedOptions = [];

        $options = $this->where('autoload', 1)->findAll();
        foreach ($options as $option) {
            self::$cachedOptions[$option['option_name']] = $this->decodeValue($option);
        }
    }

    /**
     * Decode option value
     */
    private function decodeValue(array $option)
    {
        if ($option['is_serialized'] ?? false) {
            $decoded = json_decode($option['option_value'], true);
            return $decoded !== null ? $decoded : $option['option_value'];
        }
        return $option['option_value'];
    }

    /**
     * Get all options
     */
    public function getAllOptions(): array
    {
        return $this->findAll();
    }

    /**
     * Get options by prefix
     */
    public function getByPrefix(string $prefix): array
    {
        return $this->like('option_name', $prefix, 'after')->findAll();
    }

    /**
     * Clear cache
     */
    public static function clearCache(): void
    {
        self::$cachedOptions = null;
    }

    /**
     * Get site settings for admin
     */
    public function getSiteSettings(): array
    {
        return [
            'site_name'            => $this->getOption('site_name', 'CIMembership'),
            'site_description'     => $this->getOption('site_description', ''),
            'webmaster_email'      => $this->getOption('webmaster_email', ''),
            'allow_registration'   => $this->getOption('allow_registration', '1'),
            'require_activation'   => $this->getOption('require_activation', '1'),
            'captcha_enabled'      => $this->getOption('captcha_enabled', '0'),
            'recaptcha_enabled'    => $this->getOption('recaptcha_enabled', '0'),
            'recaptcha_site_key'   => $this->getOption('recaptcha_site_key', ''),
            'recaptcha_secret_key' => $this->getOption('recaptcha_secret_key', ''),
        ];
    }

    /**
     * Update site settings
     */
    public function updateSiteSettings(array $data): void
    {
        foreach ($data as $key => $value) {
            $this->updateOption($key, $value);
        }
    }
}
