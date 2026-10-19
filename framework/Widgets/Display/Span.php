<?php

declare(strict_types=1);

namespace StackFlow\Widgets\Display;

use StackFlow\Widgets\Widget;

/**
 * Span — Inline text widget for <span> elements.
 *
 * Accepts text directly as the first argument, or a props array.
 *
 * Usage:
 *   Span::make("Don't have an account?")
 *   Span::make("Don't have an account?", ['class' => 'text-gray-500'])
 *   Span::make(['text' => "Don't have an account?", 'weight' => 'font-bold'])
 *   Span::make(['child' => $iconWidget])
 *
 * Props:
 *   - text / value (string): The text content (HTML-escaped by default)
 *   - child / children (Widget|string|array): Optional nested widgets
 *   - class (string): Extra Tailwind CSS classes
 *   - size (string): Tailwind text size (e.g. 'text-sm')
 *   - weight (string): Tailwind font weight (e.g. 'font-medium')
 *   - color (string): Tailwind text color (e.g. 'text-gray-500')
 *   - raw (bool): If true, renders unescaped HTML (default false)
 *   - id (string): Optional HTML id
 *
 * @package StackFlow\Widgets\Display
 */
class Span extends Widget
{
    /**
     * Create a new Span instance.
     *
     * @param string|array<string, mixed> $textOrProps Text string or props array
     * @param array<string, mixed> $props Extra props when first argument is text
     */
    public function __construct(string|array $textOrProps = '', array $props = [])
    {
        if (is_array($textOrProps)) {
            parent::__construct($textOrProps);
        } else {
            $props['text'] = $textOrProps;
            parent::__construct($props);
        }
    }

    /**
     * Static factory method for fluent construction.
     *
     * Allows passing text directly as the first argument.
     *
     * Example:
     *   Span::make("Don't have an account?")
     *   Span::make("Don't have an account?", ['class' => 'text-gray-500'])
     *
     * @param string|array<string, mixed> $textOrProps
     * @param array<string, mixed> $props
     * @return static
     */
    public static function make(string|array $textOrProps = '', array $props = []): static
    {
        return new static($textOrProps, $props);
    }

    public function render(): string
    {
        $text   = (string) $this->prop('text', $this->prop('value', ''));
        $size   = (string) $this->prop('size', '');
        $weight = (string) $this->prop('weight', '');
        $color  = (string) $this->prop('color', '');
        $extra  = (string) $this->prop('class', '');
        $raw    = (bool)   $this->prop('raw', false);
        $id     = $this->prop('id');

        $bodyContent = '';
        if ($this->prop('child') !== null) {
            $bodyContent = $this->renderChild($this->prop('child'));
        } elseif (!empty($this->prop('children'))) {
            $bodyContent = $this->renderChildren((array) $this->prop('children'));
        } elseif ($text !== '') {
            $bodyContent = $raw ? $text : $this->e($text);
        }

        $cssClasses = $this->classes($size, $weight, $color, $extra);
        $classAttr  = $cssClasses !== '' ? " class=\"{$cssClasses}\"" : '';
        $idAttr     = $this->attr('id', $id);

        return "<span{$idAttr}{$classAttr}>{$bodyContent}</span>";
    }
}
