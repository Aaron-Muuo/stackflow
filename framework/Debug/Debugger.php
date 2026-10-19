<?php

declare(strict_types=1);

namespace StackFlow\Debug;

/**
 * StackFlow application debugger.
 *
 * Provides development-time utilities:
 *  - Structured `log()` for arbitrary values
 *  - `logQuery()` for database query profiling
 *  - `dd()` / `dump()` for quick inspection
 *  - `renderToolbar()` for the bottom-of-page debug toolbar
 *
 * All state is held in static properties so the debugger is available
 * without container resolution throughout the request lifecycle.
 *
 * Usage:
 * ```php
 * Debugger::boot();
 * Debugger::log($user, 'Resolved user');
 * Debugger::logQuery('SELECT * FROM users WHERE id = ?', [1], 2.4);
 * Debugger::dd($someValue);
 * // In your layout, just before </body>:
 * echo Debugger::renderToolbar();
 * ```
 */
class Debugger
{
    // -------------------------------------------------------------------------
    // State
    // -------------------------------------------------------------------------

    /** @var array<int, array{label: string, value: mixed, time: float}> */
    private static array $logs = [];

    /** @var array<int, array{sql: string, bindings: array<mixed>, time: float}> */
    private static array $queries = [];

    /** @var float Unix timestamp (with microseconds) when boot() was called. */
    private static float $startTime = 0.0;

    /** @var bool Whether the debugger has been booted. */
    private static bool $booted = false;

    // -------------------------------------------------------------------------
    // Bootstrap
    // -------------------------------------------------------------------------

    /**
     * Initialise the debugger and record the request start time.
     *
     * Should be called as early as possible in the bootstrap sequence,
     * ideally before any other framework code runs.
     */
    public static function boot(): void
    {
        if (self::$booted) {
            return;
        }

        self::$startTime = defined('LARAVEL_START')
            ? \LARAVEL_START                           // integrate with other frameworks
            : (isset($_SERVER['REQUEST_TIME_FLOAT'])
                ? (float) $_SERVER['REQUEST_TIME_FLOAT']
                : microtime(true));

        self::$booted = true;
    }

    // -------------------------------------------------------------------------
    // Logging
    // -------------------------------------------------------------------------

    /**
     * Log any value with an optional human-readable label.
     *
     * Logged entries are visible in the debug toolbar overlay.
     *
     * @param mixed  $value The value to log.
     * @param string $label Optional label for the log entry.
     */
    public static function log(mixed $value, string $label = ''): void
    {
        self::$logs[] = [
            'label' => $label,
            'value' => $value,
            'time'  => microtime(true),
        ];
    }

    /**
     * Record a database query for the toolbar query panel.
     *
     * @param string        $sql      The raw SQL string (with ? placeholders).
     * @param array<mixed>  $bindings Bound parameter values.
     * @param float         $time     Query execution time in milliseconds.
     */
    public static function logQuery(string $sql, array $bindings, float $time): void
    {
        self::$queries[] = [
            'sql'      => $sql,
            'bindings' => $bindings,
            'time'     => $time,
        ];
    }

    // -------------------------------------------------------------------------
    // Dump helpers
    // -------------------------------------------------------------------------

    /**
     * Dump one or more values and terminate the request immediately.
     *
     * Output is formatted with `var_dump` inside styled `<pre>` blocks.
     * This method **never returns** (`never` return type).
     *
     * @param mixed ...$values Values to dump.
     * @return never
     */
    public static function dd(mixed ...$values): never
    {
        self::dump(...$values);
        exit(1);
    }

