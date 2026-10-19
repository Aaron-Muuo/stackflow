<?php

declare(strict_types=1);

namespace StackFlow\Widgets\Display;

use StackFlow\Widgets\Widget;

/**
 * Divider — Horizontal rule / labeled section divider widget.
 *
 * When a `label` prop is provided, renders a centered text divider
 * using flex + before/after border lines. Without a label, renders a plain <hr>.
 *
 * Props:
 *   - class  (string)       Extra CSS classes applied to the wrapper
 *   - label  (string|null)  Optional text to display in the center of the divider
 *   - color  (string)       Border color class (default 'border-gray-200 dark:border-gray-700')
 *
 * @package StackFlow\Widgets\Display
 */
class Divider extends Widget
{
    public function render(): string
    {
        $label = $this->prop('label');
        $extra = (string) $this->prop('class', '');
        $color = (string) $this->prop('color', 'border-gray-200 dark:border-gray-700');

        if ($label !== null && $label !== '') {
            return $this->renderLabeled((string) $label, $color, $extra);
        }

        $hrClasses = $this->classes('border-t', $color, $extra);
        return "<hr class=\"{$hrClasses}\">";
    }

    /**
     * Render the labeled divider variant.
     */
    private function renderLabeled(string $label, string $color, string $extra): string
    {
        $safeLabel  = $this->e($label);
        $wrapClass  = $this->classes('flex', 'items-center', 'gap-3', $extra);
        $lineClass  = "flex-1 border-t {$color}";
        $labelClass = 'text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap';

        return <<<HTML
<div class="{$wrapClass}" role="separator" aria-label="{$safeLabel}">
    <span class="{$lineClass}"></span>
    <span class="{$labelClass}">{$safeLabel}</span>
    <span class="{$lineClass}"></span>
</div>
HTML;
    }
}
