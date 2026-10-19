<?php

declare(strict_types=1);

namespace StackFlow\Widgets\Navigation;

use StackFlow\Widgets\Widget;

/**
 * Tabs Widget
 *
 * Renders an accessible, interactive tab layout with client-side tab switching,
 * supporting horizontal or vertical orientation, badge pills, and multiple style variants.
 *
 * Props:
 *  - tabs: array of [
 *      'id' => string,
 *      'label' => string,
 *      'icon' => ?string,
 *      'badge' => ?string|int,
 *      'content' => Widget|string,
 *      'active' => bool
 *    ]
 *  - variant: 'underline'|'pills'|'segmented' (default: 'underline')
 *  - orientation: 'horizontal'|'vertical' (default: 'horizontal')
 *  - class: ?string
 *
 * @package StackFlow\Widgets\Navigation
 */
class Tabs extends Widget
{
    public function render(): string
    {
        $tabs = (array) $this->prop('tabs', []);
        $variant = (string) $this->prop('variant', 'underline');
        $orientation = (string) $this->prop('orientation', 'horizontal');
        $extraClass = $this->prop('class', '');
        $tabsId = 'sf-tabs-' . uniqid();

        if (empty($tabs)) {
            return '';
        }

        $headersHtml = '';
        $panelsHtml = '';
        $isVertical = $orientation === 'vertical';

        foreach ($tabs as $index => $tab) {
            $id = $tab['id'] ?? "tab-{$index}";
            $label = $this->e($tab['label'] ?? "Tab {$index}");
            $icon = $tab['icon'] ?? '';
            $badge = isset($tab['badge']) ? (string)$tab['badge'] : null;
            $content = $this->renderChild($tab['content'] ?? '');
            $isActive = !empty($tab['active']) || $index === 0;

            $activeHeaderClass = match ($variant) {
                'pills' => ($isActive ? 'bg-indigo-600 text-white shadow-none' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-800'),
                'segmented' => ($isActive ? 'bg-white dark:bg-gray-800 text-gray-950 dark:text-white shadow-none border border-gray-200 dark:border-gray-700 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'),
                default => ($isActive ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'),
            };

            $baseHeaderClass = match ($variant) {
                'pills' => 'px-3 py-1.5 rounded-sm text-sm font-medium transition-colors cursor-pointer flex items-center gap-2 select-none',
                'segmented' => 'px-3 py-1 rounded-sm text-sm font-medium transition-all cursor-pointer flex items-center gap-2 select-none',
                default => $isVertical
                    ? 'py-3 px-4 border-l-2 font-medium text-sm transition-colors cursor-pointer flex items-center justify-between select-none'
                    : 'py-3 px-4 border-b-2 font-medium text-sm transition-colors cursor-pointer flex items-center gap-2 -mb-px select-none',
            };

            $iconHtml = $icon ? "<span class=\"shrink-0\">{$icon}</span>" : '';
            $badgeHtml = $badge !== null
                ? "<span class=\"ml-auto inline-block py-0.5 px-2 text-xs rounded-full bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 font-semibold\">{$this->e($badge)}</span>"
                : '';

            $headersHtml .= <<<HTML
            <button type="button" role="tab" onclick="sfSwitchTab('{$tabsId}', '{$id}')" id="btn-{$tabsId}-{$id}" class="sf-tab-btn {$baseHeaderClass} {$activeHeaderClass}" data-tab-id="{$id}" data-variant="{$variant}">
                {$iconHtml}<span>{$label}</span>{$badgeHtml}
            </button>
            HTML;

            $panelDisplay = $isActive ? '' : 'style="display:none;"';
            $panelsHtml .= <<<HTML
            <div role="tabpanel" id="panel-{$tabsId}-{$id}" class="sf-tab-panel" {$panelDisplay}>
                {$content}
            </div>
            HTML;
        }

        if ($isVertical) {
            return <<<HTML
            <div id="{$tabsId}" class="flex flex-col md:flex-row gap-6 w-full {$extraClass}">
                <div class="flex flex-col space-y-1 w-full md:w-64 shrink-0 border-r border-gray-100 dark:border-gray-800 pr-4">
                    {$headersHtml}
                </div>
                <div class="flex-1 min-w-0">
                    {$panelsHtml}
                </div>
                <script>
                    window.sfInitTabSwitch = true;
                </script>
            </div>
            HTML;
        }

        $navContainerClass = match ($variant) {
            'pills' => 'flex flex-wrap gap-2',
            'segmented' => 'inline-flex p-0.5 bg-gray-100 dark:bg-gray-900 rounded-sm space-x-1 border border-gray-200/80 dark:border-gray-800',
            default => 'flex border-b border-gray-200 dark:border-gray-800 space-x-1 overflow-x-auto',
        };

        return <<<HTML
        <div id="{$tabsId}" class="w-full {$extraClass}">
            <div class="{$navContainerClass}">
                {$headersHtml}
            </div>
            <div class="mt-4">
                {$panelsHtml}
            </div>
        </div>
        HTML;
    }
}
