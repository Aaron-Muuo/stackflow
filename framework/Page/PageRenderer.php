<?php

declare(strict_types=1);

namespace StackFlow\Page;

use StackFlow\Core\Request;
use StackFlow\Theme\ThemeManager;

/**
 * Renders a Page into a complete HTML document string.
 *
 * PageRenderer orchestrates the full HTML output pipeline:
 *  1. Calls $page->build($request) to obtain the root Widget.
 *  2. Calls Widget::render() to get the inner HTML.
 *  3. Wraps the output in a full HTML5 document with:
 *       - Charset / viewport meta tags from the page
 *       - Active theme styles (Google Fonts, Tailwind config, CSS vars)
 *       - Tailwind CDN (development) or built CSS (production)
 *       - Dark-mode detection script
 *       - Any additional per-page scripts / styles
 */
class PageRenderer
{
    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Render a Page into a complete HTML document.
     *
     * @param Page    $page    The page to render.
     * @param Request $request The current HTTP request.
     * @return string|\StackFlow\Core\Response A complete HTML document or direct Response.
     */
    public function render(Page $page, Request $request): string|\StackFlow\Core\Response
    {
        // 1. Build the widget tree.
        $rootWidget = $page->build($request);

        // If build() returned a Response (e.g. redirect or json), return immediately
        if ($rootWidget instanceof \StackFlow\Core\Response) {
            return $rootWidget;
        }

        // 2. Wrap in master layout if configured by the page
        $rootWidget = $this->applyLayout($page, $rootWidget, $request);

        // 3. Render the widget tree to HTML.
        $bodyHtml = $rootWidget->render();

        // 3. Gather page metadata.
        $title     = htmlspecialchars($page->title(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $themeMode = $page->theme();   // 'light' | 'dark' | 'auto'
        $metaTags  = $this->buildMetaTags($page->meta());
        $linkTags  = $this->buildLinkTags($page->styles());
        $scriptTags = $this->buildScriptTags($page->scripts());

        // 4. Active theme assets.
        $themeHead = ThemeManager::renderHead();

        // 5. Assemble and return the full document.
        return $this->assembleDocument(
            title: $title,
            themeMode: $themeMode,
            metaTags: $metaTags,
            themeHead: $themeHead,
            linkTags: $linkTags,
            scriptTags: $scriptTags,
            bodyHtml: $bodyHtml,
        );
    }

    // -------------------------------------------------------------------------
    // Document assembly
    // -------------------------------------------------------------------------

    /**
     * Assemble the final HTML document string.
     */
    private function assembleDocument(
        string $title,
        string $themeMode,
        string $metaTags,
        string $themeHead,
        string $linkTags,
        string $scriptTags,
        string $bodyHtml,
    ): string {
        $darkModeScript = $this->buildDarkModeScript($themeMode);
        $tailwindCdn    = $this->buildTailwindCdn();

        $debugToolbar = '';
        if (filter_var(\StackFlow\Core\Env::get('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN)) {
            $debugToolbar = \StackFlow\Debug\Debugger::renderToolbar();
        }

        $bodyClass = match ($themeMode) {
            'dark'  => 'bg-[#050608] text-white min-h-screen font-sans antialiased selection:bg-white selection:text-black overflow-x-hidden',
            default => 'bg-white text-gray-900 min-h-screen font-sans antialiased selection:bg-black selection:text-white overflow-x-hidden',
        };

        $clientRuntime = $this->buildClientRuntime();
        $flashToast = $this->buildFlashToast();

        return <<<HTML
        <!DOCTYPE html>
        <html lang="en" class="">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
        {$metaTags}
            <title>{$title}</title>

            {$darkModeScript}

        {$tailwindCdn}
        {$themeHead}
        {$linkTags}
        </head>
        <body class="{$bodyClass}">

        {$bodyHtml}

        {$scriptTags}
        {$clientRuntime}
        {$flashToast}
        {$debugToolbar}
        </body>
        </html>
        HTML;
    }

    // -------------------------------------------------------------------------
    // Dark-mode script
    // -------------------------------------------------------------------------

    /**
     * Inline script that applies the correct dark-mode class before first paint,
     * preventing a flash of unstyled content (FOUC). Defaults to light mode for all pages.
     */
    private function buildDarkModeScript(string $themeMode): string
    {
        $modeJson = json_encode($themeMode);

        return <<<HTML
            <script>
                (function () {
                    var mode = {$modeJson};
                    var root = document.documentElement;
                    var stored = localStorage.getItem('stackflow-theme');
                    // StackFlow defaults to light theme for all pages and components
                    if (mode === 'dark') {
                        root.classList.add('dark');
                    } else if (mode === 'light' || !stored || stored === 'dark') {
                        // Reset stale dark preferences to light by default
                        root.classList.remove('dark');
                        if (stored === 'dark' && mode === 'light') {
                            localStorage.setItem('stackflow-theme', 'light');
                        }
                    } else {
                        root.classList.remove('dark');
                    }
                })();
            </script>
        HTML;
    }

    // -------------------------------------------------------------------------
    // Tailwind CDN
    // -------------------------------------------------------------------------

    /**
     * Returns the Tailwind Play CDN <script> tag.
     *
     * In a production build this should be replaced by a compiled CSS file.
     * The ThemeManager injects the tailwind.config block separately.
     */
    private function buildTailwindCdn(): string
    {
        return '<script src="https://cdn.tailwindcss.com"></script>';
    }

    // -------------------------------------------------------------------------
    // Tag builders
    // -------------------------------------------------------------------------

    /**
     * Convert an array of meta-attribute maps into <meta> tag HTML.
     *
     * @param array<int, array<string, string>> $metas
     */
    private function buildMetaTags(array $metas): string
    {
        $lines = [];
        foreach ($metas as $meta) {
            // Skip charset — already hard-coded in template.
            if (isset($meta['charset'])) {
                continue;
            }
            // Skip viewport — already hard-coded in template.
            if (isset($meta['name']) && $meta['name'] === 'viewport') {
                continue;
            }

            $attrs = '';
            foreach ($meta as $attr => $value) {
                $attrs .= ' ' . htmlspecialchars($attr, ENT_QUOTES, 'UTF-8')
                       . '="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
            }
            $lines[] = "    <meta{$attrs}>";
        }

        return implode("\n", $lines);
    }

    /**
     * Build <link rel="stylesheet"> tags from an array of URLs.
     *
     * @param array<int, string> $urls
     */
    private function buildLinkTags(array $urls): string
    {
        $tags = [];
        foreach ($urls as $url) {
            $safe   = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
            $tags[] = "    <link rel=\"stylesheet\" href=\"{$safe}\">";
        }

        return implode("\n", $tags);
    }

    /**
     * Build <script src="..."> tags from an array of URLs.
     *
     * @param array<int, string> $urls
     */
    private function buildScriptTags(array $urls): string
    {
        $tags = [];
        foreach ($urls as $url) {
            $safe   = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
            $tags[] = "    <script src=\"{$safe}\"></script>";
        }

        return implode("\n", $tags);
    }

    /**
     * Build the global StackFlow client runtime (Toasts, Modals, Confirmations, AJAX Form Submissions).
     */
    private function buildClientRuntime(): string
    {
        $toastScript = \StackFlow\Widgets\Feedback\Toast::script();

        return <<<HTML
        {$toastScript}

        <!-- StackFlow Confirmation Modal DOM -->
        <div id="sf-confirm-modal" class="hidden fixed inset-0 z-[9998] overflow-y-auto" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-xs transition-opacity" onclick="window.StackFlow.confirmClose()"></div>
            <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-sm bg-white dark:bg-gray-900 text-left shadow-none transition-all sm:my-8 sm:w-full sm:max-w-md p-6 border border-gray-200 dark:border-gray-800">
                    <div class="flex items-start gap-4">
                        <div id="sf-confirm-icon" class="w-9 h-9 rounded-sm flex items-center justify-center shrink-0 text-rose-500 bg-rose-50 dark:bg-rose-950/60">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 id="sf-confirm-title" class="text-base font-bold text-gray-900 dark:text-white leading-6">Are you sure?</h3>
                            <p id="sf-confirm-msg" class="mt-2 text-sm text-gray-500 dark:text-gray-400 leading-relaxed">This action cannot be undone.</p>
                        </div>
                    </div>
                    <div class="mt-6 flex items-center justify-end gap-3 pt-3 border-t border-gray-100 dark:border-gray-800">
                        <button type="button" id="sf-confirm-cancel-btn" onclick="window.StackFlow.confirmClose()" class="px-4 py-2 text-sm font-medium rounded-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">Cancel</button>
                        <button type="button" id="sf-confirm-ok-btn" class="px-4 py-2 text-sm font-semibold rounded-sm shadow-none transition-colors cursor-pointer bg-rose-600 hover:bg-rose-700 text-white">Confirm</button>
                    </div>
                </div>
            </div>
        </div>

        <script>
        (function() {
            window.StackFlow = window.StackFlow || {};

            // 1. Modal Manager
            window.StackFlow.modal = {
                open: function(id) {
                    var el = document.getElementById(id);
                    if (el) {
                        el.classList.remove('hidden');
                        document.body.classList.add('overflow-hidden');
                    }
                },
                close: function(id) {
                    var el = document.getElementById(id);
                    if (el) {
                        el.classList.add('hidden');
                        document.body.classList.remove('overflow-hidden');
                    }
                }
            };

            // ESC key to dismiss modals
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    document.querySelectorAll('[role="dialog"]:not(.hidden)').forEach(function(m) {
                        m.classList.add('hidden');
                    });
                    document.body.classList.remove('overflow-hidden');
                }
            });

            // 2. Interactive Confirmation Box
            var confirmCallback = null;
            window.StackFlow.confirm = function(opts, onConfirm) {
                if (typeof opts === 'string') {
                    opts = { message: opts };
                }
                opts = opts || {};
                confirmCallback = onConfirm || opts.onConfirm || null;

                var modal = document.getElementById('sf-confirm-modal');
                if (!modal) return;

                document.getElementById('sf-confirm-title').innerText = opts.title || 'Are you sure?';
                document.getElementById('sf-confirm-msg').innerText = opts.message || 'This action cannot be undone.';
                document.getElementById('sf-confirm-ok-btn').innerText = opts.confirmText || 'Confirm';
                document.getElementById('sf-confirm-cancel-btn').innerText = opts.cancelText || 'Cancel';

                var okBtn = document.getElementById('sf-confirm-ok-btn');
                var variant = opts.variant || 'danger';
                okBtn.className = 'px-4 py-2 text-sm font-semibold rounded-lg shadow-sm transition-colors cursor-pointer ' +
                    (variant === 'primary' ? 'bg-indigo-600 hover:bg-indigo-700 text-white' :
                     variant === 'warning' ? 'bg-amber-600 hover:bg-amber-700 text-white' :
                     'bg-rose-600 hover:bg-rose-700 text-white');

                okBtn.onclick = function() {
                    window.StackFlow.confirmClose();
                    if (typeof confirmCallback === 'function') confirmCallback();
                };

                modal.classList.remove('hidden');
            };

            window.StackFlow.confirmClose = function() {
                var modal = document.getElementById('sf-confirm-modal');
                if (modal) modal.classList.add('hidden');
                confirmCallback = null;
            };

            // Auto-bind data-sf-confirm attributes
            document.addEventListener('click', function(e) {
                var target = e.target.closest('[data-sf-confirm]');
                if (!target) return;
                e.preventDefault();

                var msg = target.getAttribute('data-sf-confirm');
                var title = target.getAttribute('data-sf-confirm-title') || 'Confirm Action';
                var form = target.closest('form');

                window.StackFlow.confirm({ title: title, message: msg }, function() {
                    if (target.tagName === 'A' && target.href) {
                        window.location.href = target.href;
                    } else if (form) {
                        form.submit();
                    }
                });
            });

            // Auto-bind modal triggers: data-sf-modal-open / data-sf-modal-close
            document.addEventListener('click', function(e) {
                var openBtn = e.target.closest('[data-sf-modal-open]');
                if (openBtn) {
                    var targetId = openBtn.getAttribute('data-sf-modal-open');
                    window.StackFlow.modal.open(targetId);
                    return;
                }
                var closeBtn = e.target.closest('[data-sf-modal-close]');
                if (closeBtn) {
                    var closeId = closeBtn.getAttribute('data-sf-modal-close');
                    window.StackFlow.modal.close(closeId);
                    return;
                }
            });

            // 3. Tab switching helper
            window.sfSwitchTab = function(containerId, activeTabId) {
                var container = document.getElementById(containerId);
                if (!container) return;
                var buttons = container.querySelectorAll('.sf-tab-btn');
                var panels = container.querySelectorAll('.sf-tab-panel');

                buttons.forEach(function(btn) {
                    var isMatch = btn.getAttribute('data-tab-id') === activeTabId;
                    var variant = btn.getAttribute('data-variant') || 'underline';

                    if (variant === 'pills') {
                        btn.classList.toggle('bg-indigo-600', isMatch);
                        btn.classList.toggle('text-white', isMatch);
                        btn.classList.toggle('shadow-sm', isMatch);
                    } else if (variant === 'segmented') {
                        btn.classList.toggle('bg-white', isMatch);
                        btn.classList.toggle('dark:bg-gray-800', isMatch);
                        btn.classList.toggle('text-gray-950', isMatch);
                        btn.classList.toggle('dark:text-white', isMatch);
                        btn.classList.toggle('shadow-sm', isMatch);
                    } else {
                        btn.classList.toggle('border-indigo-600', isMatch);
                        btn.classList.toggle('text-indigo-600', isMatch);
                        btn.classList.toggle('dark:border-indigo-400', isMatch);
                        btn.classList.toggle('dark:text-indigo-400', isMatch);
                        btn.classList.toggle('border-transparent', !isMatch);
                    }
                });

                panels.forEach(function(panel) {
                    panel.style.display = panel.id === 'panel-' + containerId + '-' + activeTabId ? '' : 'none';
                });
            };

            // 4. Universal AJAX Form Submissions
            document.addEventListener('submit', function(e) {
                var form = e.target;
                if (!form.hasAttribute('data-sf-ajax')) return;

                e.preventDefault();

                var submitBtn = form.querySelector('button[type="submit"]') || form.querySelector('input[type="submit"]');
                var originalBtnHtml = submitBtn ? submitBtn.innerHTML : '';

                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<svg class="animate-spin -ml-1 mr-2 h-4 w-4 inline" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> <span>Processing...</span>';
                }

                // Reset existing error borders
                form.querySelectorAll('.border-rose-500').forEach(function(el) {
                    el.classList.remove('border-rose-500');
                });

                var formData = new FormData(form);
                var action = form.getAttribute('action') || window.location.href;
                var method = (form.getAttribute('method') || 'POST').toUpperCase();

                fetch(action, {
                    method: method,
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-StackFlow-Ajax': '1',
                        'Accept': 'application/json'
                    }
                })
                .then(function(res) {
                    var isJson = (res.headers.get('content-type') || '').includes('application/json');
                    if (isJson) return res.json();
                    return res.text().then(function(t) { return { status: res.ok ? 'ok' : 'error', html: t }; });
                })
                .then(function(data) {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalBtnHtml;
                    }

                    // Toast notification
                    if (data.toast && window.StackFlow.toast) {
                        window.StackFlow.toast.show(data.toast);
                    } else if (data.message && window.StackFlow.toast) {
                        window.StackFlow.toast.show({
                            message: data.message,
                            variant: data.status === 'error' ? 'danger' : 'success'
                        });
                    }

                    // Redirect
                    if (data.status === 'redirect' || data.redirect) {
                        var targetUrl = data.redirect || data.url;
                        var delay = data.toast ? 600 : 50;
                        setTimeout(function() {
                            window.location.href = targetUrl;
                        }, delay);
                        return;
                    }

                    // Form validation errors
                    if (data.errors) {
                        var firstMsg = '';
                        for (var field in data.errors) {
                            var fieldInput = form.querySelector('[name="' + field + '"]');
                            if (fieldInput) fieldInput.classList.add('border-rose-500');
                            if (!firstMsg && data.errors[field][0]) {
                                firstMsg = data.errors[field][0];
                            }
                        }
                        if (firstMsg && window.StackFlow.toast && !data.toast && !data.message) {
                            window.StackFlow.toast.error(firstMsg);
                        }
                    }
                })
                .catch(function(err) {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalBtnHtml;
                    }
                    if (window.StackFlow.toast) {
                        window.StackFlow.toast.error('Network request failed. Please try again.');
                    }
                });
            });
        })();
        </script>
        HTML;
    }

    /**
     * Playback any server-side flash messages via StackFlow Toast.
     */
    private function buildFlashToast(): string
    {
        $flashSuccess = \StackFlow\Core\Session::getFlash('success');
        $flashError   = \StackFlow\Core\Session::getFlash('error') ?? \StackFlow\Core\Session::getFlash('danger');
        $flashWarning = \StackFlow\Core\Session::getFlash('warning');
        $flashInfo    = \StackFlow\Core\Session::getFlash('info');

        $js = '';
        if ($flashSuccess) {
            $msg = json_encode($flashSuccess);
            $js = "window.StackFlow.toast.success({$msg});";
        } elseif ($flashError) {
            $msg = json_encode($flashError);
            $js = "window.StackFlow.toast.error({$msg});";
        } elseif ($flashWarning) {
            $msg = json_encode($flashWarning);
            $js = "window.StackFlow.toast.warning({$msg});";
        } elseif ($flashInfo) {
            $msg = json_encode($flashInfo);
            $js = "window.StackFlow.toast.info({$msg});";
        }

        if ($js === '') {
            return '';
        }

        return "<script>document.addEventListener('DOMContentLoaded', function() { if (window.StackFlow && window.StackFlow.toast) { {$js} } });</script>";
    }

    /**
     * Wrap the page content in its parent layout if configured.
     *
     * @param Page $page
     * @param \StackFlow\Widgets\Widget $content
     * @param Request $request
     * @return \StackFlow\Widgets\Widget
     */
    private function applyLayout(Page $page, \StackFlow\Widgets\Widget $content, Request $request): \StackFlow\Widgets\Widget
    {
        $layoutClass = $page->layout();
        if ($layoutClass === null || $layoutClass === '') {
            return $content;
        }

        if (!class_exists($layoutClass)) {
            throw new \RuntimeException("Layout [{$layoutClass}] configured on " . get_class($page) . " was not found.");
        }

        $layout = new $layoutClass();

        if ($layout instanceof \StackFlow\Layout\Layout) {
            return $layout->wrap($content, $request, $page);
        }

        if ($layout instanceof \StackFlow\Widgets\Widget) {
            return $layoutClass::make(array_merge(
                $page->layoutProps(),
                [
                    'child'   => $content,
                    'body'    => $content,
                    'content' => $content,
                    'page'    => $page,
                    'request' => $request,
                ]
            ));
        }

        throw new \RuntimeException("Layout [{$layoutClass}] must extend StackFlow\\Layout\\Layout or StackFlow\\Widgets\\Widget.");
    }
}
