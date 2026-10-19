<?php

declare(strict_types=1);

namespace StackFlow\Widgets\Data;

use Closure;
use StackFlow\Widgets\Widget;

/**
 * Data Table Widget
 *
 * Props:
 *  - columns: array of ['key' => string, 'label' => string, 'render' => ?Closure]
 *  - rows: array (array of arrays or objects)
 *  - actions: ?Closure (fn($row): Widget|string)
 *  - striped: bool (default: true)
 *  - hover: bool (default: true)
 *  - emptyMessage: string (default: 'No records found.')
 *  - class: ?string
 *
 * @package StackFlow\Widgets\Data
 */
class Table extends Widget
{
    public function render(): string
    {
        $columns = (array) $this->prop('columns', []);
        $rows = (array) $this->prop('rows', []);
        $actions = $this->prop('actions');
        $striped = (bool) $this->prop('striped', true);
        $hover = (bool) $this->prop('hover', true);
        $emptyMessage = (string) $this->prop('emptyMessage', 'No records found.');
        $extraClass = $this->prop('class', '');

        $hasActions = ($actions instanceof Closure);

        // Header cells
        $theadCols = '';
        foreach ($columns as $col) {
            $label = is_array($col) ? ($col['label'] ?? $col['key'] ?? '') : (string) $col;
            $theadCols .= "<th scope=\"col\" class=\"px-6 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider\">{$this->e($label)}</th>";
        }
        if ($hasActions) {
            $theadCols .= "<th scope=\"col\" class=\"px-6 py-3.5 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider\">Actions</th>";
        }

        // Body rows
        $tbodyRows = '';
        if (empty($rows)) {
            $colspan = count($columns) + ($hasActions ? 1 : 0);
            $tbodyRows = "<tr><td colspan=\"{$colspan}\" class=\"px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400\">{$this->e($emptyMessage)}</td></tr>";
        } else {
            foreach ($rows as $index => $row) {
                $isEven = ($index % 2 === 0);
                $rowClasses = [];

                if ($striped && !$isEven) {
                    $rowClasses[] = 'bg-gray-50/60 dark:bg-gray-800/40';
                }
                if ($hover) {
                    $rowClasses[] = 'hover:bg-indigo-50/40 dark:hover:bg-indigo-950/20 transition-colors';
                }

                $rowClassStr = implode(' ', $rowClasses);

                $cellsHtml = '';
                foreach ($columns as $col) {
                    $key = is_array($col) ? ($col['key'] ?? '') : (string) $col;
                    $renderer = is_array($col) ? ($col['render'] ?? null) : null;

                    $rawVal = is_object($row) ? ($row->{$key} ?? '') : ($row[$key] ?? '');

                    if ($renderer instanceof Closure) {
                        $renderedVal = $this->renderChild($renderer($rawVal, $row));
                    } else {
                        $renderedVal = $this->e((string) $rawVal);
                    }

                    $cellsHtml .= "<td class=\"px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100\">{$renderedVal}</td>";
                }

                if ($hasActions) {
                    $actionContent = $this->renderChild($actions($row));
                    $cellsHtml .= "<td class=\"px-6 py-4 whitespace-nowrap text-right text-sm font-medium\">{$actionContent}</td>";
                }

                $tbodyRows .= "<tr class=\"{$rowClassStr}\">{$cellsHtml}</tr>";
            }
        }

        return <<<HTML
        <div class="overflow-x-auto rounded-sm border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-none {$extraClass}">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                <thead class="bg-gray-50 dark:bg-gray-800/80">
                    <tr>{$theadCols}</tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    {$tbodyRows}
                </tbody>
            </table>
        </div>
        HTML;
    }
}
