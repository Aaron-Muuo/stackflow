<?php

declare(strict_types=1);

namespace StackFlow\Widgets\Feedback;

use StackFlow\Widgets\Widget;

/**
 * Skeleton Loader Widget
 *
 * Renders animated shimmer placeholders for loading states.
 *
 * Props:
 *  - variant: 'text'|'circular'|'rectangular' (default: 'text')
 *  - width: ?string (Tailwind width e.g. 'w-full', 'w-32')
 *  - height: ?string (Tailwind height e.g. 'h-4', 'h-12')
 *  - lines: int (default: 1)
 *  - class: ?string
 *
 * @package StackFlow\Widgets\Feedback
 */
class Skeleton extends Widget
{
    public function render(): string
    {
        $variant = (string) $this->prop('variant', 'text');
        $width = (string) $this->prop('width', 'w-full');
        $height = $this->prop('height');
        $lines = (int) $this->prop('lines', 1);
        $extraClass = $this->prop('class', '');

        $defaultHeight = match ($variant) {
            'circular' => 'h-12 w-12',
            'rectangular' => 'h-32',
            default => 'h-4',
        };

        $heightClass = $height ?? $defaultHeight;

        $roundedClass = match ($variant) {
            'circular' => 'rounded-full',
            'rectangular' => 'rounded-sm',
            default => 'rounded-sm',
        };

        $shimmer = 'animate-pulse bg-gray-200 dark:bg-gray-800';
        $classes = $this->classes($shimmer, $width, $heightClass, $roundedClass, $extraClass);

        if ($lines <= 1) {
            return "<div class=\"{$classes}\"></div>";
        }

        $items = '';
        for ($i = 0; $i < $lines; $i++) {
            $w = ($i === $lines - 1) ? 'w-2/3' : $width;
            $lineClasses = $this->classes($shimmer, $w, $heightClass, $roundedClass, $extraClass);
            $items .= "<div class=\"{$lineClasses}\"></div>\n";
        }

        return "<div class=\"space-y-2.5 w-full\">{$items}</div>";
    }
}
