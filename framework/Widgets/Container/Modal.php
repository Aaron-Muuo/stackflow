<?php

declare(strict_types=1);

namespace StackFlow\Widgets\Container;

use StackFlow\Widgets\Widget;

/**
 * Modal Widget
 *
 * Props:
 *  - id: string (modal identifier used by trigger and dismiss JS)
 *  - title: ?string
 *  - child: Widget|string|null
 *  - children: array
 *  - footer: Widget|string|null
 *  - size: 'sm'|'md'|'lg'|'xl' (default: 'md')
 *  - class: ?string
 *
 * @package StackFlow\Widgets\Container
 */
class Modal extends Widget
{
    public function render(): string
    {
        $id = (string) $this->prop('id', 'sf-modal-' . uniqid());
        $title = $this->prop('title');
        $footer = $this->prop('footer');
        $size = $this->prop('size', 'md');
        $extraClass = $this->prop('class', '');

        $maxWidthClass = match ($size) {
            'sm' => 'max-w-sm',
            'lg' => 'max-w-2xl',
            'xl' => 'max-w-4xl',
            default => 'max-w-lg',
        };

        $bodyContent = '';
        if ($this->prop('child') !== null) {
            $bodyContent = $this->renderChild($this->prop('child'));
        } elseif (!empty($this->prop('children'))) {
            $bodyContent = $this->renderChildren((array) $this->prop('children'));
        }

        $headerHtml = '';
        if ($title !== null) {
            $headerHtml = <<<HTML
            <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700/60 px-6 py-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{$this->e($title)}</h3>
                <button type="button" onclick="document.getElementById('{$id}').classList.add('hidden')" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                    <span class="sr-only">Close</span>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            HTML;
        }

        $footerHtml = '';
        if ($footer !== null) {
            $renderedFooter = $this->renderChild($footer);
            $footerHtml = "<div class=\"bg-gray-50 dark:bg-gray-800/50 border-t border-gray-100 dark:border-gray-700/60 px-6 py-3.5 flex justify-end gap-3\">{$renderedFooter}</div>";
        }

        return <<<HTML
        <div id="{$id}" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-900/60 transition-opacity backdrop-blur-xs" onclick="document.getElementById('{$id}').classList.add('hidden')"></div>
                <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>
                <div class="inline-block transform overflow-hidden rounded-sm bg-white dark:bg-gray-800 text-left align-bottom shadow-none border border-gray-200 dark:border-gray-700 transition-all sm:my-8 sm:w-full {$maxWidthClass} sm:align-middle {$extraClass}">
                    {$headerHtml}
                    <div class="p-6">
                        {$bodyContent}
                    </div>
                    {$footerHtml}
                </div>
            </div>
        </div>
        HTML;
    }
}
