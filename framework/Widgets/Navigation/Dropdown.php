<?php

declare(strict_types=1);

namespace StackFlow\Widgets\Navigation;

use StackFlow\Widgets\Widget;

/**
 * Dropdown Widget
 *
 * Renders an accessible, click-toggle dropdown menu with Tailwind styling.
 *
 * Props:
 *  - trigger: Widget|string (button or label that triggers dropdown)
 *  - items: array of [
 *      'label' => string,
 *      'href' => ?string,
 *      'icon' => ?string,
 *      'divider' => bool,
 *      'danger' => bool,
 *      'onClick' => ?string
 *    ]
 *  - align: 'left'|'right' (default: 'right')
 *  - class: ?string
 *
 * @package StackFlow\Widgets\Navigation
 */
class Dropdown extends Widget
{
    public function render(): string
    {
        $trigger = $this->prop('trigger', 'Options');
        $items = (array) $this->prop('items', []);
        $align = (string) $this->prop('align', 'right');
        $extraClass = $this->prop('class', '');
        $dropdownId = 'sf-dd-' . uniqid();

        $triggerHtml = $this->renderChild($trigger);

        $itemsHtml = '';
        foreach ($items as $item) {
            if (!empty($item['divider'])) {
                $itemsHtml .= '<div class="my-1 border-t border-gray-100 dark:border-gray-800"></div>';
                continue;
            }

            $label = $this->e($item['label'] ?? '');
            $href = $item['href'] ?? '#';
            $icon = $item['icon'] ?? '';
            $isDanger = !empty($item['danger']);
            $onClick = $item['onClick'] ?? '';

            $colorClass = $isDanger
                ? 'text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-950/40'
                : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800';

            $iconMarkup = $icon ? "<span class=\"mr-2.5 shrink-0\">{$icon}</span>" : '';
            $onclickAttr = $onClick ? " onclick=\"{$this->e($onClick)}\"" : '';

            $itemsHtml .= <<<HTML
            <a href="{$this->e($href)}" class="flex items-center px-4 py-2 text-sm transition-colors rounded-sm {$colorClass}"{$onclickAttr}>
                {$iconMarkup}<span>{$label}</span>
            </a>
            HTML;
        }

        $alignClass = $align === 'left' ? 'left-0' : 'right-0';

        return <<<HTML
        <div class="relative inline-block text-left {$extraClass}">
            <div onclick="document.getElementById('{$dropdownId}').classList.toggle('hidden'); event.stopPropagation();" class="cursor-pointer">
                {$triggerHtml}
            </div>
            <div id="{$dropdownId}" class="hidden absolute {$alignClass} mt-1 w-56 rounded-sm shadow-none bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 p-1 z-50 transition-all">
                {$itemsHtml}
            </div>
            <script>
                if (!window.sfDropdownAttached) {
                    window.sfDropdownAttached = true;
                    document.addEventListener('click', function() {
                        document.querySelectorAll('[id^="sf-dd-"]').forEach(function(el) {
                            el.classList.add('hidden');
                        });
                    });
                }
            </script>
        </div>
        HTML;
    }
}
