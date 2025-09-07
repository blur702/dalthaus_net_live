<?php

declare(strict_types=1);

namespace CMS\Models;

/**
 * Settings Model
 * 
 * Handles key-value settings storage and retrieval
 * for site configuration and preferences.
 * 
 * @package CMS\Models
 * @author  Kevin
 * @version 1.0.0
 */
class Settings extends BaseModel
{
    /**
     * Table name
     */
    protected string $table = 'settings';

    /**
     * Primary key
     */
    protected string $primaryKey = 'setting_id';

    /**
     * Cache for settings to avoid multiple database queries
     */
    private static array $settingsCache = [];

    /**
     * Default settings values
     */
    private static array $defaults = [
        'site_title' => 'My CMS',
        'site_motto' => 'A Simple Content Management System',
        'site_logo' => '',
        'favicon' => '',
        'admin_email' => 'admin@example.com',
        'timezone' => 'America/New_York',
        'date_format' => 'Y-m-d',
        'items_per_page' => '10'
    ];

    /**
     * Get setting value by name
     * 
     * @param string $name Setting name
     * @param mixed $default Default value if setting not found
     * @return mixed
     */
    public static function get(string $name, mixed $default = null): mixed
    {
        // Check cache first
        if (array_key_exists($name, self::$settingsCache)) {
            return self::$settingsCache[$name];
        }

        $instance = new static();
        $value = $instance->db->fetchColumn(
            "SELECT setting_value FROM {$instance->table} WHERE setting_name = ?",
            [$name]
        );

        if ($value === false || $value === null) {
            $value = $default ?? self::$defaults[$name] ?? null;
        }

        // Cache the value
        self::$settingsCache[$name] = $value;

        return $value;
    }

