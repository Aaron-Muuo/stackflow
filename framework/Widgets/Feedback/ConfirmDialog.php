<?php

declare(strict_types=1);

namespace StackFlow\Widgets\Feedback;

use StackFlow\Core\Session;
use StackFlow\Widgets\Widget;

/**
 * ConfirmDialog Widget
 *
 * Renders an accessible confirmation dialog box for destructive or sensitive operations
 * (e.g. deleting items, confirming orders, discarding changes).
 *
 * Props:
 *  - id: string (modal id)
 *  - title: string (dialog title, default: 'Are you sure?')
 *  - message: string (detailed explanation text)
 *  - confirmText: string (default: 'Confirm')
 *  - cancelText: string (default: 'Cancel')
 *  - variant: 'danger'|'warning'|'primary' (default: 'danger')
 *  - action: ?string (URL to submit to when confirmed)
 *  - method: 'POST'|'DELETE'|'PUT' (default: 'POST')
 *  - onConfirm: ?string (JavaScript handler when confirmed)
 *  - class: ?string
 *
 * @package StackFlow\Widgets\Feedback
 */
class ConfirmDialog extends Widget
{
    public function render(): string
    {
        $id = (string) $this->prop('id', 'confirm-' . uniqid());
        $title = (string) $this->prop('title', 'Are you sure?');
        $message = (string) $this->prop('message', 'This action cannot be undone.');
        $confirmText = (string) $this->prop('confirmText', 'Confirm');
        $cancelText = (string) $this->prop('cancelText', 'Cancel');
        $variant = (string) $this->prop('variant', 'danger');
        $action = $this->prop('action');
        $method = strtoupper((string) $this->prop('method', 'POST'));
        $onConfirm = $this->prop('onConfirm');
        $extraClass = $this->prop('class', '');

        $confirmBtnColor = match ($variant) {
            'warning' => 'bg-amber-600 hover:bg-amber-700 text-white focus:ring-amber-500',
            'primary' => 'bg-indigo-600 hover:bg-indigo-700 text-white focus:ring-indigo-500',
            default => 'bg-rose-600 hover:bg-rose-700 text-white focus:ring-rose-500',
        };

        $iconColor = match ($variant) {
            'warning' => 'text-amber-500 bg-amber-50 dark:bg-amber-950/60 ring-amber-100 dark:ring-amber-900',
            'primary' => 'text-indigo-500 bg-indigo-50 dark:bg-indigo-950/60 ring-indigo-100 dark:ring-indigo-900',
            default => 'text-rose-500 bg-rose-50 dark:bg-rose-950/60 ring-rose-100 dark:ring-rose-900',
        };

        // Form wrapping for backend action if action URL provided
        $formOpen = '';
        $formClose = '';
        if ($action !== null) {
            $token = Session::token();
            $methodField = in_array($method, ['PUT', 'DELETE', 'PATCH'], true)
                ? "<input type=\"hidden\" name=\"_method\" value=\"{$method}\">"
                : '';
            $formOpen = "<form method=\"POST\" action=\"{$this->e((string)$action)}\" class=\"inline\"><input type=\"hidden\" name=\"_token\" value=\"{$this->e($token)}\">{$methodField}";
            $formClose = "</form>";
        }

        $onclickAttr = $onConfirm !== null
            ? " onclick=\"document.getElementById('{$id}').classList.add('hidden'); {$onConfirm};\""
            : ($action === null ? " onclick=\"document.getElementById('{$id}').classList.add('hidden');\"" : '');

        return <<<HTML
        <div id="{$id}" class="hidden fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-xs transition-opacity" onclick="document.getElementById('{$id}').classList.add('hidden')"></div>
            <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-sm bg-white dark:bg-gray-900 text-left shadow-none transition-all sm:my-8 sm:w-full sm:max-w-md p-6 border border-gray-200 dark:border-gray-800 {$extraClass}">
                    <div class="flex items-start gap-4">
                        <div class="w-9 h-9 rounded-sm flex items-center justify-center shrink-0 {$iconColor}">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="text-base font-bold text-gray-900 dark:text-white leading-6">
                                {$this->e($title)}
                            </h3>
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 leading-relaxed">
                                {$this->e($message)}
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-3 pt-3 border-t border-gray-100 dark:border-gray-800">
                        <button type="button" onclick="document.getElementById('{$id}').classList.add('hidden')" class="px-4 py-2 text-sm font-medium rounded-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                            {$this->e($cancelText)}
                        </button>
                        {$formOpen}
                        <button type="submit" class="px-4 py-2 text-sm font-semibold rounded-sm shadow-none transition-colors cursor-pointer {$confirmBtnColor}"{$onclickAttr}>
                            {$this->e($confirmText)}
                        </button>
                        {$formClose}
                    </div>
                </div>
            </div>
        </div>
        HTML;
    }
}
