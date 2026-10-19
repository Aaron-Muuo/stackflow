<?php

declare(strict_types=1);

namespace StackFlow\Widgets\Input;

use StackFlow\Widgets\Widget;

/**
 * FileInput Widget
 *
 * Renders a clean, accessible file upload input with drag-and-drop aesthetic
 * and Tailwind styling.
 *
 * Props:
 *  - name: string
 *  - label: ?string
 *  - accept: ?string (e.g. 'image/*', '.pdf,.docx')
 *  - multiple: bool (default: false)
 *  - disabled: bool (default: false)
 *  - error: ?string
 *  - hint: ?string
 *  - class: ?string
 *  - id: ?string
 *
 * @package StackFlow\Widgets\Input
 */
class FileInput extends Widget
{
    public function render(): string
    {
        $name = (string) $this->prop('name', '');
        $label = $this->prop('label');
        $accept = $this->prop('accept');
        $multiple = (bool) $this->prop('multiple', false);
        $disabled = (bool) $this->prop('disabled', false);
        $error = $this->prop('error');
        $hint = $this->prop('hint');
        $extraClass = $this->prop('class', '');
        $id = $this->prop('id', 'file-' . uniqid());

        $labelHtml = $label !== null
            ? "<label for=\"{$id}\" class=\"block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5\">{$this->e($label)}</label>"
            : '';

        $hintHtml = $hint !== null
            ? "<p class=\"text-xs text-gray-500 dark:text-gray-400 mt-1\">{$this->e($hint)}</p>"
            : '';

        $errorHtml = $error !== null
            ? "<p class=\"text-xs text-rose-600 dark:text-rose-400 mt-1\">{$this->e($error)}</p>"
            : '';

        $multipleAttr = $multiple ? ' multiple' : '';
        $disabledAttr = $disabled ? ' disabled' : '';
        $acceptAttr = $accept !== null ? " accept=\"{$this->e($accept)}\"" : '';

        return <<<HTML
        <div class="{$extraClass}">
            {$labelHtml}
            <input type="file" id="{$id}" name="{$name}"{$acceptAttr}{$multipleAttr}{$disabledAttr} class="block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-sm file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-950/60 dark:file:text-indigo-400 file:cursor-pointer cursor-pointer border border-gray-300 dark:border-gray-700 rounded-sm shadow-none p-1 bg-white dark:bg-gray-800 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition-colors">
            {$hintHtml}
            {$errorHtml}
        </div>
        HTML;
    }
}
