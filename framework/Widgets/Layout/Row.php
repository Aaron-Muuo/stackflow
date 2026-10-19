<?php

declare(strict_types=1);

namespace StackFlow\Widgets\Layout;

use StackFlow\Widgets\Widget;

/**
 * Row — Horizontal flex container widget.
 *
 * Renders children in a horizontal row using Tailwind flex utilities.
 *
 * Props:
 *   - children  (array<Widget|string>)  Child widgets or strings
 *   - class     (string)                Extra CSS classes
 *   - gap       (string)                Tailwind gap class (default 'gap-4')
 *   - align     (string)                'start'|'center'|'end'|'stretch' — maps to items-*
 *   - justify   (string)                Tailwind justify-content class (e.g. 'justify-between')
 *   - wrap      (bool)                  Whether to enable flex-wrap (default false)
 *
 * @package StackFlow\Widgets\Layout
 */
class Row extends Widget
{
    /**
     * Map align prop values to Tailwind items-* classes.
     */
    private const ALIGN_MAP = [
        'start'   => 'items-start',
        'center'  => 'items-center',
        'end'     => 'items-end',
        'stretch' => 'items-stretch',
    ];

    public function render(): string
    {
        $children = (array) $this->prop('children', []);
        $gap      = (string) $this->prop('gap', 'gap-4');
        $align    = (string) $this->prop('align', '');
        $justify  = (string) $this->prop('justify', '');
        $wrap     = (bool)   $this->prop('wrap', false);
        $extra    = (string) $this->prop('class', '');

        $alignClass   = self::ALIGN_MAP[$align] ?? '';
        $wrapClass    = $wrap ? 'flex-wrap' : '';
        $cssClasses   = $this->classes('flex', 'flex-row', $wrapClass, $gap, $alignClass, $justify, $extra);
        $childrenHtml = $this->renderChildren($children);

        return "<div class=\"{$cssClasses}\">{$childrenHtml}</div>";
    }
}
