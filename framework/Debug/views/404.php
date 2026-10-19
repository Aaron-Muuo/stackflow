<?php
/**
 * StackFlow 404 Not Found View
 *
 * Variables passed in:
 * @var string $path
 * @var string $method
 * @var string $message
 */

$path = $path ?? ($_SERVER['REQUEST_URI'] ?? '/');
$method = $method ?? ($_SERVER['REQUEST_METHOD'] ?? 'GET');
$message = $message ?? 'Page Not Found';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 | Not Found — Stackflow</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .code-font { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }
    </style>
</head>
<body class="bg-gray-950 text-gray-100 min-h-screen antialiased selection:bg-rose-500 selection:text-white flex flex-col justify-between p-6 sm:p-8">
    <!-- Top Header -->
    <header class="max-w-7xl mx-auto w-full flex items-center justify-between pb-6">
        <a href="/" class="flex items-center gap-3 group">
            <span class="inline-flex items-center justify-center h-9 w-9 rounded-xl bg-rose-600/20 text-rose-400 font-bold text-sm ring-1 ring-rose-500/30 font-mono transition-transform group-hover:scale-105">&lt;/&gt;</span>
            <span class="text-base font-bold tracking-tight text-white">Stackflow</span>
        </a>
        <div class="text-xs font-mono text-gray-500">
            HTTP 404
        </div>
    </header>

    <!-- Center Card: Laravel 404 Lockup -->
    <main class="w-full max-w-xl mx-auto my-auto text-center py-8">
        <div class="bg-gray-900 border border-gray-800 rounded-3xl p-8 sm:p-14 shadow-2xl relative overflow-hidden">
            <!-- Subtle background ambient glow -->
            <div class="absolute -top-24 left-1/2 -translate-x-1/2 w-96 h-96 bg-rose-500/10 rounded-full blur-3xl pointer-events-none"></div>

            <div class="relative z-10">
                <!-- Laravel 404 Lockup (Number | Label) -->
                <div class="flex items-center justify-center gap-4 sm:gap-6 my-4">
                    <span class="text-6xl sm:text-7xl font-extrabold tracking-tight text-white font-mono">404</span>
                    <span class="h-12 sm:h-14 w-px bg-gray-700/80"></span>
                    <div class="text-left">
                        <span class="block text-rose-400 text-[11px] font-bold uppercase tracking-widest font-mono">STATUS</span>
                        <span class="text-lg sm:text-2xl font-bold tracking-tight text-gray-200 uppercase tracking-wider">NOT FOUND</span>
                    </div>
                </div>

                <p class="text-gray-400 text-sm sm:text-base max-w-md mx-auto mb-8 leading-relaxed">
                    The page you are looking for could not be found or has been moved.
                </p>

                <!-- Navigation buttons -->
                <div class="flex items-center justify-center gap-3 flex-wrap">
                    <a href="/" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-medium text-sm transition-all shadow-lg shadow-indigo-600/25">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        <span>Return to Home</span>
                    </a>
                    <button type="button" onclick="window.history.length > 1 ? window.history.back() : window.location.href='/'" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-gray-800 hover:bg-gray-750 text-gray-300 hover:text-white font-medium text-sm transition-all border border-gray-700/60">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        <span>Go Back</span>
                    </button>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="max-w-7xl mx-auto w-full pt-6 text-center text-xs text-gray-500">
        Stackflow Framework
    </footer>
</body>
</html>
