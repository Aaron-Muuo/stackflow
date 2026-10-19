<?php

declare(strict_types=1);

namespace StackFlow\Theme\Themes;

use StackFlow\Theme\Theme;

/**
 * StackFlow Default Theme.
 *
 * A clean, modern theme built on:
 *  - Indigo as the primary brand color
 *  - Gray for secondary / neutral surfaces
 *  - Violet for accent highlights
 *  - Inter as the UI sans-serif typeface (via Google Fonts)
 *
 * Supports both light and dark modes via Tailwind's `class` dark-mode
 * strategy — the `dark` class is toggled on `<html>` by the framework.
 */
class DefaultTheme extends Theme
{
    // -------------------------------------------------------------------------
    // Identity
    // -------------------------------------------------------------------------

    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return 'default';
    }

    // -------------------------------------------------------------------------
    // Palette
    // -------------------------------------------------------------------------

    /**
     * {@inheritdoc}
     *
     * Color names map to Tailwind's built-in palette keys so the Tailwind
     * config can resolve them as full shade objects (50–950).
     */
    public function colors(): array
    {
        return [
            'primary'   => 'indigo',
            'secondary' => 'gray',
            'accent'    => 'violet',
            'danger'    => 'red',
            'warning'   => 'amber',
            'success'   => 'emerald',
            'info'      => 'sky',
        ];
    }

    // -------------------------------------------------------------------------
    // Typography
    // -------------------------------------------------------------------------

    /**
     * {@inheritdoc}
     */
    public function fonts(): array
    {
        return [
            'sans' => 'Inter',
            'mono' => 'JetBrains Mono',
        ];
    }

    // -------------------------------------------------------------------------
    // Tailwind config override
    // -------------------------------------------------------------------------

    /**
     * Returns a comprehensive Tailwind Play CDN config block.
     *
     * Extends the base implementation with:
     *  - Explicit shade objects for primary / accent colors
     *  - Custom `borderRadius` scale
     *  - Custom `boxShadow` utilities
     *  - Custom `animation` + `keyframes` for spinner / fade-in
     *
     * @return string
     */
    public function tailwindConfig(): string
    {
        return <<<'JS'
        <script>
            tailwind.config = {
                darkMode: 'class',
                theme: {
                    extend: {
                        colors: {
                            primary: {
                                50:  '#eef2ff',
                                100: '#e0e7ff',
                                200: '#c7d2fe',
                                300: '#a5b4fc',
                                400: '#818cf8',
                                500: '#6366f1',
                                600: '#4f46e5',
                                700: '#4338ca',
                                800: '#3730a3',
                                900: '#312e81',
                                950: '#1e1b4b',
                            },
                            secondary: {
                                50:  '#f9fafb',
                                100: '#f3f4f6',
                                200: '#e5e7eb',
                                300: '#d1d5db',
                                400: '#9ca3af',
                                500: '#6b7280',
                                600: '#4b5563',
                                700: '#374151',
                                800: '#1f2937',
                                900: '#111827',
                                950: '#030712',
                            },
                            accent: {
                                50:  '#f5f3ff',
                                100: '#ede9fe',
                                200: '#ddd6fe',
                                300: '#c4b5fd',
                                400: '#a78bfa',
                                500: '#8b5cf6',
                                600: '#7c3aed',
                                700: '#6d28d9',
                                800: '#5b21b6',
                                900: '#4c1d95',
                                950: '#2e1065',
                            },
                            danger:  (typeof tailwind !== 'undefined' && tailwind.colors) ? tailwind.colors.red : '#ef4444',
                            warning: (typeof tailwind !== 'undefined' && tailwind.colors) ? tailwind.colors.amber : '#f59e0b',
                            success: (typeof tailwind !== 'undefined' && tailwind.colors) ? tailwind.colors.emerald : '#10b981',
                            info:    (typeof tailwind !== 'undefined' && tailwind.colors) ? tailwind.colors.sky : '#0ea5e9',
                        },
                        fontFamily: {
                            sans: ['-apple-system', 'BlinkMacSystemFont', '"SF Pro Display"', '"SF Pro Text"', '"SF Pro"', 'system-ui', '-apple-system', 'sans-serif'],
                            mono: ['JetBrains Mono', 'ui-monospace', 'SFMono-Regular', 'Consolas', 'monospace'],
                        },
                        borderRadius: {
                            'sm': '0.25rem',
                            DEFAULT: '0.375rem',
                            'md': '0.5rem',
                            'lg': '0.75rem',
                            'xl': '1rem',
                            '2xl': '1.5rem',
                        },
                        boxShadow: {
                            'sf-sm':  '0 1px 3px 0 rgb(0 0 0 / 0.08), 0 1px 2px -1px rgb(0 0 0 / 0.08)',
                            'sf-md':  '0 4px 6px -1px rgb(0 0 0 / 0.07), 0 2px 4px -2px rgb(0 0 0 / 0.07)',
                            'sf-lg':  '0 10px 15px -3px rgb(0 0 0 / 0.07), 0 4px 6px -4px rgb(0 0 0 / 0.07)',
                            'sf-xl':  '0 20px 25px -5px rgb(0 0 0 / 0.07), 0 8px 10px -6px rgb(0 0 0 / 0.07)',
                        },
                        animation: {
                            'sf-spin':     'spin 1s linear infinite',
                            'sf-fade-in':  'sfFadeIn 0.2s ease-out',
                            'sf-slide-up': 'sfSlideUp 0.3s ease-out',
                        },
                        keyframes: {
                            sfFadeIn: {
                                '0%':   { opacity: '0' },
                                '100%': { opacity: '1' },
                            },
                            sfSlideUp: {
                                '0%':   { opacity: '0', transform: 'translateY(8px)' },
                                '100%': { opacity: '1', transform: 'translateY(0)' },
                            },
                        },
                    },
                },
            };
        </script>
        JS;
    }

    // -------------------------------------------------------------------------
    // Google Fonts override
    // -------------------------------------------------------------------------

    /**
     * Returns a <link> tag that loads both Inter and JetBrains Mono from
     * Google Fonts with a preconnect hint for performance.
     *
     * @return string
     */
    public function googleFontsUrl(): string
    {
        $url = 'https://fonts.googleapis.com/css2?'
             . 'family=Inter:wght@300;400;500;600;700&'
             . 'family=JetBrains+Mono:wght@400;500;700&'
             . 'display=swap';

        $safe = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');

        return <<<HTML
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="{$safe}" rel="stylesheet">
        HTML;
    }
}
