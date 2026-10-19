<?php

declare(strict_types=1);

namespace StackFlow\Theme;

/**
 * Abstract base class for all StackFlow themes.
 *
 * A Theme encapsulates:
 *  - A color palette (used in Tailwind config + CSS variables)
 *  - A font stack (loaded from Google Fonts)
 *  - A Tailwind JS config block (injected via <script>)
 *  - CSS custom properties (injected via <style>:root{...}</style>)
 *
 * Concrete themes extend this class and implement the abstract methods.
 */
abstract class Theme
{
    // -------------------------------------------------------------------------
    // Abstract contract
    // -------------------------------------------------------------------------

    /**
     * Unique machine-readable name for this theme (e.g. `'default'`, `'ocean'`).
     *
     * @return string
     */
    abstract public function name(): string;

    /**
     * Color palette used to generate Tailwind config and CSS variables.
     *
     * Expected keys (all optional, sensible defaults are used when missing):
     *  - `primary`   — main brand color (Tailwind color name, e.g. `'indigo'`)
     *  - `secondary` — secondary color
     *  - `accent`    — highlight / accent color
     *  - `danger`    — destructive-action color (default `'red'`)
     *  - `warning`   — warning color (default `'amber'`)
     *  - `success`   — success color (default `'green'`)
     *  - `info`      — informational color (default `'sky'`)
     *
     * @return array<string, string>
     */
    abstract public function colors(): array;

    /**
     * Font family definitions.
     *
     * Expected keys:
     *  - `sans` — default sans-serif font (e.g. `'Inter'`)
     *  - `mono` — monospace font (e.g. `'JetBrains Mono'`)
     *
     * @return array<string, string>
     */
    abstract public function fonts(): array;

    // -------------------------------------------------------------------------
    // Concrete helpers
    // -------------------------------------------------------------------------

    /**
     * Generate a Tailwind Play CDN `tailwind.config` block as a <script> tag.
     *
     * Injects custom colors and font families derived from the theme's palette.
     * The `darkMode` strategy is set to `'class'` so StackFlow can control it
     * programmatically.
     *
     * @return string A `<script>tailwind.config = {...}</script>` block.
     */
    public function tailwindConfig(): string
    {
        $colors = $this->colors();
        $fonts  = $this->fonts();

        $primary   = $colors['primary']   ?? 'indigo';
        $secondary = $colors['secondary'] ?? 'gray';
        $accent    = $colors['accent']    ?? 'violet';
        $danger    = $colors['danger']    ?? 'red';
        $warning   = $colors['warning']   ?? 'amber';
        $success   = $colors['success']   ?? 'green';
        $info      = $colors['info']      ?? 'sky';

        $sans = $fonts['sans'] ?? 'Inter';
        $mono = $fonts['mono'] ?? 'ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace';

        // JSON-encode the font stacks so the output is always valid JS.
        $sansJs = json_encode([$sans, 'ui-sans-serif', 'system-ui', 'sans-serif']);
        $monoJs = json_encode([$mono, 'ui-monospace', 'SFMono-Regular', 'Consolas', 'monospace']);

        return <<<JS
        <script>
            tailwind.config = {
                darkMode: 'class',
                theme: {
                    extend: {
                        colors: {
                            primary:   (typeof tailwind !== 'undefined' && tailwind.colors && tailwind.colors['{$primary}'])   ? tailwind.colors['{$primary}']   : '{$primary}',
                            secondary: (typeof tailwind !== 'undefined' && tailwind.colors && tailwind.colors['{$secondary}']) ? tailwind.colors['{$secondary}'] : '{$secondary}',
                            accent:    (typeof tailwind !== 'undefined' && tailwind.colors && tailwind.colors['{$accent}'])    ? tailwind.colors['{$accent}']    : '{$accent}',
                            danger:    (typeof tailwind !== 'undefined' && tailwind.colors && tailwind.colors['{$danger}'])    ? tailwind.colors['{$danger}']    : '{$danger}',
                            warning:   (typeof tailwind !== 'undefined' && tailwind.colors && tailwind.colors['{$warning}'])   ? tailwind.colors['{$warning}']   : '{$warning}',
                            success:   (typeof tailwind !== 'undefined' && tailwind.colors && tailwind.colors['{$success}'])   ? tailwind.colors['{$success}']   : '{$success}',
                            info:      (typeof tailwind !== 'undefined' && tailwind.colors && tailwind.colors['{$info}'])      ? tailwind.colors['{$info}']      : '{$info}',
                        },
                        fontFamily: {
                            sans: {$sansJs},
                            mono: {$monoJs},
                        },
                    },
                },
            };
        </script>
        JS;
    }

    /**
     * Generate a `<style>:root{...}</style>` block with CSS custom properties.
     *
     * These variables are available globally in CSS and can be referenced by
     * custom components outside Tailwind's purge cycle.
     *
     * @return string
     */
    public function cssVariables(): string
    {
        $colors = $this->colors();
        $fonts  = $this->fonts();

        $vars = [];

        foreach ($colors as $key => $value) {
            $cssKey  = '--sf-color-' . preg_replace('/[^a-z0-9\-]/', '-', strtolower($key));
            $vars[] = "    {$cssKey}: {$value};";
        }

        foreach ($fonts as $key => $value) {
            $cssKey  = '--sf-font-' . preg_replace('/[^a-z0-9\-]/', '-', strtolower($key));
            $vars[] = "    {$cssKey}: \"{$value}\", sans-serif;";
        }

        $varBlock = implode("\n", $vars);

        return <<<HTML
        <style>
            :root {
        {$varBlock}
            }
        </style>
        HTML;
    }

    /**
     * Build a Google Fonts URL that loads all fonts declared in `fonts()`.
     *
     * Returns an empty string when the theme has no fonts to load.
     *
     * @return string A Google Fonts `<link>` tag, or empty string.
     */
    public function googleFontsUrl(): string
    {
        $fonts = $this->fonts();

        if (empty($fonts)) {
            return '';
        }

        $families = [];
        foreach ($fonts as $font) {
            // Encode the family name for use in a URL (spaces → +).
            $families[] = 'family=' . urlencode($font) . ':wght@300;400;500;600;700';
        }

        $query = implode('&', $families) . '&display=swap';
        $url   = 'https://fonts.googleapis.com/css2?' . $query;
        $safe  = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');

        return <<<HTML
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="{$safe}" rel="stylesheet">
        HTML;
    }
}
