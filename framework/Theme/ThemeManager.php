<?php

declare(strict_types=1);

namespace StackFlow\Theme;

use StackFlow\Theme\Themes\DefaultTheme;
use RuntimeException;

/**
 * ThemeManager — central registry for StackFlow themes.
 *
 * Usage:
 * ```php
 * // Boot registers the built-in DefaultTheme automatically:
 * ThemeManager::boot();
 *
 * // Register additional custom themes:
 * ThemeManager::register('ocean', new OceanTheme());
 *
 * // Switch the active theme (e.g. from app config):
 * ThemeManager::use(config('app.theme', 'default'));
 *
 * // In the document <head>, emit all theme-related tags:
 * echo ThemeManager::renderHead();
 * ```
 *
 * All methods are static so the manager is available globally without
 * container resolution, matching the simple bootstrap pattern used
 * throughout the StackFlow framework.
 */
class ThemeManager
{
    // -------------------------------------------------------------------------
    // State
    // -------------------------------------------------------------------------

    /** @var array<string, Theme> Registered themes keyed by name. */
    private static array $themes = [];

    /** @var string The name of the currently active theme. */
    private static string $activeTheme = 'default';

    // -------------------------------------------------------------------------
    // Registration
    // -------------------------------------------------------------------------

    /**
     * Register a theme under a given name.
     *
     * If a theme with the same name already exists it is silently overwritten,
     * allowing application code to replace the built-in DefaultTheme.
     *
     * @param string $name  Unique identifier for the theme (e.g. `'ocean'`).
     * @param Theme  $theme The theme instance to register.
     */
    public static function register(string $name, Theme $theme): void
    {
        self::$themes[$name] = $theme;
    }

    /**
     * Set the active theme by name.
     *
     * @param string $name The registered name of the theme to activate.
     * @throws RuntimeException When no theme with that name has been registered.
     */
    public static function use(string $name): void
    {
        if (! isset(self::$themes[$name])) {
            throw new RuntimeException(
                "StackFlow ThemeManager: theme \"{$name}\" is not registered. "
                . 'Available themes: ' . implode(', ', array_keys(self::$themes)) . '.'
            );
        }

        self::$activeTheme = $name;
    }

    // -------------------------------------------------------------------------
    // Resolution
    // -------------------------------------------------------------------------

    /**
     * Return the currently active Theme instance.
     *
     * @throws RuntimeException When no themes have been registered (boot not called).
     */
    public static function active(): Theme
    {
        if (empty(self::$themes)) {
            self::boot();
        }

        if (! isset(self::$themes[self::$activeTheme])) {
            throw new RuntimeException(
                "StackFlow ThemeManager: active theme \"{self::$activeTheme}\" is not registered."
            );
        }

        return self::$themes[self::$activeTheme];
    }

    // -------------------------------------------------------------------------
    // Bootstrap
    // -------------------------------------------------------------------------

    /**
     * Boot the ThemeManager.
     *
     * Registers the built-in {@see DefaultTheme} and, if an `app` config is
     * available, applies the theme name from `config('app.theme')`.
     *
     * This method is idempotent — calling it multiple times is safe.
     */
    public static function boot(): void
    {
        // Always ensure the default theme is available.
        if (! isset(self::$themes['default'])) {
            self::register('default', new DefaultTheme());
        }

        // Honor app config when the config helper is available.
        if (function_exists('config')) {
            /** @var array<string, mixed> $appConfig */
            $appConfig = config('app');
            $themeName = (string) ($appConfig['theme'] ?? 'default');

            if (isset(self::$themes[$themeName])) {
                self::$activeTheme = $themeName;
            }
        }
    }

    // -------------------------------------------------------------------------
    // Head rendering
    // -------------------------------------------------------------------------

    /**
     * Render all `<head>` tags required by the active theme.
     *
     * Returns a string containing (in order):
     *  1. Google Fonts `<link>` tags
     *  2. CSS custom properties `<style>` block
     *  3. Tailwind `<script>` config block  ← must come after the Tailwind CDN
     *
     * > **Note:** The Tailwind CDN `<script>` itself is injected by
     * > {@see PageRenderer} before this output so the `tailwind.config`
     * > assignment in step 3 can reference `tailwind.colors`.
     *
     * @return string
     */
    public static function renderHead(): string
    {
        $theme = self::active();

        return implode("\n", [
            $theme->googleFontsUrl(),
            $theme->cssVariables(),
            $theme->tailwindConfig(),
        ]);
    }
}
