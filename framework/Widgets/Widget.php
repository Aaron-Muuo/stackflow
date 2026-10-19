<?php

declare(strict_types=1);

namespace StackFlow\Widgets;

/**
 * Abstract base class for all StackFlow Widgets.
 *
 * Widgets are PHP classes that render HTML with Tailwind CSS classes.
 * They are composable via `child` and `children` props.
 * Users can extend any widget and override `render()` to customize it.
 *
 * Usage:
 *   $widget = MyWidget::make(['label' => 'Hello'])->render();
 *   echo $widget;
 *
 * @package StackFlow\Widgets
 */
abstract class Widget
{
    /**
     * The props array holding all configuration for this widget.
     *
     * @var array<string, mixed>
     */
    protected array $props = [];

    /**
     * Create a new Widget instance with the given props.
     *
     * @param array<string, mixed> $props
     */
    public function __construct(array $props = [])
    {
        $this->props = $props;
    }

    /**
     * Render the widget to an HTML string.
     *
     * Override this method in subclasses to customize rendering.
     *
     * @return string
     */
    abstract public function render(): string;

    /**
     * Magic method — allows widgets to be used directly in string context.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->render();
    }

    /**
     * Static factory method for fluent widget construction.
     *
     * Example: Button::make(['label' => 'Click me', 'variant' => 'primary'])
     *
     * @param  array<string, mixed> $props
     * @return static
     */
    public static function make(array $props = []): static
    {
        return new static($props);
    }

    /**
     * Set a single prop value (fluent setter).
     *
     * Example: Button::make()->set('label', 'Submit')
     *
     * @param  string $key
     * @param  mixed  $value
     * @return static
     */
    public function set(string $key, mixed $value): static
    {
        $this->props[$key] = $value;
        return $this;
    }

    /**
     * Merge additional props into this widget (fluent).
     *
     * @param  array<string, mixed> $props
     * @return static
     */
    public function withProps(array $props): static
    {
        $this->props = array_merge($this->props, $props);
        return $this;
    }

    /**
     * Retrieve a prop value by key, with an optional default.
     *
     * @param  string $key
     * @param  mixed  $default
     * @return mixed
     */
    protected function prop(string $key, mixed $default = null): mixed
    {
        return $this->props[$key] ?? $default;
    }

    /**
     * Join multiple Tailwind class strings, filtering out empty/null values.
     *
     * @param  string ...$classes
     * @return string
     */
    protected function classes(string ...$classes): string
    {
        return implode(' ', array_filter($classes, static fn(string $c) => $c !== ''));
    }

    /**
     * Render a child — which may be a Widget, a raw string, or null.
     *
     * @param  Widget|string|null $child
     * @return string
     */
    protected function renderChild(mixed $child): string
    {
        if ($child instanceof Widget) {
            return $child->render();
        }

        if (is_string($child)) {
            return $child;
        }

        return '';
    }

    /**
     * Render an array of children (Widget|string) into a single HTML string.
     *
     * @param  array<int, Widget|string|null> $children
     * @return string
     */
    protected function renderChildren(array $children): string
    {
        return implode('', array_map([$this, 'renderChild'], $children));
    }

    /**
     * Render an HTML attribute if the value is not null/false.
     *
     * For boolean true, renders the attribute name only (e.g., `disabled`).
     * For strings, renders `name="value"`.
     *
     * @param  string $name
     * @param  mixed  $value
     * @return string
     */
    protected function attr(string $name, mixed $value): string
    {
        if ($value === null || $value === false) {
            return '';
        }

        if ($value === true) {
            return " {$name}";
        }

        $escaped = htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return " {$name}=\"{$escaped}\"";
    }

    /**
     * Merge a base Tailwind class string with an optional extra class string.
     *
     * @param  string      $base
     * @param  string|null $extra
     * @return string
     */
    protected function mergeClasses(string $base, ?string $extra): string
    {
        if ($extra === null || $extra === '') {
            return $base;
        }

        return trim("{$base} {$extra}");
    }

    /**
     * Escape a string for safe HTML output.
     *
     * @param  string $value
     * @return string
     */
    protected function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
