<?php

declare(strict_types=1);

namespace App\Layouts;

use StackFlow\Core\Request;
use StackFlow\Layout\Layout;
use StackFlow\Page\Page;
use StackFlow\Widgets\Widget;

/**
 * AdminLayout — Master portal layout for admin panels and backend dashboards.
 *
 * Provides a responsive desktop/mobile sidebar, top navigation header with user
 * profile and quick actions, content container, and footer.
 *
 * Customization via $page->layoutProps():
 *  - 'brand' => 'Custom Portal'
 *  - 'title' => 'Page Title'
 *  - 'sidebar' => [['label' => '...', 'href' => '...', 'icon' => '...']]
 *  - 'user' => ['name' => 'John Doe', 'role' => 'Admin', 'avatar' => 'JD']
 *  - 'fullWidth' => true|false
 */
class AdminLayout extends Layout
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
                $brand = $props['brand'] ?? 'Stackflow Admin';
                $title = $props['title'] ?? $this->page->title();
                $fullWidth = !empty($props['fullWidth']);
                $contentHtml = $this->contentWidget->render();

                $currentPath = $this->request->path;

                $defaultNav = [
                    ['label' => 'Overview', 'href' => '/admin', 'icon' => 'grid', 'active' => $currentPath === '/admin' || $currentPath === '/admin/'],
                    ['label' => 'Pages', 'href' => '/admin/pages', 'icon' => 'document-text', 'active' => str_starts_with($currentPath, '/admin/pages')],
                    ['label' => 'Users', 'href' => '/admin/users', 'icon' => 'users', 'active' => str_starts_with($currentPath, '/admin/users')],
                    ['label' => 'Database', 'href' => '/admin/database', 'icon' => 'circle-stack', 'active' => str_starts_with($currentPath, '/admin/database')],
                    ['label' => 'Settings', 'href' => '/admin/settings', 'icon' => 'cog-6-tooth', 'active' => str_starts_with($currentPath, '/admin/settings')],
                ];

                $navItems = $props['sidebar'] ?? $defaultNav;
                $user = $props['user'] ?? [
                    'name' => 'Admin User',
                    'email' => 'admin@stackflow.dev',
                    'initials' => 'AD',
                    'role' => 'Administrator'
                ];

                $navLinksHtml = '';
                foreach ($navItems as $item) {
                    $label = htmlspecialchars($item['label'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE);
                    $href = htmlspecialchars($item['href'] ?? '#', ENT_QUOTES | ENT_SUBSTITUTE);
                    $isActive = !empty($item['active']);
                    $badge = isset($item['badge']) ? "<span class=\"ml-auto text-[11px] font-semibold px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-900/60 dark:text-indigo-300\">{$item['badge']}</span>" : '';

                    $linkClasses = $isActive
                        ? 'bg-indigo-600 text-white font-medium shadow-none'
                        : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-800/60';

                    $dotColor = $isActive ? 'bg-white' : 'bg-transparent';

                    $navLinksHtml .= <<<HTML
                    <a href="{$href}" class="flex items-center gap-3 px-3 py-2 rounded-sm text-sm transition-all duration-150 {$linkClasses}">
                        <span class="w-2 h-2 rounded-full {$dotColor}"></span>
                        <span class="truncate">{$label}</span>
                        {$badge}
                    </a>
HTML;
                }

                $maxWClass = $fullWidth ? 'w-full px-4 sm:px-6 lg:px-8' : 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8';

                return <<<HTML
                <div class="min-h-screen bg-gray-50 dark:bg-gray-950 flex flex-col antialiased">
                    <!-- Mobile Sidebar Backdrop -->
                    <div id="admin-mobile-backdrop" class="fixed inset-0 bg-black/50 z-40 hidden md:hidden transition-opacity" onclick="toggleAdminSidebar()"></div>

                    <div class="flex-1 flex">
                        <!-- Desktop & Mobile Sidebar -->
                        <aside id="admin-sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800 flex flex-col transition-transform duration-200 -translate-x-full md:translate-x-0 md:static">
                            <!-- Sidebar Brand -->
                            <div class="h-16 px-6 flex items-center justify-between border-b border-gray-100 dark:border-gray-800/80">
                                <a href="/admin" class="flex items-center gap-2 font-bold text-gray-900 dark:text-white tracking-tight text-base">
                                    <span class="w-7 h-7 rounded-sm bg-indigo-600 text-white flex items-center justify-center text-xs font-mono font-bold shadow-none">&lt;/&gt;</span>
                                    <span>{$brand}</span>
                                </a>
                                <button type="button" class="md:hidden text-gray-500 hover:text-gray-700 dark:hover:text-gray-300" onclick="toggleAdminSidebar()">
                                    ✕
                                </button>
                            </div>

                            <!-- Navigation List -->
                            <nav class="flex-1 px-4 py-5 space-y-1 overflow-y-auto">
                                <div class="px-2 pb-2 text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Navigation</div>
                                {$navLinksHtml}
                            </nav>

                            <!-- User Footer -->
                            <div class="p-4 border-t border-gray-100 dark:border-gray-800/80">
                                <div class="flex items-center gap-3 p-2 rounded-sm bg-gray-50 dark:bg-gray-800/50">
                                    <div class="w-8 h-8 rounded-sm bg-indigo-600 text-white flex items-center justify-center font-bold text-xs uppercase shrink-0">
                                        {$user['initials']}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-semibold text-gray-900 dark:text-white truncate">{$user['name']}</p>
                                        <p class="text-[11px] text-gray-500 dark:text-gray-400 truncate">{$user['role']}</p>
                                    </div>
                                </div>
                            </div>
                        </aside>

                        <!-- Main Content Wrapper -->
                        <div class="flex-1 flex flex-col min-w-0">
                            <!-- Top Navbar Header -->
                            <header class="h-16 bg-white/80 dark:bg-gray-900/80 backdrop-blur-md border-b border-gray-200 dark:border-gray-800 sticky top-0 z-30 flex items-center justify-between px-4 sm:px-6">
                                <div class="flex items-center gap-3">
                                    <button type="button" onclick="toggleAdminSidebar()" class="md:hidden p-2 rounded-sm text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                                    </button>
                                    <h1 class="text-base font-semibold text-gray-900 dark:text-white truncate">{$title}</h1>
                                </div>

                                <div class="flex items-center gap-3">
                                    <a href="/" target="_blank" class="text-xs text-gray-500 hover:text-gray-900 dark:hover:text-white hidden sm:inline-flex items-center gap-1">
                                        View Site ↗
                                    </a>
                                    <div class="w-px h-4 bg-gray-200 dark:bg-gray-700 hidden sm:block"></div>
                                    <button type="button" onclick="var isDark = document.documentElement.classList.toggle('dark'); localStorage.setItem('stackflow-theme', isDark ? 'dark' : 'light');" class="p-2 rounded-sm text-gray-500 hover:bg-gray-100 transition-colors" title="Toggle theme">
                                        🌓
                                    </button>
                                </div>
                            </header>

                            <!-- Page Body -->
                            <main class="flex-1 py-6 sm:py-8">
                                <div class="{$maxWClass}">
                                    {$contentHtml}
                                </div>
                            </main>

                            <!-- Footer -->
                            <footer class="border-t border-gray-200/60 dark:border-gray-800/60 py-4 px-6 text-center text-xs text-gray-400 dark:text-gray-500">
                                Stackflow Framework &bull; Admin Portal Shell
                            </footer>
                        </div>
                    </div>
                </div>

                <script>
                function toggleAdminSidebar() {
                    var sidebar = document.getElementById('admin-sidebar');
                    var backdrop = document.getElementById('admin-mobile-backdrop');
                    if (!sidebar || !backdrop) return;
                    var isClosed = sidebar.classList.contains('-translate-x-full');
                    if (isClosed) {
                        sidebar.classList.remove('-translate-x-full');
                        backdrop.classList.remove('hidden');
                    } else {
                        sidebar.classList.add('-translate-x-full');
                        backdrop.classList.add('hidden');
                    }
                }
                </script>
HTML;
            }
        };
    }
}
