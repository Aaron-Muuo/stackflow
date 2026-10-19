<?php

declare(strict_types=1);

namespace StackFlow\Widgets\Navigation;

use StackFlow\Widgets\Widget;

/**
 * Sidebar Navigation Widget
 *
 * Props:
 *  - items: array of ['label' => string, 'href' => string, 'icon' => ?string, 'active' => bool, 'badge' => ?string]
 *  - header: Widget|string|null
 *  - footer: Widget|string|null
 *  - class: ?string
 *
 * @package StackFlow\Widgets\Navigation
 */
class Sidebar extends Widget
{
    public function render(): string
    {
        $items = (array) $this->prop('items', []);
        $header = $this->prop('header');
        $footer = $this->prop('footer');
        $extraClass = $this->prop('class', '');

        $classes = $this->classes('flex flex-col w-64 h-screen border-r border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900', $extraClass);

        $headerHtml = '';
        if ($header !== null) {
            $rendered = $this->renderChild($header);
            $headerHtml = "<div class=\"p-4 border-b border-gray-100 dark:border-gray-800\">{$rendered}</div>";
        }

        $itemsHtml = '';
        foreach ($items as $item) {
            $label = $this->e($item['label'] ?? '');
            $href = $this->e($item['href'] ?? '#');
            $icon = $item['icon'] ?? '';
            $active = !empty($item['active']);
            $badge = $item['badge'] ?? null;

            $itemClasses = $active
                ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 font-medium'
                : 'text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-white';

            $iconMarkup = $icon ? "<span class=\"mr-3 shrink-0\">{$icon}</span>" : '';
            $badgeMarkup = $badge ? "<span class=\"ml-auto inline-block py-0.5 px-2 text-xs font-semibold rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300\">{$this->e($badge)}</span>" : '';

            $itemsHtml .= "<a href=\"{$href}\" class=\"flex items-center px-3 py-2 text-sm rounded-sm transition-colors {$itemClasses}\">{$iconMarkup}<span>{$label}</span>{$badgeMarkup}</a>";
        }

        $footerHtml = '';
        if ($footer !== null) {
            $rendered = $this->renderChild($footer);
            $footerHtml = "<div class=\"p-4 border-t border-gray-100 dark:border-gray-800 mt-auto\">{$rendered}</div>";
        }

        return <<<HTML
        <aside class="{$classes}">
            {$headerHtml}
            <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                {$itemsHtml}
            </nav>
            {$footerHtml}
        </aside>
        HTML;
    }
}
