<?php

declare(strict_types=1);

namespace StackFlow\Widgets\Display;

use StackFlow\Widgets\Widget;

/**
 * Text — Typographic text widget.
 *
 * Renders a block or inline text element with Tailwind typography utilities.
 * The `value` prop is HTML-escaped by default; set `raw` to true to allow HTML.
 *
 * Props:
 *   - value   (string)  The text content (required)
 *   - tag     (string)  HTML tag: 'p'|'h1'|'h2'|'h3'|'h4'|'span'|'label' (default 'p')
 *   - size    (string)  Tailwind text size class (e.g. 'text-lg')
 *   - weight  (string)  Tailwind font weight class (e.g. 'font-semibold')
 *   - color   (string)  Tailwind text color class (e.g. 'text-gray-700')
 *   - class   (string)  Extra CSS classes
 *   - raw     (bool)    If true, value is rendered as raw HTML (default false)
 *   - for     (string)  If tag is 'label', sets the `for` attribute
 *
 * @package StackFlow\Widgets\Display
 */
class Text extends Widget
{
    /**
     * Allowed HTML tags for safety.
     */
    private const ALLOWED_TAGS = ['p', 'h1', 'h2', 'h3', 'h4', 'span', 'label'];

    public function render(): string
    {
        $value  = (string) $this->prop('value', '');
        $tag    = (string) $this->prop('tag', 'p');
        $size   = (string) $this->prop('size', '');
        $weight = (string) $this->prop('weight', '');
        $color  = (string) $this->prop('color', '');
        $extra  = (string) $this->prop('class', '');
        $raw    = (bool)   $this->prop('raw', false);
        $for    = $this->prop('for');

        // Sanitize tag
        if (!in_array($tag, self::ALLOWED_TAGS, true)) {
            $tag = 'p';
        }

        $cssClasses = $this->classes($size, $weight, $color, $extra);
        $classAttr  = $cssClasses !== '' ? " class=\"{$cssClasses}\"" : '';
        $forAttr    = ($tag === 'label' && $for !== null) ? $this->attr('for', $for) : '';
        $content    = $raw ? $value : $this->e($value);

        return "<{$tag}{$classAttr}{$forAttr}>{$content}</{$tag}>";
    }
}
