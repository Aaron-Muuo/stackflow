<?php

declare(strict_types=1);

namespace StackFlow\Widgets\Input;

use StackFlow\Widgets\Widget;

/**
 * Button Widget
 *
 * A versatile, Tailwind-styled button or anchor element.
 * Users can extend this class and override `render()` to change styling or markup.
 *
 * Props:
 *  - label: string (button label text)
 *  - type: 'button'|'submit'|'reset' (default: 'button')
 *  - variant: 'primary'|'secondary'|'danger'|'ghost'|'outline' (default: 'primary')
 *  - size: 'sm'|'md'|'lg' (default: 'md')
 *  - href: ?string (if provided, renders <a> tag instead of <button>)
 *  - disabled: bool (default: false)
 *  - loading: bool (default: false)
 *  - icon: ?string (HTML icon markup or SVG)
 *  - class: ?string (additional custom CSS classes)
 *  - id: ?string
 *  - onClick: ?string (JavaScript onclick handler)
 *
 * @package StackFlow\Widgets\Input
 */
class Button extends Widget
{
    public function render(): string
    {
        $label = $this->prop('label', '');
        $variant = $this->prop('variant', 'primary');
        $size = $this->prop('size', 'md');
        $href = $this->prop('href');
        $disabled = (bool) $this->prop('disabled', false);
        $loading = (bool) $this->prop('loading', false);
        $icon = $this->prop('icon');
        $extraClass = $this->prop('class', '');
        $id = $this->prop('id');
        $onClick = $this->prop('onClick');
        $rounded = $this->prop('rounded', 'sm');
        $shadow = $this->prop('shadow', 'none');

        $roundedClass = match ($rounded) {
            'none' => 'rounded-none',
            'md' => 'rounded-md',
            'lg' => 'rounded-lg',
            'xl' => 'rounded-xl',
            'full' => 'rounded-full',
            default => 'rounded-sm',
        };

        $shadowClass = match ($shadow) {
            'sm' => 'shadow-sm',
            'md' => 'shadow-md',
            'lg' => 'shadow-lg',
            'xl' => 'shadow-xl',
            default => 'shadow-none',
        };

        $baseClasses = "inline-flex items-center justify-center font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:pointer-events-none {$roundedClass} {$shadowClass} cursor-pointer";

        $sizeClasses = match ($size) {
            'sm' => 'px-3 py-1.5 text-xs gap-1.5',
            'lg' => 'px-6 py-3 text-base gap-2.5',
            default => 'px-4 py-2 text-sm gap-2',
        };

        $variantClasses = match ($variant) {
            'secondary' => 'bg-gray-100 hover:bg-gray-200 text-gray-800 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-100 focus:ring-gray-400',
            'danger' => 'bg-rose-600 hover:bg-rose-700 text-white focus:ring-rose-500',
            'ghost' => 'bg-transparent hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-200 focus:ring-gray-400',
            'outline' => 'bg-transparent border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-200 focus:ring-indigo-500',
            default => 'bg-indigo-600 hover:bg-indigo-700 text-white focus:ring-indigo-500',
        };

        $classes = $this->classes($baseClasses, $sizeClasses, $variantClasses, $extraClass);

        $loadingSpinner = $loading ? '<svg class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>' : '';
        $iconMarkup = ($icon && !$loading) ? "<span class=\"button-icon\">{$icon}</span>" : '';
        $body = "{$loadingSpinner}{$iconMarkup}<span>{$this->e($label)}</span>";

        $idAttr = $this->attr('id', $id);
        $onclickAttr = $this->attr('onclick', $onClick);

        if ($href !== null && !$disabled) {
            $hrefAttr = $this->attr('href', $href);
            return "<a{$idAttr}{$hrefAttr} class=\"{$classes}\"{$onclickAttr}>{$body}</a>";
        }

        $type = $this->prop('type', 'button');
        $typeAttr = $this->attr('type', $type);
        $disabledAttr = ($disabled || $loading) ? ' disabled' : '';

        return "<button{$idAttr}{$typeAttr} class=\"{$classes}\"{$onclickAttr}{$disabledAttr}>{$body}</button>";
    }
}
