<?php

declare(strict_types=1);

namespace StackFlow\Widgets\Layout;

use StackFlow\Widgets\Widget;

/**
 * Container — Centered, max-width layout container widget.
 *
 * Renders a centered div with a max-width and optional horizontal padding.
 *
 * Props:
 *   - child     (Widget|string|null) Single child widget or string
 *   - class     (string)            Extra CSS classes
 *   - maxWidth  (string)            'sm'|'md'|'lg'|'xl'|'2xl'|'full' (default 'xl')
 *   - center    (bool)              Whether to center with mx-auto (default true)
 *   - padding   (string)            Horizontal padding class (default 'px-4')
 *
 * @package StackFlow\Widgets\Layout
 */
class Container extends Widget
{
    /**
     * Valid max-width options mapped to Tailwind max-w-* classes.
     */
    private const MAX_WIDTHS = ['sm', 'md', 'lg', 'xl', '2xl', 'full'];

    public function render(): string
    {
        $child    = $this->prop('child');
        $extra    = (string) $this->prop('class', '');
        $maxWidth = (string) $this->prop('maxWidth', 'xl');
        $center   = (bool)   $this->prop('center', true);
        $padding  = (string) $this->prop('padding', 'px-4');

        // Validate maxWidth; fall back to xl
        if (!in_array($maxWidth, self::MAX_WIDTHS, true)) {
            $maxWidth = 'xl';
        }

        $maxWClass  = "max-w-{$maxWidth}";
        $centerClass = $center ? 'mx-auto' : '';
        $cssClasses  = $this->classes('container', $centerClass, $padding, $maxWClass, $extra);

        $childHtml = $this->renderChild($child);

        return "<div class=\"{$cssClasses}\">{$childHtml}</div>";
    }
}