    /**
     * Dump one or more values without terminating the request.
     *
     * @param mixed ...$values Values to dump.
     */
    public static function dump(mixed ...$values): void
    {
        $isHttp = PHP_SAPI !== 'cli';

        if ($isHttp) {
            echo self::dumpStyles();
        }

        foreach ($values as $value) {
            if ($isHttp) {
                echo '<div class="sf-dump">';
                echo '<div class="sf-dump-type">' . htmlspecialchars(gettype($value), ENT_QUOTES, 'UTF-8') . '</div>';
                echo '<pre class="sf-dump-pre">';
                ob_start();
                var_dump($value);
                $raw = (string) ob_get_clean();
                echo htmlspecialchars($raw, ENT_QUOTES, 'UTF-8');
                echo '</pre></div>';
            } else {
                var_dump($value);
            }
        }
    }

    /**
     * Inline CSS for `dump()` / `dd()` output blocks.
     *
     * Emitted only once per request via a static flag.
     */
    private static function dumpStyles(): string
    {
        static $printed = false;

        if ($printed) {
            return '';
        }

        $printed = true;

        return <<<'HTML'
        <style>
            .sf-dump {
                font-family: 'JetBrains Mono', 'Fira Code', ui-monospace, monospace;
                background: #1e2030;
                border: 1px solid #3b4261;
                border-left: 4px solid #7c3aed;
                border-radius: 6px;
                margin: 12px 0;
                overflow: hidden;
                max-width: 100%;
            }
            .sf-dump-type {
                background: #16172b;
                color: #a78bfa;
                font-size: 11px;
                font-weight: 600;
                padding: 4px 12px;
                text-transform: uppercase;
                letter-spacing: .05em;
            }
            .sf-dump-pre {
                color: #c0caf5;
                font-size: 13px;
                line-height: 1.6;
                margin: 0;
                overflow-x: auto;
                padding: 12px 16px;
                white-space: pre;
            }
        </style>
        HTML;
    }

    // -------------------------------------------------------------------------
    // Debug toolbar
    // -------------------------------------------------------------------------

