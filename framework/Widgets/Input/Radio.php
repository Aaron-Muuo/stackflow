<?php

declare(strict_types=1);

namespace StackFlow\Widgets\Input;

use StackFlow\Widgets\Widget;

/**
 * Radio Group Widget
 *
 * Renders a group of radio buttons with Tailwind styling, labels, and error states.
 *
 * Props:
 *  - name: string (input name attribute)
 *  - label: ?string (group label text)
 *  - options: array of ['value' => '...', 'label' => '...'] or key => label map
 *  - value: mixed (currently selected value)
 *  - inline: bool (horizontal layout if true, default: false)
 *  - error: ?string (error message)
 *  - disabled: bool (default: false)
 *  - class: ?string
 *  - id: ?string
 *
 * @package StackFlow\Widgets\Input
 */
class Radio extends Widget
{
    public function render(): string
    {
        $name = (string) $this->prop('name', '');
        $label = $this->prop('label');
        $options = (array) $this->prop('options', []);
        $selectedValue = $this->prop('value');
        $inline = (bool) $this->prop('inline', false);
        $error = $this->prop('error');
        $disabled = (bool) $this->prop('disabled', false);
        $extraClass = $this->prop('class', '');

        $labelHtml = $label !== null
            ? "<label class=\"block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2\">{$this->e($label)}</label>"
            : '';

        $errorHtml = $error !== null
            ? "<p class=\"mt-1 text-xs text-rose-600 dark:text-rose-400\">{$this->e($error)}</p>"
            : '';

        $groupLayout = $inline ? 'flex flex-row flex-wrap gap-4' : 'flex flex-col gap-2';

        $optionsHtml = '';
        foreach ($options as $key => $opt) {
            $optValue = is_array($opt) ? ($opt['value'] ?? '') : (string) $key;
            $optLabel = is_array($opt) ? ($opt['label'] ?? '') : (string) $opt;

            $isChecked = (string) $optValue === (string) $selectedValue;
            $checkedAttr = $isChecked ? ' checked' : '';
            $disabledAttr = $disabled ? ' disabled' : '';

            $optId = 'radio-' . $name . '-' . preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$optValue);

            $optionsHtml .= <<<HTML
            <label for="{$optId}" class="inline-flex items-center gap-2.5 cursor-pointer text-sm text-gray-800 dark:text-gray-200 select-none">
                <input type="radio" id="{$optId}" name="{$name}" value="{$this->e((string)$optValue)}" class="w-4 h-4 text-indigo-600 border-gray-300 dark:border-gray-600 focus:ring-indigo-500 dark:focus:ring-indigo-400 dark:bg-gray-700 transition-colors"{$checkedAttr}{$disabledAttr}>
                <span>{$this->e($optLabel)}</span>
            </label>
            HTML;
        }

        return <<<HTML
        <div class="{$extraClass}">
            {$labelHtml}
            <div class="{$groupLayout}">
                {$optionsHtml}
            </div>
            {$errorHtml}
        </div>
        HTML;
    }
}
