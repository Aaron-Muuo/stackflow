<?php

declare(strict_types=1);

namespace StackFlow\Widgets\Display;

use StackFlow\Widgets\Widget;

/**
 * Image Widget
 *
 * Renders an accessible, responsive HTML <img> element with Tailwind utilities.
 *
 * Props:
 *  - src: string (required)
 *  - alt: string (default: '')
 *  - width: string|int|null
 *  - height: string|int|null
 *  - rounded: 'none'|'sm'|'md'|'lg'|'xl'|'2xl'|'full' (default: 'none')
 *  - fit: 'cover'|'contain'|'fill'|'none' (default: 'cover')
 *  - aspect: 'square'|'video'|'auto' (default: 'auto')
 *  - loading: 'lazy'|'eager' (default: 'lazy')
 *  - class: ?string
 *  - id: ?string
 *
 * @package StackFlow\Widgets\Display
 */
class Image extends Widget
{
    public function render(): string
    {
        $src = (string) $this->prop('src', '');
        $alt = (string) $this->prop('alt', '');
        $width = $this->prop('width');
        $height = $this->prop('height');
        $rounded = (string) $this->prop('rounded', 'none');
        $fit = (string) $this->prop('fit', 'cover');
        $aspect = (string) $this->prop('aspect', 'auto');
        $loading = (string) $this->prop('loading', 'lazy');
        $extraClass = $this->prop('class', '');
        $id = $this->prop('id');

        $roundedClass = match ($rounded) {
            'sm' => 'rounded-sm',
            'md' => 'rounded-md',
            'lg' => 'rounded-lg',
            'xl' => 'rounded-xl',
            '2xl' => 'rounded-2xl',
            'full' => 'rounded-full',
            default => '',
        };

        $fitClass = match ($fit) {
            'contain' => 'object-contain',
            'fill' => 'object-fill',
            'none' => 'object-none',
            default => 'object-cover',
        };

        $aspectClass = match ($aspect) {
            'square' => 'aspect-square',
            'video' => 'aspect-video',
            default => '',
        };

        $classes = $this->classes('max-w-full h-auto', $roundedClass, $fitClass, $aspectClass, $extraClass);

        $attrs = $this->attr('src', $src)
            . $this->attr('alt', $alt)
            . $this->attr('width', $width)
            . $this->attr('height', $height)
            . $this->attr('loading', $loading)
            . $this->attr('id', $id);

        return "<img{$attrs} class=\"{$classes}\">";
    }
}
