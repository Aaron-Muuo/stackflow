<?php

declare(strict_types=1);

namespace StackFlow\Widgets\Input;

use StackFlow\Widgets\Widget;

/**
 * Form Input Widget
 *
 * Props:
 *  - name: string
 *  - type: 'text'|'email'|'password'|'number'|'tel'|'url'|'search'|'hidden' (default: 'text')
 *  - label: ?string
 *  - placeholder: ?string
 *  - value: mixed
 *  - required: bool
 *  - disabled: bool
 *  - error: ?string
 *  - hint: ?string
 *  - class: ?string
 *  - id: ?string
 *
 * @package StackFlow\Widgets\Input
 */
class Input extends Widget
{
    public function render(): string
    {
        $name = (string) $this->prop('name', '');
        $type = (string) $this->prop('type', 'text');
        $label = $this->prop('label');
        $placeholder = $this->prop('placeholder');
        $value = $this->prop('value');
        $required = (bool) $this->prop('required', false);
        $disabled = (bool) $this->prop('disabled', false);
        $error = $this->prop('error');
        $hint = $this->prop('hint');
        $extraClass = $this->prop('class', '');
        $id = $this->prop('id', $name ? "input_{$name}" : null);

        if ($type === 'hidden') {
            return sprintf(
                '<input type="hidden"%s%s%s>',
                $this->attr('name', $name),
                $this->attr('value', $value),
                $this->attr('id', $id)
            );
        }

        $baseInputClass = 'block w-full rounded-sm border px-3 py-2 text-sm shadow-none transition-colors focus:outline-none focus:ring-1 focus:ring-offset-0 disabled:bg-gray-100 disabled:cursor-not-allowed dark:bg-gray-800 dark:text-white';
        $stateClass = $error
            ? 'border-rose-500 text-rose-900 focus:border-rose-500 focus:ring-rose-500/20 dark:border-rose-400 dark:text-rose-200'
            : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500/20 dark:border-gray-700 dark:focus:border-indigo-400';

        $inputClasses = $this->classes($baseInputClass, $stateClass, $extraClass);

        $labelHtml = '';
        if ($label !== null) {
            $reqMark = $required ? '<span class="text-rose-500 ml-0.5">*</span>' : '';
            $labelHtml = "<label{$this->attr('for', $id)} class=\"block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5\">{$this->e($label)}{$reqMark}</label>";
        }

        $inputHtml = sprintf(
            '<input%s%s%s%s%s%s%s class="%s">',
            $this->attr('id', $id),
            $this->attr('name', $name),
            $this->attr('type', $type),
            $this->attr('value', $value),
            $this->attr('placeholder', $placeholder),
            $required ? ' required' : '',
            $disabled ? ' disabled' : '',
            $inputClasses
        );

        $errorHtml = $error
            ? "<p class=\"mt-1.5 text-xs text-rose-600 dark:text-rose-400\">{$this->e($error)}</p>"
            : '';

        $hintHtml = ($hint && !$error)
            ? "<p class=\"mt-1 text-xs text-gray-500 dark:text-gray-400\">{$this->e($hint)}</p>"
            : '';

        return "<div class=\"w-full\">{$labelHtml}{$inputHtml}{$errorHtml}{$hintHtml}</div>";
    }
}
