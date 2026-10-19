<?php

declare(strict_types=1);

namespace StackFlow\Core;

/**
 * StackFlow Configuration Loader
 *
 * Loads all PHP array configuration files from `app/config/` and supports
 * dot-notation key access (e.g. `Config::get('database.connections.mysql.host')`).
 *
 * @package StackFlow\Core
 */
class Config
{
    /**
     * Configuration items repository.
     *
     * @var array<string, mixed>
     */
    private static array $items = [];

    /**
     * Load all configuration files from the `app/config` directory.
     *
     * @param string $basePath Project root path
     */
    public static function load(string $basePath): void
    {
        $configDir = rtrim($basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'config';

        if (!is_dir($configDir)) {
            return;
        }

        $files = glob($configDir . DIRECTORY_SEPARATOR . '*.php');
        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            $key = basename($file, '.php');
            $values = require $file;
            if (is_array($values)) {
                static::$items[$key] = $values;
            }
        }
    }

    /**
     * Retrieve a configuration item using dot notation.
     *
     * Example: Config::get('app.name', 'StackFlow')
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, static::$items)) {
            return static::$items[$key];
        }

        $segments = explode('.', $key);
        $target = static::$items;

        foreach ($segments as $segment) {
            if (is_array($target) && array_key_exists($segment, $target)) {
                $target = $target[$segment];
            } else {
                return $default;
            }
        }

        return $target;
    }

    /**
     * Set a configuration item using dot notation.
     */
    public static function set(string $key, mixed $value): void
    {
        $keys = explode('.', $key);
        $target = &static::$items;

        while (count($keys) > 1) {
            $k = array_shift($keys);
            if (!isset($target[$k]) || !is_array($target[$k])) {
                $target[$k] = [];
            }
            $target = &$target[$k];
        }

        $target[array_shift($keys)] = $value;
    }

    /**
     * Check if a config key exists.
     */
    public static function has(string $key): bool
    {
        return static::get($key) !== null;
    }

    /**
     * Return all configuration items.
     *
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        return static::$items;
    }
}
