<?php

declare(strict_types=1);

namespace StackFlow\Core;

/**
 * StackFlow Session Manager
 *
 * Handles native PHP sessions, CSRF token generation, and flash messages.
 *
 * @package StackFlow\Core
 */
class Session
{
    private const FLASH_KEY = '_sf_flash';
    private const TOKEN_KEY = '_sf_token';

    /**
     * Start the session if not already active.
     */
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            if (!headers_sent()) {
                ini_set('session.cookie_httponly', '1');
                ini_set('session.use_only_cookies', '1');
                @session_start();
            } else {
                @session_start();
            }
        }

        // Clean up old flash items on subsequent requests
        if (isset($_SESSION[self::FLASH_KEY]['old'])) {
            unset($_SESSION[self::FLASH_KEY]['old']);
        }
        if (isset($_SESSION[self::FLASH_KEY]['new'])) {
            $_SESSION[self::FLASH_KEY]['old'] = $_SESSION[self::FLASH_KEY]['new'];
            unset($_SESSION[self::FLASH_KEY]['new']);
        }
    }

    /**
     * Put a key/value pair into the session.
     */
    public static function set(string $key, mixed $value): void
    {
        static::start();
        $_SESSION[$key] = $value;
    }

    /**
     * Get a value from the session.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        static::start();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Check if a key exists in the session.
     */
    public static function has(string $key): bool
    {
        static::start();
        return isset($_SESSION[$key]);
    }

    /**
     * Remove a key from the session.
     */
    public static function forget(string $key): void
    {
        static::start();
        unset($_SESSION[$key]);
    }

    /**
     * Flash a key/value pair that will expire after the next request.
     */
    public static function flash(string $key, mixed $value): void
    {
        static::start();
        $_SESSION[self::FLASH_KEY]['new'][$key] = $value;
    }

    /**
     * Get a flashed value.
     */
    public static function getFlash(string $key, mixed $default = null): mixed
    {
        static::start();
        return $_SESSION[self::FLASH_KEY]['new'][$key]
            ?? $_SESSION[self::FLASH_KEY]['old'][$key]
            ?? $default;
    }

    /**
     * Get or create a CSRF token.
     */
    public static function token(): string
    {
        static::start();
        if (!isset($_SESSION[self::TOKEN_KEY])) {
            $_SESSION[self::TOKEN_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::TOKEN_KEY];
    }

    /**
     * Regenerate the session ID.
     */
    public static function regenerate(): void
    {
        static::start();
        session_regenerate_id(true);
    }

    /**
     * Destroy the session completely.
     */
    public static function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }
            session_destroy();
        }
    }
}
