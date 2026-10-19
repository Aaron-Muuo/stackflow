<?php

declare(strict_types=1);

namespace StackFlow\Widgets\Display;

use StackFlow\Widgets\Widget;

/**
 * Heading Widget
 *
 * Renders HTML heading elements <h1> through <h6> with proportional typographic scaling,
 * tracking, and an optional subtitle description.
 *
 * Props:
 *  - level: int 1-6 (default: 2)
 *  - value: string (heading text)
 *  - subtitle: ?string
 *  - class: ?string
 *  - id: ?string
 *
 * @package StackFlow\Widgets\Display
 */
class Heading extends Widget
{
    public function render(): string
    {
        $level = (int) $this->prop('level', 2);
        if ($level < 1 || $level > 6) {
            $level = 2;
        }

        $tag = "h{$level}";
        $value = (string) $this->prop('value', '');
        $subtitle = $this->prop('subtitle');
        $extraClass = $this->prop('class', '');
        $id = $this->prop('id');

        $levelClass = match ($level) {
            1 => 'text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight text-gray-900 dark:text-white',
            2 => 'text-2xl sm:text-3xl font-bold tracking-tight text-gray-900 dark:text-white',
            3 => 'text-xl sm:text-2xl font-bold tracking-tight text-gray-900 dark:text-white',
            4 => 'text-lg sm:text-xl font-semibold text-gray-900 dark:text-white',
            5 => 'text-base font-semibold text-gray-900 dark:text-white',
            6 => 'text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400',
        };

        $classes = $this->classes($levelClass, $extraClass);
        $idAttr = $this->attr('id', $id);

        $headingHtml = "<{$tag}{$idAttr} class=\"{$classes}\">{$this->e($value)}</{$tag}>";

        if ($subtitle !== null) {
            $subtitleHtml = "<p class=\"mt-1 text-sm sm:text-base text-gray-500 dark:text-gray-400 font-normal\">{$this->e((string)$subtitle)}</p>";
            return "<div>{$headingHtml}{$subtitleHtml}</div>";
        }

        return $headingHtml;
    }
}
