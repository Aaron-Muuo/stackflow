<?php

declare(strict_types=1);

namespace StackFlow\Widgets\Feedback;

use StackFlow\Widgets\Widget;

/**
 * Alert Widget
 *
 * Props:
 *  - message: string
 *  - title: ?string
 *  - variant: 'info'|'success'|'warning'|'danger' (default: 'info')
 *  - dismissible: bool (default: false)
 *  - icon: bool (default: true)
 *  - class: ?string
 *
 * @package StackFlow\Widgets\Feedback
 */
class Alert extends Widget
{
    public function render(): string
    {
        $message = (string) $this->prop('message', '');
        $title = $this->prop('title');
        $variant = $this->prop('variant', 'info');
        $dismissible = (bool) $this->prop('dismissible', false);
        $showIcon = (bool) $this->prop('icon', true);
        $extraClass = $this->prop('class', '');
        $id = 'sf-alert-' . uniqid();

        $variantClasses = match ($variant) {
            'success' => 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
            'warning' => 'bg-amber-50 dark:bg-amber-950/40 text-amber-800 dark:text-amber-300 border-amber-200 dark:border-amber-800',
            'danger' => 'bg-rose-50 dark:bg-rose-950/40 text-rose-800 dark:text-rose-300 border-rose-200 dark:border-rose-800',
            default => 'bg-indigo-50 dark:bg-indigo-950/40 text-indigo-800 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800',
        };

        $iconSvg = match ($variant) {
            'success' => '<svg class="w-5 h-5 shrink-0 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>',
            'warning' => '<svg class="w-5 h-5 shrink-0 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>',
            'danger' => '<svg class="w-5 h-5 shrink-0 text-rose-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>',
            default => '<svg class="w-5 h-5 shrink-0 text-indigo-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>',
        };

        $classes = $this->classes('p-4 rounded-sm border shadow-none flex items-start gap-3 transition-all', $variantClasses, $extraClass);

        $iconMarkup = $showIcon ? $iconSvg : '';
        $titleMarkup = $title !== null ? "<h4 class=\"font-semibold text-sm mb-0.5\">{$this->e($title)}</h4>" : '';
        $body = "<div>{$titleMarkup}<p class=\"text-sm\">{$this->e($message)}</p></div>";

        $dismissMarkup = $dismissible
            ? "<button type=\"button\" onclick=\"document.getElementById('{$id}').remove()\" class=\"ml-auto -mx-1.5 -my-1.5 p-1.5 rounded-sm opacity-70 hover:opacity-100 focus:outline-none\"><span class=\"sr-only\">Dismiss</span><svg class=\"w-4 h-4\" fill=\"currentColor\" viewBox=\"0 0 20 20\"><path fill-rule=\"evenodd\" d=\"M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z\" clip-rule=\"evenodd\"/></svg></button>"
            : '';

        return "<div id=\"{$id}\" class=\"{$classes}\" role=\"alert\">{$iconMarkup}{$body}{$dismissMarkup}</div>";
    }
}
