<?php

declare(strict_types=1);

namespace App\Layouts;

use StackFlow\Core\Request;
use StackFlow\Layout\Layout;
use StackFlow\Page\Page;
use StackFlow\Widgets\Widget;

/**
 * AuthLayout — Minimal / Blank Canvas Layout
 */
class AuthLayout extends Layout
{
    public function wrap(Widget $content, Request $request, Page $page): Widget
    {
        return new class($content) extends Widget {
            public function __construct(private readonly Widget $contentWidget)
            {
                parent::__construct();
            }

            public function render(): string
            {
                $html = $this->contentWidget->render();
                return <<<HTML
                <div class="min-h-screen bg-gray-50 text-gray-900 flex items-center justify-center p-4">
                    <div class="w-full max-w-md">{$html}</div>
                </div>
HTML;
            }
        };
    }
}
