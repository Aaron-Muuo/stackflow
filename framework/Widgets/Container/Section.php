<?php

declare(strict_types=1);

namespace StackFlow\Widgets\Container;

use StackFlow\Widgets\Widget;

/**
 * Section Widget
 *
 * Props:
 *  - child: Widget|string|null
 *  - children: array
 *  - title: ?string
 *  - subtitle: ?string
 *  - class: ?string
 *  - id: ?string
 *
 * @package StackFlow\Widgets\Container
 */
class Section extends Widget
{
    public function render(): string
    {
        $title = $this->prop('title');
        $subtitle = $this->prop('subtitle');
        $extraClass = $this->prop('class', '');
        $id = $this->prop('id');

        $classes = $this->classes('py-8', $extraClass);

        $headerHtml = '';
        if ($title !== null || $subtitle !== null) {
            $titleMarkup = $title !== null ? "<h2 class=\"text-2xl font-bold tracking-tight text-gray-900 dark:text-white\">{$this->e($title)}</h2>" : '';
            $subMarkup = $subtitle !== null ? "<p class=\"mt-1 text-sm text-gray-500 dark:text-gray-400\">{$this->e($subtitle)}</p>" : '';
            $headerHtml = "<div class=\"mb-6\">{$titleMarkup}{$subMarkup}</div>";
        }

        $bodyContent = '';
        if ($this->prop('child') !== null) {
            $bodyContent = $this->renderChild($this->prop('child'));
        } elseif (!empty($this->prop('children'))) {
            $bodyContent = $this->renderChildren((array) $this->prop('children'));
        }

        $idAttr = $this->attr('id', $id);

        return "<section{$idAttr} class=\"{$classes}\">{$headerHtml}{$bodyContent}</section>";
    }
}
