<?php

declare(strict_types=1);

namespace StackFlow\Widgets\Navigation;

use StackFlow\Widgets\Widget;

/**
 * Responsive Navbar Widget
 *
 * Props:
 *  - brand: string|Widget
 *  - links: array of ['label' => string, 'href' => string, 'active' => bool]
 *  - actions: Widget|string|array (right-aligned widgets/buttons)
 *  - sticky: bool (default: false)
 *  - class: ?string
 *
 * @package StackFlow\Widgets\Navigation
 */
class Navbar extends Widget
{
    public function render(): string
    {
        $brand = $this->prop('brand', 'StackFlow');
        $links = (array) $this->prop('links', []);
        $actions = $this->prop('actions');
        $sticky = (bool) $this->prop('sticky', false);
        $extraClass = $this->prop('class', '');

        $navId = 'sf-nav-' . uniqid();

        $baseClass = 'w-full bg-white/90 dark:bg-gray-900/90 backdrop-blur-md border-b border-gray-200 dark:border-gray-800 transition-colors z-40';
        if ($sticky) {
            $baseClass .= ' sticky top-0';
        }

        $classes = $this->classes($baseClass, $extraClass);

        $brandHtml = $brand instanceof Widget
            ? $brand->render()
            : "<a href=\"/\" class=\"text-xl font-bold tracking-tight text-gray-900 dark:text-white flex items-center gap-2\"><span class=\"text-indigo-600 dark:text-indigo-400\">&lt;/&gt;</span>{$this->e((string)$brand)}</a>";

        $linksHtml = '';
        foreach ($links as $link) {
            $label = $this->e($link['label'] ?? '');
            $href = $this->e($link['href'] ?? '#');
            $active = !empty($link['active']);

            $linkClass = $active
                ? 'text-indigo-600 dark:text-indigo-400 font-semibold'
                : 'text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white transition-colors';

            $linksHtml .= "<a href=\"{$href}\" class=\"px-3 py-2 text-sm rounded-sm {$linkClass}\">{$label}</a>";
        }

        $actionsHtml = '';
        if ($actions !== null) {
            if (is_array($actions)) {
                $actionsHtml = $this->renderChildren($actions);
            } else {
                $actionsHtml = $this->renderChild($actions);
            }
        }

        return <<<HTML
        <nav class="{$classes}">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <div class="flex items-center gap-8">
                        <div class="shrink-0">
                            {$brandHtml}
                        </div>
                        <div class="hidden md:flex items-center space-x-1">
                            {$linksHtml}
                        </div>
                    </div>
                    <div class="hidden md:flex items-center gap-3">
                        {$actionsHtml}
                    </div>
                    <div class="md:hidden flex items-center gap-2">
                        <button type="button" onclick="document.getElementById('{$navId}').classList.toggle('hidden')" class="p-2 rounded-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none">
                            <span class="sr-only">Toggle Navigation</span>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                        </button>
                    </div>
                </div>
            </div>
            <div id="{$navId}" class="hidden md:hidden px-4 pt-2 pb-4 space-y-1 border-t border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900">
                {$linksHtml}
                <div class="pt-3 border-t border-gray-100 dark:border-gray-800 flex items-center gap-2">
                    {$actionsHtml}
                </div>
            </div>
        </nav>
        HTML;
    }
}