    /**
     * Set setting value
     * 
     * @param string $name Setting name
     * @param mixed $value Setting value
     * @return bool
     */
    public static function set(string $name, mixed $value): bool
    {
        $instance = new static();
        
        // Convert value to string for storage
        $valueString = is_bool($value) ? ($value ? '1' : '0') : (string) $value;

        try {
            // Check if setting exists
            $exists = $instance->db->exists($instance->table, 'setting_name = ?', [$name]);

            if ($exists) {
                // Update existing setting
                $result = $instance->db->update(
                    $instance->table,
                    ['setting_value' => $valueString],
                    'setting_name = ?',
                    [$name]
                );
                $success = $result > 0;
            } else {
                // Insert new setting
                $id = $instance->db->insert($instance->table, [
                    'setting_name' => $name,
                    'setting_value' => $valueString
                ]);
                $success = !empty($id);
            }

            if ($success) {
                // Update cache
                self::$settingsCache[$name] = $value;
            }

            return $success;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get multiple settings
     * 
     * @param array $names Array of setting names
     * @return array Associative array of settings
     */
    public static function getMultiple(array $names): array
    {
        $instance = new static();
        $result = [];

        // Get uncached settings from database
        $uncached = [];
        foreach ($names as $name) {
            if (array_key_exists($name, self::$settingsCache)) {
                $result[$name] = self::$settingsCache[$name];
            } else {
                $uncached[] = $name;
            }
        }

        if (!empty($uncached)) {
            $placeholders = str_repeat('?,', count($uncached) - 1) . '?';
            $settings = $instance->db->fetchAll(
                "SELECT setting_name, setting_value FROM {$instance->table} WHERE setting_name IN ({$placeholders})",
                $uncached
            );

            foreach ($settings as $setting) {
                $value = $setting['setting_value'];
                $result[$setting['setting_name']] = $value;
                self::$settingsCache[$setting['setting_name']] = $value;
            }

            // Add defaults for missing settings
            foreach ($uncached as $name) {
                if (!isset($result[$name])) {
                    $default = self::$defaults[$name] ?? null;
                    $result[$name] = $default;
                    self::$settingsCache[$name] = $default;
                }
            }
        }

        return $result;
    }

    /**
     * Get all settings
     * 
     * @return array
     */
    public static function getAll(): array
    {
        $instance = new static();
        $settings = $instance->db->fetchAll(
            "SELECT setting_name, setting_value FROM {$instance->table}"
        );

        $result = [];
        foreach ($settings as $setting) {
            $result[$setting['setting_name']] = $setting['setting_value'];
        }

        // Add defaults for missing settings
        foreach (self::$defaults as $name => $default) {
            if (!isset($result[$name])) {
                $result[$name] = $default;
            }
        }

        // Update cache
        self::$settingsCache = $result;

        return $result;
    }

    /**
     * Set multiple settings at once
     * 
     * @param array $settings Associative array of setting_name => value pairs
     * @return bool
     */
    public static function setMultiple(array $settings): bool
    {
        $instance = new static();

        try {
            $instance->db->beginTransaction();

            foreach ($settings as $name => $value) {
                $valueString = is_bool($value) ? ($value ? '1' : '0') : (string) $value;
                
                $exists = $instance->db->exists($instance->table, 'setting_name = ?', [$name]);

                if ($exists) {
                    $instance->db->update(
                        $instance->table,
                        ['setting_value' => $valueString],
                        'setting_name = ?',
                        [$name]
                    );
                } else {
                    $instance->db->insert($instance->table, [
                        'setting_name' => $name,
                        'setting_value' => $valueString
                    ]);
                }

                // Update cache
                self::$settingsCache[$name] = $value;
            }

            $instance->db->commit();
            return true;
        } catch (\Exception $e) {
            $instance->db->rollback();
            error_log('Settings::setMultiple error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a setting by name
     * 
     * @param string $name Setting name
     * @return bool
     */
    public static function deleteSetting(string $name): bool
    {
        $instance = new static();
        
        $deleted = $instance->db->delete($instance->table, 'setting_name = ?', [$name]);
        
        if ($deleted > 0) {
            // Remove from cache
            unset(self::$settingsCache[$name]);
            return true;
        }

        return false;
    }

    /**
     * Check if setting exists
     * 
     * @param string $name Setting name
     * @return bool
     */
    public static function settingExists(string $name): bool
    {
        $instance = new static();
        return $instance->db->exists($instance->table, 'setting_name = ?', [$name]);
    }

    /**
     * Get settings for admin form
     * 
     * @return array
     */
    public static function getForAdmin(): array
    {
        return self::getMultiple([
            'site_title',
            'site_motto', 
            'site_logo',
            'favicon',
            'admin_email',
            'timezone',
            'date_format',
            'items_per_page'
        ]);
    }

    /**
     * Get boolean setting value
     * 
     * @param string $name Setting name
     * @param bool $default Default value
     * @return bool
     */
    public static function getBool(string $name, bool $default = false): bool
    {
        $value = self::get($name, $default);
        
        if (is_bool($value)) {
            return $value;
        }
        
        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on']);
    }

    /**
     * Get integer setting value
     * 
     * @param string $name Setting name
     * @param int $default Default value
     * @return int
     */
    public static function getInt(string $name, int $default = 0): int
    {
        return (int) self::get($name, $default);
    }

    /**
     * Get float setting value
     * 
     * @param string $name Setting name
     * @param float $default Default value
     * @return float
     */
    public static function getFloat(string $name, float $default = 0.0): float
    {
        return (float) self::get($name, $default);
    }

    /**
     * Clear settings cache
     * 
     * @return void
     */
    public static function clearCache(): void
    {
        self::$settingsCache = [];
    }

    /**
     * Validate setting data
     * 
     * @param array $data Settings data
     * @return array Array of validation errors
     */
    public static function validateSettings(array $data): array
    {
        $errors = [];

        // Site title validation
        if (empty($data['site_title'])) {
            $errors['site_title'] = 'Site title is required';
        } elseif (strlen($data['site_title']) > 255) {
            $errors['site_title'] = 'Site title must be less than 255 characters';
        }

        // Site motto validation
        if (isset($data['site_motto']) && strlen($data['site_motto']) > 500) {
            $errors['site_motto'] = 'Site motto must be less than 500 characters';
        }

        // Email validation
        if (!empty($data['admin_email']) && !filter_var($data['admin_email'], FILTER_VALIDATE_EMAIL)) {
            $errors['admin_email'] = 'Invalid email address';
        }

        // Items per page validation
        if (isset($data['items_per_page'])) {
            $itemsPerPage = (int) $data['items_per_page'];
            if ($itemsPerPage < 1 || $itemsPerPage > 100) {
                $errors['items_per_page'] = 'Items per page must be between 1 and 100';
            }
        }

        // Timezone validation
        if (!empty($data['timezone']) && !in_array($data['timezone'], timezone_identifiers_list())) {
            $errors['timezone'] = 'Invalid timezone';
        }

        return $errors;
    }

    /**
     * Get available timezones
     * 
     * @return array
     */
    public static function getAvailableTimezones(): array
    {
        $timezones = [];
        foreach (timezone_identifiers_list() as $timezone) {
            $timezones[$timezone] = $timezone;
        }
        return $timezones;
    }

    /**
     * Get available date formats
     * 
     * @return array
     */
    public static function getAvailableDateFormats(): array
    {
        $now = time();
        return [
            'Y-m-d' => date('Y-m-d', $now),
            'm/d/Y' => date('m/d/Y', $now),
            'd/m/Y' => date('d/m/Y', $now),
            'F j, Y' => date('F j, Y', $now),
            'j F Y' => date('j F Y', $now),
            'M j, Y' => date('M j, Y', $now),
            'j M Y' => date('j M Y', $now)
        ];
    }
}
