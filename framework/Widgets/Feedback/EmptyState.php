<?php

declare(strict_types=1);

namespace StackFlow\Widgets\Feedback;

use StackFlow\Widgets\Widget;

/**
 * EmptyState Widget
 *
 * Renders a clean placeholder illustration with icon, title, description,
 * and call-to-action button when data is empty.
 *
 * Props:
 *  - icon: ?string (SVG markup or Widget)
 *  - title: string
 *  - description: ?string
 *  - action: ?Widget (typically a Button)
 *  - class: ?string
 *
 * @package StackFlow\Widgets\Feedback
 */
class EmptyState extends Widget
{
    public function render(): string
    {
        $icon = $this->prop('icon');
        $title = (string) $this->prop('title', 'No data found');
        $description = $this->prop('description');
        $action = $this->prop('action');
        $extraClass = $this->prop('class', '');

        $classes = $this->classes('text-center py-12 px-4 flex flex-col items-center justify-center max-w-md mx-auto', $extraClass);

        $iconHtml = '';
        if ($icon !== null) {
            $iconHtml = "<div class=\"w-12 h-12 rounded-sm bg-gray-100 dark:bg-gray-800 text-gray-400 dark:text-gray-500 flex items-center justify-center mb-4\">{$icon}</div>";
        } else {
            $iconHtml = <<<HTML
            <div class="w-12 h-12 rounded-sm bg-gray-100 dark:bg-gray-800 text-gray-400 dark:text-gray-500 flex items-center justify-center mb-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
            </div>
            HTML;
        }

        $titleHtml = "<h3 class=\"text-base font-bold text-gray-900 dark:text-white mb-1\">{$this->e($title)}</h3>";

        $descHtml = $description !== null
            ? "<p class=\"text-sm text-gray-500 dark:text-gray-400 mb-6 max-w-sm\">{$this->e((string)$description)}</p>"
            : '';

        $actionHtml = '';
        if ($action !== null) {
            $actionHtml = "<div class=\"mt-4\">{$this->renderChild($action)}</div>";
        }

        return <<<HTML
        <div class="{$classes}">
            {$iconHtml}
            {$titleHtml}
            {$descHtml}
            {$actionHtml}
        </div>
        HTML;
    }
}
