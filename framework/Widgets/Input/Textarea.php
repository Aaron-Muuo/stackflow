<?php

declare(strict_types=1);

namespace StackFlow\Widgets\Input;

use StackFlow\Widgets\Widget;

/**
 * Textarea Widget
 *
 * Props:
 *  - name: string
 *  - label: ?string
 *  - value: ?string
 *  - rows: int (default: 4)
 *  - placeholder: ?string
 *  - required: bool
 *  - disabled: bool
 *  - error: ?string
 *  - class: ?string
 *  - id: ?string
 *
 * @package StackFlow\Widgets\Input
 */
class Textarea extends Widget
{
    public function render(): string
    {
        $name = (string) $this->prop('name', '');
        $label = $this->prop('label');
        $value = (string) $this->prop('value', '');
        $rows = (int) $this->prop('rows', 4);
        $placeholder = $this->prop('placeholder');
        $required = (bool) $this->prop('required', false);
        $disabled = (bool) $this->prop('disabled', false);
        $error = $this->prop('error');
        $extraClass = $this->prop('class', '');
        $id = $this->prop('id', $name ? "textarea_{$name}" : null);

        $baseClass = 'block w-full rounded-sm border px-3.5 py-2 text-sm shadow-none transition-colors focus:outline-none focus:ring-1 focus:ring-offset-0 disabled:bg-gray-100 dark:bg-gray-800 dark:text-white';
        $stateClass = $error
            ? 'border-rose-500 focus:border-rose-500 focus:ring-rose-500/20'
            : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500/20 dark:border-gray-700';

        $classes = $this->classes($baseClass, $stateClass, $extraClass);

        $labelHtml = '';
        if ($label !== null) {
            $reqMark = $required ? '<span class="text-rose-500 ml-0.5">*</span>' : '';
            $labelHtml = "<label{$this->attr('for', $id)} class=\"block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5\">{$this->e($label)}{$reqMark}</label>";
        }

        $textareaHtml = sprintf(
            '<textarea%s%s rows="%d"%s%s%s class="%s">%s</textarea>',
            $this->attr('id', $id),
            $this->attr('name', $name),
            $rows,
            $this->attr('placeholder', $placeholder),
            $required ? ' required' : '',
            $disabled ? ' disabled' : '',
            $classes,
            $this->e($value)
        );

        $errorHtml = $error ? "<p class=\"mt-1.5 text-xs text-rose-600 dark:text-rose-400\">{$this->e($error)}</p>" : '';

        return "<div class=\"w-full\">{$labelHtml}{$textareaHtml}{$errorHtml}</div>";
    }
}
