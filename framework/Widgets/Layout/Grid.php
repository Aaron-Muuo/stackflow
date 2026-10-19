<?php

declare(strict_types=1);

namespace StackFlow\Widgets\Layout;

use StackFlow\Widgets\Widget;

/**
 * Grid — CSS grid container widget.
 *
 * Renders children in a responsive CSS grid. Always mobile-first:
 * 1 column on mobile, 2 on sm breakpoint, and `cols` on lg breakpoint.
 *
 * Props:
 *   - children  (array<Widget|string>)  Child widgets or strings
 *   - cols      (int)                   Number of columns on lg+ screens (1-12, default 3)
 *   - gap       (string)                Tailwind gap class (default 'gap-6')
 *   - class     (string)                Extra CSS classes
 *
 * @package StackFlow\Widgets\Layout
 */
class Grid extends Widget
{
    public function render(): string
    {
        $children = (array) $this->prop('children', []);
        $cols     = max(1, min(12, (int) $this->prop('cols', 3)));
        $gap      = (string) $this->prop('gap', 'gap-6');
        $extra    = (string) $this->prop('class', '');

        // Always responsive: 1 col mobile, 2 col sm, n col lg
        $colClass   = "grid-cols-1 sm:grid-cols-2 lg:grid-cols-{$cols}";
        $cssClasses = $this->classes('grid', $colClass, $gap, $extra);

        $childrenHtml = $this->renderChildren($children);

        return "<div class=\"{$cssClasses}\">{$childrenHtml}</div>";
    }
}
