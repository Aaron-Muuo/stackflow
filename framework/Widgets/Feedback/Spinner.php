<?php

declare(strict_types=1);

namespace StackFlow\Widgets\Feedback;

use StackFlow\Widgets\Widget;

/**
 * Spinner Loading Widget
 *
 * Props:
 *  - size: 'sm'|'md'|'lg'|'xl' (default: 'md')
 *  - color: string (Tailwind color, default: 'text-indigo-600')
 *  - class: ?string
 *
 * @package StackFlow\Widgets\Feedback
 */
class Spinner extends Widget
{
    public function render(): string
    {
        $size = $this->prop('size', 'md');
        $color = $this->prop('color', 'text-indigo-600 dark:text-indigo-400');
        $extraClass = $this->prop('class', '');

        $sizeClass = match ($size) {
            'sm' => 'h-4 w-4',
            'lg' => 'h-8 w-8',
            'xl' => 'h-12 w-12',
            default => 'h-6 w-6',
        };

        $classes = $this->classes('animate-spin', $sizeClass, $color, $extraClass);

        return <<<HTML
        <svg class="{$classes}" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        HTML;
    }
}
