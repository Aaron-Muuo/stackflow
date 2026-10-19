<?php

declare(strict_types=1);

namespace StackFlow\Widgets\Navigation;

use StackFlow\Widgets\Widget;

/**
 * Breadcrumb Navigation Widget
 *
 * Props:
 *  - items: array of ['label' => string, 'href' => ?string]
 *  - separator: string (default: '/')
 *  - class: ?string
 *
 * @package StackFlow\Widgets\Navigation
 */
class Breadcrumb extends Widget
{
    public function render(): string
    {
        $items = (array) $this->prop('items', []);
        $separator = $this->prop('separator', '/');
        $extraClass = $this->prop('class', '');

        $classes = $this->classes('flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400', $extraClass);

        $html = "<nav class=\"{$classes}\" aria-label=\"Breadcrumb\"><ol class=\"flex items-center space-x-2\">";

        $count = count($items);
        foreach ($items as $idx => $item) {
            $label = $this->e($item['label'] ?? '');
            $href = $item['href'] ?? null;
            $isLast = ($idx === $count - 1);

            $html .= '<li class="flex items-center">';
            if ($idx > 0) {
                $html .= "<span class=\"mx-2 text-gray-400 select-none\">{$this->e($separator)}</span>";
            }

            if ($href && !$isLast) {
                $html .= "<a href=\"{$this->e($href)}\" class=\"hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors\">{$label}</a>";
            } else {
                $html .= "<span class=\"font-medium text-gray-900 dark:text-white\">{$label}</span>";
            }
            $html .= '</li>';
        }

        $html .= '</ol></nav>';

        return $html;
    }
}
