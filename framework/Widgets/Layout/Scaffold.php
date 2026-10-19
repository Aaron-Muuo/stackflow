<?php

declare(strict_types=1);

namespace StackFlow\Widgets\Layout;

use StackFlow\Widgets\Widget;

/**
 * Scaffold — Full-page shell widget (inspired by Flutter's Scaffold).
 *
 * Provides structured layout slots for:
 *   - appBar (Widget|string|null): Top navigation bar slot
 *   - body (Widget|string|null): Main content area
 *   - drawer (Widget|string|null): Side drawer slot
 *   - bottomNav (Widget|string|null): Bottom navigation slot
 *   - floatingActionButton (Widget|string|null): Floating action button slot
 *   - class (string): Extra classes for the container
 *
 * @package StackFlow\Widgets\Layout
 */
class Scaffold extends Widget
{
    public function render(): string
    {
        $appBar    = $this->renderChild($this->prop('appBar'));
        $body      = $this->renderChild($this->prop('body'));
        $drawer    = $this->renderChild($this->prop('drawer'));
        $bottomNav = $this->renderChild($this->prop('bottomNav'));
        $fab       = $this->renderChild($this->prop('floatingActionButton'));
        $extraClass = $this->prop('class', '');

        $drawerHtml    = $drawer    ? "<aside class=\"stackflow-drawer\">{$drawer}</aside>" : '';
        $bottomNavHtml = $bottomNav ? "<nav class=\"stackflow-bottom-nav\">{$bottomNav}</nav>" : '';
        $fabHtml       = $fab       ? "<div class=\"fixed bottom-6 right-6 z-50\">{$fab}</div>" : '';

        $classes = $this->classes('min-h-screen flex flex-col w-full relative', $extraClass);

        return <<<HTML
        <div class="{$classes}">
            {$drawerHtml}
            {$appBar}
            <main class="flex-1 w-full flex flex-col">
                {$body}
            </main>
            {$bottomNavHtml}
            {$fabHtml}
        </div>
        HTML;
    }
}
