<?php

declare(strict_types=1);

namespace StackFlow\Widgets\Data;

use Closure;
use StackFlow\Widgets\Widget;

/**
 * ListView Widget
 *
 * Props:
 *  - items: array
 *  - builder: Closure(item, index): Widget|string
 *  - divider: bool (default: true)
 *  - emptyMessage: string (default: 'No items.')
 *  - class: ?string
 *
 * @package StackFlow\Widgets\Data
 */
class ListView extends Widget
{
    public function render(): string
    {
        $items = (array) $this->prop('items', []);
        $builder = $this->prop('builder');
        $divider = (bool) $this->prop('divider', true);
        $emptyMessage = (string) $this->prop('emptyMessage', 'No items.');
        $extraClass = $this->prop('class', '');

        if (empty($items)) {
            return "<div class=\"py-8 text-center text-sm text-gray-500 dark:text-gray-400 {$extraClass}\">{$this->e($emptyMessage)}</div>";
        }

        $dividerClass = $divider ? 'divide-y divide-gray-100 dark:divide-gray-800' : 'space-y-2';
        $classes = $this->classes('flex flex-col', $dividerClass, $extraClass);

        $html = "<div class=\"{$classes}\">";

        if ($builder instanceof Closure) {
            foreach ($items as $idx => $item) {
                $rendered = $this->renderChild($builder($item, $idx));
                $html .= "<div class=\"py-3\">{$rendered}</div>";
            }
        }

        $html .= '</div>';

        return $html;
    }
}
