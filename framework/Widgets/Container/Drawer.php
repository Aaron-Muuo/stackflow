<?php

declare(strict_types=1);

namespace StackFlow\Widgets\Container;

use StackFlow\Widgets\Widget;

/**
 * Drawer Widget
 *
 * Renders a slide-out drawer panel (offcanvas) for mobile navigation, sidebars, or filters.
 *
 * Props:
 *  - id: string (required unique id)
 *  - title: ?string
 *  - position: 'left'|'right' (default: 'left')
 *  - child: Widget|string|null
 *  - children: array of Widget|string
 *  - footer: Widget|string|null
 *  - class: ?string
 *
 * @package StackFlow\Widgets\Container
 */
class Drawer extends Widget
{
    public function render(): string
    {
        $id = (string) $this->prop('id', 'drawer-' . uniqid());
        $title = $this->prop('title');
        $position = (string) $this->prop('position', 'left');
        $child = $this->prop('child');
        $children = (array) $this->prop('children', []);
        $footer = $this->prop('footer');
        $extraClass = $this->prop('class', '');

        $bodyContent = '';
        if ($child !== null) {
            $bodyContent .= $this->renderChild($child);
        }
        if (!empty($children)) {
            $bodyContent .= $this->renderChildren($children);
        }

        $footerHtml = '';
        if ($footer !== null) {
            $rendered = $this->renderChild($footer);
            $footerHtml = "<div class=\"p-4 border-t border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-850 mt-auto\">{$rendered}</div>";
        }

        $positionClasses = $position === 'right'
            ? 'right-0 border-l'
            : 'left-0 border-r';

        return <<<HTML
        <div id="{$id}" class="fixed inset-0 z-50 overflow-hidden hidden" aria-labelledby="slide-over-title" role="dialog" aria-modal="true">
            <!-- Background Backdrop -->
            <div onclick="document.getElementById('{$id}').classList.add('hidden')" class="fixed inset-0 bg-black/40 backdrop-blur-sm transition-opacity"></div>

            <div class="fixed inset-y-0 max-w-full flex {$positionClasses}">
                <div class="w-screen max-w-md bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-800 shadow-none flex flex-col {$extraClass}">
                    <!-- Drawer Header -->
                    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                        <h2 class="text-base font-bold text-gray-900 dark:text-white" id="slide-over-title">
                            {$this->e((string)$title)}
                        </h2>
                        <button type="button" onclick="document.getElementById('{$id}').classList.add('hidden')" class="p-1 rounded-sm text-gray-400 hover:text-gray-600 dark:hover:text-white transition-colors">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- Drawer Content -->
                    <div class="flex-1 p-6 overflow-y-auto">
                        {$bodyContent}
                    </div>

                    <!-- Drawer Footer -->
                    {$footerHtml}
                </div>
            </div>
        </div>
        HTML;
    }
}
