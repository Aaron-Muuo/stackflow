<?php

declare(strict_types=1);

namespace StackFlow\Widgets\Container;

use StackFlow\Widgets\Widget;

/**
 * Card Widget
 *
 * Props:
 *  - child: Widget|string|null
 *  - children: array
 *  - title: ?string
 *  - subtitle: ?string
 *  - footer: Widget|string|null
 *  - shadow: 'none'|'sm'|'md'|'lg'|'xl' (default: 'none')
 *  - rounded: 'none'|'sm'|'md'|'lg'|'xl'|'2xl' (default: 'sm')
 *  - padding: string (default: 'p-6')
 *  - class: ?string
 *  - href: ?string (makes whole card a clickable link)
 *
 * @package StackFlow\Widgets\Container
 */
class Card extends Widget
{
    public function render(): string
    {
        $title = $this->prop('title');
        $subtitle = $this->prop('subtitle');
        $footer = $this->prop('footer');
        $shadow = $this->prop('shadow', 'none');
        $rounded = $this->prop('rounded', 'sm');
        $padding = $this->prop('padding', 'p-6');
        $extraClass = $this->prop('class', '');
        $href = $this->prop('href');

        $shadowClass = match ($shadow) {
            'sm' => 'shadow-sm',
            'md' => 'shadow-md',
            'lg' => 'shadow-lg',
            'xl' => 'shadow-xl',
            default => 'shadow-none',
        };

        $roundedClass = match ($rounded) {
            'none' => 'rounded-none',
            'md' => 'rounded-md',
            'lg' => 'rounded-lg',
            'xl' => 'rounded-xl',
            '2xl' => 'rounded-2xl',
            default => 'rounded-sm',
        };

        $baseClasses = "bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700/60 {$shadowClass} {$roundedClass} overflow-hidden transition-all";
        if ($href) {
            $baseClasses .= ' hover:border-gray-300 dark:hover:border-gray-600 hover:-translate-y-0.5 cursor-pointer block';
        }

        $classes = $this->classes($baseClasses, $extraClass);

        $headerHtml = '';
        if ($title !== null || $subtitle !== null) {
            $titleMarkup = $title !== null ? "<h3 class=\"text-lg font-semibold text-gray-900 dark:text-white\">{$this->e($title)}</h3>" : '';
            $subMarkup = $subtitle !== null ? "<p class=\"text-sm text-gray-500 dark:text-gray-400 mt-0.5\">{$this->e($subtitle)}</p>" : '';
            $headerHtml = "<div class=\"border-b border-gray-100 dark:border-gray-700/60 px-6 py-4\">{$titleMarkup}{$subMarkup}</div>";
        }

        $bodyContent = '';
        if ($this->prop('child') !== null) {
            $bodyContent = $this->renderChild($this->prop('child'));
        } elseif (!empty($this->prop('children'))) {
            $bodyContent = $this->renderChildren((array) $this->prop('children'));
        }

        $bodyHtml = "<div class=\"{$padding}\">{$bodyContent}</div>";

        $footerHtml = '';
        if ($footer !== null) {
            $renderedFooter = $this->renderChild($footer);
            $footerHtml = "<div class=\"bg-gray-50 dark:bg-gray-800/50 border-t border-gray-100 dark:border-gray-700/60 px-6 py-3.5\">{$renderedFooter}</div>";
        }

        $content = "{$headerHtml}{$bodyHtml}{$footerHtml}";

        if ($href) {
            return "<a href=\"{$this->e($href)}\" class=\"{$classes}\">{$content}</a>";
        }

        return "<div class=\"{$classes}\">{$content}</div>";
    }
}
