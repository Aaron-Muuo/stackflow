<?php

declare(strict_types=1);

namespace StackFlow\Widgets\Data;

use StackFlow\Widgets\Widget;

/**
 * Pagination Widget
 *
 * Props:
 *  - currentPage: int
 *  - totalPages: int
 *  - baseUrl: string (default: '?page=')
 *  - class: ?string
 *
 * @package StackFlow\Widgets\Data
 */
class Pagination extends Widget
{
    public function render(): string
    {
        $currentPage = (int) $this->prop('currentPage', 1);
        $totalPages = (int) $this->prop('totalPages', 1);
        $baseUrl = (string) $this->prop('baseUrl', '?page=');
        $extraClass = $this->prop('class', '');

        if ($totalPages <= 1) {
            return '';
        }

        $classes = $this->classes('flex items-center justify-between border-t border-gray-200 dark:border-gray-800 px-4 py-3 sm:px-6', $extraClass);

        $prevPage = max(1, $currentPage - 1);
        $nextPage = min($totalPages, $currentPage + 1);

        $prevDisabled = ($currentPage <= 1);
        $nextDisabled = ($currentPage >= $totalPages);

        $prevUrl = $prevDisabled ? '#' : $this->buildUrl($baseUrl, $prevPage);
        $nextUrl = $nextDisabled ? '#' : $this->buildUrl($baseUrl, $nextPage);

        $prevClass = $prevDisabled
            ? 'pointer-events-none opacity-40 text-gray-400'
            : 'text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-800';

        $nextClass = $nextDisabled
            ? 'pointer-events-none opacity-40 text-gray-400'
            : 'text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-800';

        $pageItems = '';
        for ($i = 1; $i <= $totalPages; $i++) {
            if ($i === $currentPage) {
                $pageItems .= "<span class=\"relative z-10 inline-flex items-center bg-indigo-600 px-4 py-2 text-sm font-semibold text-white rounded-sm focus:z-20\">{$i}</span>";
            } elseif ($i === 1 || $i === $totalPages || ($i >= $currentPage - 1 && $i <= $currentPage + 1)) {
                $url = $this->buildUrl($baseUrl, $i);
                $pageItems .= "<a href=\"{$url}\" class=\"relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-900 dark:text-gray-200 ring-1 ring-inset ring-gray-300 dark:ring-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 rounded-sm\">{$i}</a>";
            } elseif ($i === $currentPage - 2 || $i === $currentPage + 2) {
                $pageItems .= "<span class=\"relative inline-flex items-center px-2 py-2 text-sm text-gray-500\">...</span>";
            }
        }

        return <<<HTML
        <nav class="{$classes}" aria-label="Pagination">
            <div class="flex flex-1 justify-between sm:hidden">
                <a href="{$prevUrl}" class="relative inline-flex items-center rounded-sm border border-gray-300 px-4 py-2 text-sm font-medium {$prevClass}">Previous</a>
                <a href="{$nextUrl}" class="relative ml-3 inline-flex items-center rounded-sm border border-gray-300 px-4 py-2 text-sm font-medium {$nextClass}">Next</a>
            </div>
            <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm text-gray-700 dark:text-gray-300">
                        Page <span class="font-medium">{$currentPage}</span> of <span class="font-medium">{$totalPages}</span>
                    </p>
                </div>
                <div>
                    <div class="flex items-center space-x-1">
                        <a href="{$prevUrl}" class="relative inline-flex items-center px-3 py-2 text-sm font-medium rounded-sm border border-gray-300 dark:border-gray-700 {$prevClass}">Previous</a>
                        {$pageItems}
                        <a href="{$nextUrl}" class="relative inline-flex items-center px-3 py-2 text-sm font-medium rounded-sm border border-gray-300 dark:border-gray-700 {$nextClass}">Next</a>
                    </div>
                </div>
            </div>
        </nav>
        HTML;
    }

    private function buildUrl(string $base, int $page): string
    {
        if (str_contains($base, '?page=')) {
            return str_replace('?page=', '?page=' . $page, $base);
        }

        $sep = str_contains($base, '?') ? '&' : '?';
        return "{$base}{$sep}page={$page}";
    }
}
