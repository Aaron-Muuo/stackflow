<?php

declare(strict_types=1);

namespace App\Layouts;

use StackFlow\Core\Request;
use StackFlow\Layout\Layout;
use StackFlow\Page\Page;
use StackFlow\Widgets\Navigation\Navbar;
use StackFlow\Widgets\Widget;

/**
 * AppLayout — Standard application layout with top navbar, container, and footer.
 *
 * Customization via $page->layoutProps():
 *  - 'brand' => 'MyApp'
 *  - 'links' => [['label' => 'Home', 'href' => '/', 'active' => true]]
 *  - 'actions' => Widget|string|array
 *  - 'maxWidth' => 'max-w-7xl'
 */
class AppLayout extends Layout
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
                $brand = $props['brand'] ?? 'Stackflow';
                $maxWidth = $props['maxWidth'] ?? 'max-w-7xl';
                $links = $props['links'] ?? [
                    ['label' => 'Home', 'href' => '/', 'active' => $this->request->path === '/'],
                    ['label' => 'Dashboard', 'href' => '/dashboard', 'active' => str_starts_with($this->request->path, '/dashboard')],
                ];
                $actions = $props['actions'] ?? null;
                $contentHtml = $this->contentWidget->render();

                $navbar = Navbar::make([
                    'brand'   => $brand,
                    'links'   => $links,
                    'actions' => $actions,
                    'sticky'  => true,
                ])->render();

                return <<<HTML
                <div class="min-h-screen flex flex-col bg-white text-gray-900 antialiased">
                    {$navbar}

                    <main class="flex-1 w-full {$maxWidth} mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
                        {$contentHtml}
                    </main>

                    <footer class="border-t border-gray-100 bg-gray-50/50 py-8 px-4 sm:px-6 lg:px-8 text-center text-sm text-gray-500">
                        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4">
                            <div class="flex items-center gap-2">
                                <span class="font-semibold text-gray-900">{$brand}</span>
                                <span>&bull;</span>
                                <span>Powered by Stackflow</span>
                            </div>
                            <div class="flex items-center gap-6 text-xs">
                                <a href="/" class="hover:text-gray-900 transition-colors">Home</a>
                                <a href="https://github.com/Aaron-Muuo/stackflow" target="_blank" class="hover:text-gray-900 transition-colors">GitHub</a>
                            </div>
                        </div>
                    </footer>
                </div>
HTML;
            }
        };
    }
}
