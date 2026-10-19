<?php

declare(strict_types=1);

namespace StackFlow\Core;

use Dotenv\Dotenv;
use RuntimeException;

/**
 * StackFlow Environment Loader
 *
 * Loads and provides access to .env environment variables.
 *
 * @package StackFlow\Core
 */
class Env
{
    /**
     * Whether .env has already been loaded.
     */
    private static bool $loaded = false;

    /**
     * Load the .env file from the given directory path.
     *
     * @param string $path Project root directory containing .env
     */
    public static function load(string $path): void
    {
        if (static::$loaded) {
            return;
        }

        $envFile = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '.env';

        // Check if vlucas/phpdotenv is available
        if (class_exists(Dotenv::class) && file_exists($envFile)) {
            $dotenv = Dotenv::createImmutable($path);
            $dotenv->safeLoad();
            static::$loaded = true;
            return;
        }

        // Native fallback parser if Dotenv package is not installed or .env exists
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($lines !== false) {
                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line === '' || str_starts_with($line, '#')) {
                        continue;
                    }

                    if (str_contains($line, '=')) {
                        [$key, $val] = explode('=', $line, 2);
                        $key = trim($key);
                        $val = trim($val);

                        // Remove surrounding quotes if present
                        if (
                            (str_starts_with($val, '"') && str_ends_with($val, '"')) ||
                            (str_starts_with($val, "'") && str_ends_with($val, "'"))
                        ) {
                            $val = substr($val, 1, -1);
                        }

                        $_ENV[$key] = $val;
                        putenv("{$key}={$val}");
                    }
                }
            }
        }

        static::$loaded = true;
    }

    /**
     * Get an environment variable value with type casting for booleans and null.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? getenv($key);

        if ($value === false || $value === null) {
            return $default;
        }

        return match (strtolower((string) $value)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'empty', '(empty)' => '',
            'null', '(null)' => null,
            default => $value,
        };
    }

    /**
     * Get a required environment variable or throw an exception.
     *
     * @throws RuntimeException
     */
    public static function required(string $key): string
    {
        $val = static::get($key);
        if ($val === null || $val === '') {
            throw new RuntimeException("Missing required environment variable [{$key}].");
        }

        return (string) $val;
    }
}
