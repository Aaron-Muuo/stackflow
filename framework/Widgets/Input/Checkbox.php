<?php

declare(strict_types=1);

namespace StackFlow\Widgets\Input;

use StackFlow\Widgets\Widget;

/**
 * Checkbox Widget
 *
 * Props:
 *  - name: string
 *  - label: string
 *  - checked: bool
 *  - value: mixed (default: '1')
 *  - disabled: bool
 *  - error: ?string
 *  - class: ?string
 *  - id: ?string
 *
 * @package StackFlow\Widgets\Input
 */
class Checkbox extends Widget
{
    public function render(): string
    {
        $name = (string) $this->prop('name', '');
        $label = $this->prop('label', '');
        $checked = (bool) $this->prop('checked', false);
        $value = $this->prop('value', '1');
        $disabled = (bool) $this->prop('disabled', false);
        $error = $this->prop('error');
        $extraClass = $this->prop('class', '');
        $id = $this->prop('id', $name ? "cb_{$name}" : null);

        $boxClass = 'h-4 w-4 rounded-sm border-gray-300 text-indigo-600 focus:ring-1 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 disabled:opacity-50';
        $classes = $this->classes($boxClass, $extraClass);

        $checkedAttr = $checked ? ' checked' : '';
        $disabledAttr = $disabled ? ' disabled' : '';

        $inputHtml = sprintf(
            '<input type="checkbox"%s%s%s%s%s class="%s">',
            $this->attr('id', $id),
            $this->attr('name', $name),
            $this->attr('value', $value),
            $checkedAttr,
            $disabledAttr,
            $classes
        );

        $labelHtml = $label !== ''
            ? "<label{$this->attr('for', $id)} class=\"ml-2 text-sm text-gray-700 dark:text-gray-300 select-none cursor-pointer\">{$this->e($label)}</label>"
            : '';

        $errorHtml = $error ? "<p class=\"mt-1 text-xs text-rose-600 dark:text-rose-400\">{$this->e($error)}</p>" : '';

        return "<div class=\"flex flex-col\"><div class=\"flex items-center\">{$inputHtml}{$labelHtml}</div>{$errorHtml}</div>";
    }
}
