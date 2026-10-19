<?php

declare(strict_types=1);

namespace StackFlow\Widgets\Input;

use StackFlow\Widgets\Widget;

/**
 * Switch Toggle Widget
 *
 * Renders an iOS/Android style animated toggle switch component.
 *
 * Props:
 *  - name: string
 *  - label: ?string
 *  - description: ?string
 *  - checked: bool (default: false)
 *  - value: mixed (default: '1')
 *  - disabled: bool (default: false)
 *  - error: ?string
 *  - class: ?string
 *  - id: ?string
 *
 * @package StackFlow\Widgets\Input
 */
class SwitchToggle extends Widget
{
    public function render(): string
    {
        $name = (string) $this->prop('name', '');
        $label = $this->prop('label');
        $description = $this->prop('description');
        $checked = (bool) $this->prop('checked', false);
        $value = (string) $this->prop('value', '1');
        $disabled = (bool) $this->prop('disabled', false);
        $error = $this->prop('error');
        $extraClass = $this->prop('class', '');
        $id = $this->prop('id', 'switch-' . uniqid());

        $checkedAttr = $checked ? ' checked' : '';
        $disabledAttr = $disabled ? ' disabled' : '';

        $descHtml = $description !== null
            ? "<p class=\"text-xs text-gray-500 dark:text-gray-400\">{$this->e($description)}</p>"
            : '';

        $errorHtml = $error !== null
            ? "<p class=\"mt-1 text-xs text-rose-600 dark:text-rose-400\">{$this->e($error)}</p>"
            : '';

        return <<<HTML
        <div class="flex items-start justify-between gap-4 {$extraClass}">
            <div class="flex flex-col">
                <label for="{$id}" class="text-sm font-medium text-gray-800 dark:text-gray-200 cursor-pointer select-none">
                    {$this->e((string)$label)}
                </label>
                {$descHtml}
                {$errorHtml}
            </div>
            <label for="{$id}" class="relative inline-flex items-center cursor-pointer shrink-0">
                <input type="checkbox" id="{$id}" name="{$name}" value="{$this->e($value)}" class="sr-only peer"{$checkedAttr}{$disabledAttr}>
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-indigo-400 dark:peer-focus:ring-indigo-600 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
            </label>
        </div>
        HTML;
    }
}