    /**
     * Render the StackFlow debug toolbar HTML.
     *
     * Returns an empty string when not in debug mode (no `APP_DEBUG` env var).
     *
     * The toolbar is a fixed bar at the bottom of the viewport. It shows:
     *  - StackFlow logo / version badge
     *  - Execution time (ms)
     *  - Peak memory usage (MB)
     *  - Query count (click to expand)
     *  - Log count (click to expand)
     *
     * Clicking a pill opens a detail overlay panel.
     *
     * @return string Self-contained HTML + CSS + JS toolbar fragment.
     */
    public static function renderToolbar(): string
    {
        $execTime   = round((microtime(true) - self::$startTime) * 1000, 2);
        $peakMemory = round(memory_get_peak_usage(true) / 1024 / 1024, 2);
        $queryCount = count(self::$queries);
        $logCount   = count(self::$logs);

        $queriesJson = json_encode(self::$queries, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $logsJson    = json_encode(self::$logs,    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $queryBadgeClass = $queryCount > 50 ? 'sf-tb-pill--warn' : 'sf-tb-pill--default';

        return <<<HTML
        <!-- Stackflow Debug Toolbar (Collapsible Bottom-Right Tab) -->
        <div id="sf-debug-root">
            <style>
                #sf-debug-root {
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                    font-size: 12px;
                    z-index: 99999;
                    user-select: none;
                    -webkit-font-smoothing: antialiased;
                }

                /* Collapsed Tab: Neat tab sticking to bottom-right edge */
                #sf-debug-tab {
                    position: fixed;
                    bottom: 0;
                    right: 24px;
                    height: 28px;
                    background: #0f172a;
                    border: 1px solid #334155;
                    border-bottom: none;
                    border-top-left-radius: 6px;
                    border-top-right-radius: 6px;
                    padding: 0 10px;
                    display: flex;
                    align-items: center;
                    gap: 6px;
                    cursor: pointer;
                    color: #94a3b8;
                    box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.18);
                    transition: all .15s ease;
                    z-index: 99999;
                }
                #sf-debug-tab:hover {
                    background: #1e293b;
                    color: #f8fafc;
                    transform: translateY(-1px);
                }
                .sf-bug-icon {
                    width: 14px;
                    height: 14px;
                    flex-shrink: 0;
                    stroke: #818cf8;
                }
                .sf-tab-brand {
                    color: #818cf8;
                    font-weight: 600;
                    display: flex;
                    align-items: center;
                    gap: 5px;
                }
                .sf-tab-stat {
                    color: #64748b;
                    font-size: 11px;
                }
                .sf-tab-icon {
                    width: 12px;
                    height: 12px;
                    stroke: currentColor;
                }

                /* Expanded Toolbar: Docked on bottom right */
                #sf-toolbar {
                    position: fixed;
                    bottom: 0;
                    right: 24px;
                    height: 36px;
                    background: #0f172a;
                    border: 1px solid #334155;
                    border-bottom: none;
                    border-top-left-radius: 8px;
                    border-top-right-radius: 8px;
                    padding: 0 8px;
                    display: none;
                    align-items: center;
                    gap: 2px;
                    color: #e2e8f0;
                    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.25);
                    z-index: 99999;
                }
                #sf-toolbar.sf-open {
                    display: flex;
                }

                .sf-tb-brand {
                    color: #818cf8;
                    font-weight: 600;
                    letter-spacing: -.01em;
                    padding: 0 8px 0 4px;
                    border-right: 1px solid #1e293b;
                    margin-right: 4px;
                    display: flex;
                    align-items: center;
                    gap: 5px;
                }
                .sf-tb-pill {
                    display: flex;
                    align-items: center;
                    gap: 5px;
                    padding: 3px 8px;
                    border-radius: 4px;
                    color: #94a3b8;
                    font-size: 11px;
                }
                .sf-tb-btn {
                    cursor: pointer;
                    transition: background .15s, color .15s;
                }
                .sf-tb-btn:hover,
                .sf-tb-btn--active {
                    background: #1e293b;
                    color: #e2e8f0;
                }
                .sf-tb-pill--warn { color: #fbbf24; }
                .sf-tb-pill--warn:hover { background: #292524; }
                .sf-tb-pill svg { width: 12px; height: 12px; flex-shrink: 0; }
                .sf-tb-badge {
                    background: #312e81;
                    color: #c7d2fe;
                    border-radius: 9999px;
                    padding: 0 5px;
                    font-size: 10px;
                    font-weight: 700;
                    min-width: 16px;
                    text-align: center;
                }
                .sf-tb-sep {
                    width: 1px;
                    height: 18px;
                    background: #1e293b;
                    margin: 0 2px;
                }
                .sf-tb-close {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    width: 22px;
                    height: 22px;
                    border-radius: 4px;
                    cursor: pointer;
                    color: #64748b;
                    transition: background .15s, color .15s;
                    margin-left: 4px;
                }
                .sf-tb-close:hover {
                    background: #1e293b;
                    color: #f1f5f9;
                }

                /* Overlay Drawer */
                #sf-overlay {
                    display: none;
                    position: fixed;
                    bottom: 42px;
                    right: 24px;
                    width: min(720px, calc(100vw - 48px));
                    max-height: 60vh;
                    background: #0f172a;
                    border: 1px solid #334155;
                    border-radius: 8px;
                    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5), 0 8px 10px -6px rgba(0, 0, 0, 0.5);
                    z-index: 99998;
                    overflow-y: auto;
                    padding: 16px;
                }
                #sf-overlay.sf-overlay--open {
                    display: block;
                }
                .sf-panel-header {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    margin-bottom: 12px;
                    padding-bottom: 8px;
                    border-bottom: 1px solid #1e293b;
                }
                .sf-panel-title {
                    color: #818cf8;
                    font-weight: 600;
                    font-size: 11px;
                    text-transform: uppercase;
                    letter-spacing: .06em;
                }
                .sf-panel-close {
                    color: #64748b;
                    cursor: pointer;
                    padding: 2px 6px;
                    border-radius: 4px;
                    font-size: 12px;
                }
                .sf-panel-close:hover {
                    color: #fff;
                    background: #1e293b;
                }
                .sf-query-row, .sf-log-row {
                    border-bottom: 1px solid #1e293b;
                    padding: 8px 0;
                    display: grid;
                    gap: 4px;
                }
                .sf-query-row:last-child, .sf-log-row:last-child { border: none; }
                .sf-sql { color: #93c5fd; font-family: ui-monospace, Menlo, Consolas, monospace; font-size: 12px; white-space: pre-wrap; word-break: break-all; }
                .sf-time { color: #6b7280; font-size: 11px; }
                .sf-log-label { color: #a78bfa; font-weight: 600; font-size: 11px; }
                .sf-log-value { color: #c0caf5; font-family: ui-monospace, Menlo, Consolas, monospace; font-size: 12px; white-space: pre-wrap; word-break: break-all; }
                .sf-empty { color: #4b5563; font-style: italic; }
            </style>

            <!-- Collapsed Tab -->
            <div id="sf-debug-tab" onclick="sfToggleToolbar(true)" title="Open Stackflow Debug Bar">
                <span class="sf-tab-brand">
                    <svg class="sf-bug-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect width="8" height="14" x="8" y="6" rx="4"/>
                        <path d="m19 7-3 2"/>
                        <path d="m5 7 3 2"/>
                        <path d="m19 19-3-2"/>
                        <path d="m5 19 3-2"/>
                        <path d="M20 13h-4"/>
                        <path d="M4 13h4"/>
                        <path d="m10 4 1 2"/>
                        <path d="m14 4-1 2"/>
                    </svg>
                    <span>Stackflow</span>
                </span>
                <span class="sf-tab-stat">{$execTime}ms</span>
                <svg class="sf-tab-icon" viewBox="0 0 24 24" fill="none" stroke-width="2.5">
                    <polyline points="18 15 12 9 6 15"></polyline>
                </svg>
            </div>

            <!-- Expanded Toolbar -->
            <div id="sf-toolbar">
                <span class="sf-tb-brand">
                    <svg class="sf-bug-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect width="8" height="14" x="8" y="6" rx="4"/>
                        <path d="m19 7-3 2"/>
                        <path d="m5 7 3 2"/>
                        <path d="m19 19-3-2"/>
                        <path d="m5 19 3-2"/>
                        <path d="M20 13h-4"/>
                        <path d="M4 13h4"/>
                        <path d="m10 4 1 2"/>
                        <path d="m14 4-1 2"/>
                    </svg>
                    <span>Stackflow</span>
                </span>

                <div class="sf-tb-pill" title="Execution time">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                    </svg>
                    {$execTime} ms
                </div>

                <div class="sf-tb-sep"></div>

                <div class="sf-tb-pill" title="Peak memory">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
                    </svg>
                    {$peakMemory} MB
                </div>

                <div class="sf-tb-sep"></div>

                <div class="sf-tb-pill sf-tb-btn {$queryBadgeClass}" onclick="sfTogglePanel('queries')" title="Database queries">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <ellipse cx="12" cy="5" rx="9" ry="3"/>
                        <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/>
                        <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/>
                    </svg>
                    Queries <span class="sf-tb-badge">{$queryCount}</span>
                </div>

                <div class="sf-tb-sep"></div>

                <div class="sf-tb-pill sf-tb-btn" onclick="sfTogglePanel('logs')" title="Debug logs">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                        <polyline points="10 9 9 9 8 9"/>
                    </svg>
                    Logs <span class="sf-tb-badge">{$logCount}</span>
                </div>

                <div class="sf-tb-sep"></div>

                <!-- Minimize / Collapse button -->
                <div class="sf-tb-close" onclick="sfToggleToolbar(false)" title="Collapse debug bar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width: 12px; height: 12px;">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </div>
            </div>

            <!-- Detail Overlay Drawer -->
            <div id="sf-overlay">
                <div class="sf-panel-header">
                    <div id="sf-panel-header-title" class="sf-panel-title">Details</div>
                    <div class="sf-panel-close" onclick="sfTogglePanel('')" title="Close panel">✕</div>
                </div>
                <div id="sf-panel-queries" style="display:none">
                    <div id="sf-queries-content"></div>
                </div>
                <div id="sf-panel-logs" style="display:none">
                    <div id="sf-logs-content"></div>
                </div>
            </div>
        </div>

        <script>
            (function () {
                var queries = {$queriesJson};
                var logs    = {$logsJson};
                var activePanel = '';

                function renderQueries() {
                    var el = document.getElementById('sf-queries-content');
                    if (!queries.length) { el.innerHTML = '<p class="sf-empty">No queries recorded.</p>'; return; }
                    el.innerHTML = queries.map(function(q, i) {
                        return '<div class="sf-query-row">'
                             + '<div class="sf-sql">#' + (i+1) + ' ' + escHtml(q.sql) + '</div>'
                             + '<div class="sf-time">Bindings: ' + escHtml(JSON.stringify(q.bindings)) + ' &mdash; ' + q.time.toFixed(2) + ' ms</div>'
                             + '</div>';
                    }).join('');
                }

                function renderLogs() {
                    var el = document.getElementById('sf-logs-content');
                    if (!logs.length) { el.innerHTML = '<p class="sf-empty">No logs recorded.</p>'; return; }
                    el.innerHTML = logs.map(function(l) {
                        return '<div class="sf-log-row">'
                             + (l.label ? '<div class="sf-log-label">' + escHtml(l.label) + '</div>' : '')
                             + '<div class="sf-log-value">' + escHtml(JSON.stringify(l.value, null, 2)) + '</div>'
                             + '</div>';
                    }).join('');
                }

                function escHtml(s) {
                    return String(s)
                        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
                        .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
                }

                window.sfToggleToolbar = function(open) {
                    var tab = document.getElementById('sf-debug-tab');
                    var toolbar = document.getElementById('sf-toolbar');
                    var overlay = document.getElementById('sf-overlay');

                    if (open) {
                        tab.style.display = 'none';
                        toolbar.classList.add('sf-open');
                        try { localStorage.setItem('sf_debug_expanded', '1'); } catch (e) {}
                    } else {
                        toolbar.classList.remove('sf-open');
                        tab.style.display = 'flex';
                        if (overlay) overlay.classList.remove('sf-overlay--open');
                        activePanel = '';
                        try { localStorage.setItem('sf_debug_expanded', '0'); } catch (e) {}
                    }
                };

                window.sfTogglePanel = function(panel) {
                    var overlay = document.getElementById('sf-overlay');
                    var qPanel  = document.getElementById('sf-panel-queries');
                    var lPanel  = document.getElementById('sf-panel-logs');
                    var titleEl = document.getElementById('sf-panel-header-title');

                    if (panel === activePanel || panel === '') {
                        overlay.classList.remove('sf-overlay--open');
                        activePanel = '';
                        return;
                    }

                    activePanel = panel;
                    overlay.classList.add('sf-overlay--open');

                    if (panel === 'queries') {
                        titleEl.textContent = 'Database Queries (' + queries.length + ')';
                        qPanel.style.display = 'block';
                        lPanel.style.display = 'none';
                        renderQueries();
                    } else if (panel === 'logs') {
                        titleEl.textContent = 'Debug Logs (' + logs.length + ')';
                        qPanel.style.display = 'none';
                        lPanel.style.display = 'block';
                        renderLogs();
                    }
                };

                // Restore previous expanded state if user had it open
                try {
                    if (localStorage.getItem('sf_debug_expanded') === '1') {
                        sfToggleToolbar(true);
                    }
                } catch (e) {}
            })();
        </script>
        HTML;
    }
}
