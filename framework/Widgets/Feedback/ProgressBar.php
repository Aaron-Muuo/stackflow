<?php

declare(strict_types=1);

namespace StackFlow\Widgets\Feedback;

use StackFlow\Widgets\Widget;

/**
 * Progress Bar Widget
 *
 * Renders an animated, accessible linear progress bar with custom colors,
 * sizing options, stripes, and label overlays.
 *
 * Props:
 *  - value: int|float (0 to 100)
 *  - variant: 'primary'|'success'|'warning'|'danger'|'gradient' (default: 'primary')
 *  - size: 'sm'|'md'|'lg' (default: 'md')
 *  - label: bool (default: false)
 *  - labelPosition: 'top'|'inside' (default: 'top')
 *  - striped: bool (default: false)
 *  - animated: bool (default: false)
 *  - class: ?string
 *
 * @package StackFlow\Widgets\Feedback
 */
class ProgressBar extends Widget
{
    public function render(): string
    {
        $value = max(0, min(100, (float) $this->prop('value', 0)));
        $variant = (string) $this->prop('variant', 'primary');
        $size = (string) $this->prop('size', 'md');
        $showLabel = (bool) $this->prop('label', false);
        $labelPosition = (string) $this->prop('labelPosition', 'top');
        $striped = (bool) $this->prop('striped', false);
        $animated = (bool) $this->prop('animated', false);
        $extraClass = $this->prop('class', '');

        $heightClass = match ($size) {
            'sm' => 'h-1.5',
            'lg' => 'h-5',
            default => 'h-2.5',
        };

        $barColor = match ($variant) {
            'success' => 'bg-emerald-500',
            'warning' => 'bg-amber-500',
            'danger' => 'bg-rose-500',
            'gradient' => 'bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500',
            default => 'bg-indigo-600',
        };

        $stripedClass = $striped ? 'bg-[length:1rem_1rem] bg-[linear-gradient(45deg,rgba(255,255,255,0.15)_25%,transparent_25%,transparent_50%,rgba(255,255,255,0.15)_50%,rgba(255,255,255,0.15)_75%,transparent_75%,transparent)]' : '';
        $animClass = $animated ? 'animate-[progress-stripes_1s_linear_infinite]' : '';

        $topLabel = ($showLabel && $labelPosition === 'top')
            ? "<div class=\"flex justify-between mb-1.5 text-xs font-semibold text-gray-700 dark:text-gray-300\"><span>Progress</span><span>{$value}%</span></div>"
            : '';

        $insideLabel = ($showLabel && $labelPosition === 'inside' && $size === 'lg' && $value > 10)
            ? "<span class=\"text-[11px] font-bold text-white px-2 leading-none flex items-center justify-end h-full\">{$value}%</span>"
            : '';

        return <<<HTML
        <div class="w-full {$extraClass}">
            {$topLabel}
            <div class="w-full bg-gray-200 dark:bg-gray-800 rounded-full {$heightClass} overflow-hidden shadow-inner">
                <div class="{$barColor} {$heightClass} rounded-full transition-all duration-500 {$stripedClass} {$animClass}" style="width: {$value}%">
                    {$insideLabel}
                </div>
            </div>
        </div>
        HTML;
    }
}
