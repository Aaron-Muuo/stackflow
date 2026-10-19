<?php

declare(strict_types=1);

namespace StackFlow\Widgets\Display;

use StackFlow\Widgets\Widget;

/**
 * Link Widget
 *
 * Renders an accessible HTML <a> anchor tag with Tailwind styling and variants.
 *
 * Props:
 *  - label: string (link text)
 *  - href: string (URL, default: '#')
 *  - target: ?string ('_blank'|'_self'|'_parent'|'_top')
 *  - rel: ?string (auto-sets 'noopener noreferrer' when target is _blank)
 *  - variant: 'default'|'primary'|'subtle'|'button' (default: 'default')
 *  - icon: ?string (HTML icon markup or Widget)
 *  - child: ?Widget|string
 *  - class: ?string
 *  - id: ?string
 *
 * @package StackFlow\Widgets\Display
 */
class Link extends Widget
{
    public function render(): string
    {
        $label = (string) $this->prop('label', '');
        $href = (string) $this->prop('href', '#');
        $target = $this->prop('target');
        $rel = $this->prop('rel');
        $variant = (string) $this->prop('variant', 'default');
        $icon = $this->prop('icon');
        $child = $this->prop('child');
        $extraClass = $this->prop('class', '');
        $id = $this->prop('id');

        if ($target === '_blank' && $rel === null) {
            $rel = 'noopener noreferrer';
        }

        $variantClasses = match ($variant) {
            'primary' => 'text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300 font-medium underline underline-offset-4',
            'subtle' => 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition-colors',
            'button' => 'inline-flex items-center justify-center px-4 py-2 text-sm font-medium rounded-sm bg-indigo-600 hover:bg-indigo-700 text-white transition-colors shadow-none',
            default => 'text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 transition-colors',
        };

        $classes = $this->classes('inline-flex items-center gap-1.5 cursor-pointer', $variantClasses, $extraClass);

        $attrs = $this->attr('href', $href)
            . $this->attr('target', $target)
            . $this->attr('rel', $rel)
            . $this->attr('id', $id);

        $iconHtml = $icon !== null ? "<span class=\"shrink-0\">{$icon}</span>" : '';
        $bodyHtml = $child !== null ? $this->renderChild($child) : "<span>{$this->e($label)}</span>";

        return "<a{$attrs} class=\"{$classes}\">{$iconHtml}{$bodyHtml}</a>";
    }
}
