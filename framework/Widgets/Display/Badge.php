<?php

declare(strict_types=1);

namespace StackFlow\Widgets\Display;

use StackFlow\Widgets\Widget;

/**
 * Badge — Pill-shaped label badge widget.
 *
 * Renders a small inline badge with semantic color variants.
 *
 * Props:
 *   - label    (string)  Badge text (required)
 *   - variant  (string)  'primary'|'success'|'warning'|'danger'|'info' (default 'primary')
 *   - class    (string)  Extra CSS classes
 *
 * @package StackFlow\Widgets\Display
 */
class Badge extends Widget
{
    /**
     * Tailwind class sets for each variant.
     * Includes dark mode support.
     */
    private const VARIANT_CLASSES = [
        'primary' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200',
        'success' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        'warning' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        'danger'  => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        'info'    => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    ];

    public function render(): string
    {
        $label   = $this->e((string) $this->prop('label', ''));
        $variant = (string) $this->prop('variant', 'primary');
        $extra   = (string) $this->prop('class', '');
        $rounded = (string) $this->prop('rounded', 'sm');
        $roundedClass = match ($rounded) {
            'full' => 'rounded-full',
            'none' => 'rounded-none',
            'md' => 'rounded-md',
            'lg' => 'rounded-lg',
            default => 'rounded-sm',
        };

        $variantClasses = self::VARIANT_CLASSES[$variant] ?? self::VARIANT_CLASSES['primary'];
        $cssClasses     = $this->classes(
            'inline-flex',
            'items-center',
            'px-2',
            'py-0.5',
            $roundedClass,
            'text-xs',
            'font-medium',
            $variantClasses,
            $extra,
        );

        return "<span class=\"{$cssClasses}\">{$label}</span>";
    }
}
