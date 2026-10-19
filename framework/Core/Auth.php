<?php

declare(strict_types=1);

namespace StackFlow\Core;

/**
 * StackFlow Authentication Helper
 *
 * Provides convenient authentication methods backed by the session and user models.
 *
 * @package StackFlow\Core
 */
class Auth
{
    private static string $currentGuard = 'web';

    /**
     * Set the active guard.
     */
    public static function guard(string $guard = 'web'): static
    {
        static::$currentGuard = $guard;
        return new static();
    }

    /**
     * Check if a user is authenticated.
     */
    public static function check(): bool
    {
        return static::user() !== null;
    }

    /**
     * Get the authenticated user ID.
     */
    public static function id(): mixed
    {
        $user = static::user();
        if ($user === null) {
            return null;
        }

        return $user->id ?? null;
    }

    /**
     * Get the currently authenticated user object.
     */
    public static function user(): ?object
    {
        $guardKey = '_sf_auth_' . static::$currentGuard;
        $userId = Session::get($guardKey);

        if ($userId === null) {
            return null;
        }

        $guardConfig = Config::get("auth.guards." . static::$currentGuard);
        $modelClass = $guardConfig['model'] ?? 'App\\Models\\User';

        if (class_exists($modelClass) && method_exists($modelClass, 'find')) {
            return $modelClass::find($userId);
        }

        return null;
    }

    /**
     * Log in a user object.
     */
    public static function login(object $user): void
    {
        $guardKey = '_sf_auth_' . static::$currentGuard;
        $id = $user->id ?? null;

        if ($id !== null) {
            Session::regenerate();
            Session::set($guardKey, $id);
        }
    }

    /**
     * Attempt login with credentials (e.g. ['email' => $e, 'password' => $p]).
     */
    public static function attempt(array $credentials): bool
    {
        $guardConfig = Config::get("auth.guards." . static::$currentGuard);
        $modelClass = $guardConfig['model'] ?? 'App\\Models\\User';

        if (!class_exists($modelClass)) {
            return false;
        }

        $password = $credentials['password'] ?? null;
        unset($credentials['password']);

        if (!$password) {
            return false;
        }

        $query = $modelClass::query();
        foreach ($credentials as $col => $val) {
            $query->where($col, '=', $val);
        }

        $user = $query->first();
        if (!$user) {
            return false;
        }

        $storedPassword = $user->password ?? '';
        if (password_verify($password, $storedPassword)) {
            static::login($user);
            return true;
        }

        return false;
    }

    /**
     * Log out the currently authenticated user.
     */
    public static function logout(): void
    {
        $guardKey = '_sf_auth_' . static::$currentGuard;
        Session::forget($guardKey);
        Session::regenerate();
    }
}
