<?php

declare(strict_types=1);

namespace App\Layouts;

use StackFlow\Core\Request;
use StackFlow\Layout\Layout;
use StackFlow\Page\Page;
use StackFlow\Widgets\Widget;

/**
 * BlankLayout — Minimal canvas layout for auth pages, landing focus pages, and wizards.
 *
 * Provides a centered, clean canvas without top navbar or sidebars.
 *
 * Customization via $page->layoutProps():
 *  - 'center' => true|false (default true)
 *  - 'maxWidth' => 'max-w-md' (default 'max-w-md')
 */
class BlankLayout extends Layout
{
    public function wrap(Widget $content, Request $request, Page $page): Widget
    {
        return new class($content, $request, $page) extends Widget {
            public function __construct(
                private readonly Widget $contentWidget,
                private readonly Request $request,
                private readonly Page $page,
            ) {
                parent::__construct();
            }

            public function render(): string
            {
                $props = $this->page->layoutProps();
                $center = $props['center'] ?? true;
                $maxWidth = $props['maxWidth'] ?? 'max-w-md';
                $contentHtml = $this->contentWidget->render();

                $centerClasses = $center
                    ? 'flex items-center justify-center min-h-screen p-4 sm:p-6'
                    : 'p-4 sm:p-8 min-h-screen';

                return <<<HTML
                <div class="min-h-screen bg-gray-50/80 text-gray-900 {$centerClasses}">
                    <div class="w-full {$maxWidth}">
                        {$contentHtml}
                    </div>
                </div>
HTML;
            }
        };
    }
}
