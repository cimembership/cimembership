<?php

declare(strict_types=1);

namespace CIMembership\Libraries\Settings;

use CIMembership\Models\OptionModel;

/**
 * Settings Service
 *
 * Provides a simple interface for managing application settings.
 *
 * @package CIMembership\Libraries\Settings
 */
class SettingsService
{
    protected ?OptionModel $optionModel = null;

    public function __construct()
    {
        $this->optionModel = model('OptionModel');
    }

    /**
     * Get a setting value
     */
    public function get(string $key, $default = null)
    {
        return $this->optionModel->getOption($key, $default);
    }

    /**
     * Set a setting value
     */
    public function set(string $key, $value): bool
    {
        return $this->optionModel->updateOption($key, $value);
    }

    /**
     * Get multiple settings
     */
    public function getMultiple(array $keys): array
    {
        $settings = [];
        foreach ($keys as $key) {
            $settings[$key] = $this->get($key);
        }
        return $settings;
    }

    /**
     * Delete a setting
     */
    public function delete(string $key): bool
    {
        return $this->optionModel->deleteOption($key);
    }

    /**
     * Get all settings
     */
    public function getAll(): array
    {
        return $this->optionModel->findAll();
    }

    /**
     * Get settings by prefix
     */
    public function getByPrefix(string $prefix): array
    {
        return $this->optionModel->like('option_name', $prefix . '_')->findAll();
    }
}
