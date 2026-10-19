<?php
/**
 * StackFlow Pretty Error View
 *
 * Variables passed in:
 * @var Throwable $exception
 * @var array<int, array{line: int, code: string}> $codeSnippet
 * @var array<int, array<string, mixed>> $stackTrace
 * @var array{method: string, url: string, get: array, post: array, headers: array} $request
 */

$class = get_class($exception);
$message = $exception->getMessage();
$file = $exception->getFile();
$line = $exception->getLine();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error: <?= htmlspecialchars($class, ENT_QUOTES, 'UTF-8') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .code-font { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }
    </style>
</head>
<body class="bg-gray-950 text-gray-100 min-h-screen antialiased selection:bg-rose-500 selection:text-white">
    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Header -->
        <header class="border-b border-gray-800 pb-6 mb-8 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center justify-center h-10 w-10 rounded-xl bg-rose-600/20 text-rose-400 font-bold text-lg ring-1 ring-rose-500/30">!</span>
                <div>
                    <h1 class="text-xl font-bold tracking-tight text-white flex items-center gap-2">
                        <span>Stackflow</span>
                        <span class="text-xs font-mono font-normal px-2 py-0.5 rounded bg-gray-800 text-gray-400">PHP <?= PHP_VERSION ?></span>
                    </h1>
                    <p class="text-xs text-gray-400">Unhandled Exception occurred during request</p>
                </div>
            </div>
            <div class="flex items-center gap-2 text-xs font-mono text-gray-400 bg-gray-900 border border-gray-800 px-3 py-1.5 rounded-lg">
                <span class="font-semibold text-gray-300"><?= htmlspecialchars($request['method'], ENT_QUOTES, 'UTF-8') ?></span>
                <span class="text-gray-600">|</span>
                <span class="truncate max-w-sm"><?= htmlspecialchars($request['url'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </header>

        <!-- Exception Card -->
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6 mb-8 shadow-xl">
            <div class="text-rose-400 font-mono text-sm font-semibold mb-2"><?= htmlspecialchars($class, ENT_QUOTES, 'UTF-8') ?></div>
            <div class="text-2xl font-bold text-white mb-4 leading-tight"><?= htmlspecialchars($message ?: '(No message provided)', ENT_QUOTES, 'UTF-8') ?></div>
            <div class="flex items-center gap-2 text-sm text-gray-400 font-mono bg-gray-950/60 p-3 rounded-xl border border-gray-800/80">
                <svg class="w-4 h-4 text-gray-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span class="text-indigo-400"><?= htmlspecialchars($file, ENT_QUOTES, 'UTF-8') ?></span>
                <span class="text-gray-600">:</span>
                <span class="text-amber-400 font-bold"><?= $line ?></span>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left 2 Cols: Code snippet & Stack trace -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Snippet -->
                <div class="bg-gray-900 border border-gray-800 rounded-2xl overflow-hidden shadow-xl">
                    <div class="px-6 py-4 border-b border-gray-800 flex items-center justify-between bg-gray-900/50">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-400">Source Snippet</span>
                        <span class="text-xs font-mono text-gray-500"><?= basename($file) ?></span>
                    </div>
                    <div class="p-4 overflow-x-auto code-font text-xs leading-relaxed bg-gray-950">
                        <?php foreach ($codeSnippet as $item): ?>
                            <?php $isErr = ($item['line'] === $line); ?>
                            <div class="flex items-center py-0.5 px-2 rounded <?= $isErr ? 'bg-rose-950/80 border-l-4 border-rose-500 text-rose-200' : 'text-gray-300' ?>">
                                <span class="w-12 select-none shrink-0 <?= $isErr ? 'text-rose-400 font-bold' : 'text-gray-600' ?>"><?= $item['line'] ?></span>
                                <span class="whitespace-pre"><?= htmlspecialchars($item['code'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Stack Trace -->
                <div class="bg-gray-900 border border-gray-800 rounded-2xl overflow-hidden shadow-xl">
                    <div class="px-6 py-4 border-b border-gray-800 flex items-center justify-between bg-gray-900/50">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-400">Stack Trace</span>
                        <span class="text-xs text-gray-500"><?= count($stackTrace) ?> frames</span>
                    </div>
                    <div class="divide-y divide-gray-800/60 max-h-[500px] overflow-y-auto">
                        <?php foreach ($stackTrace as $idx => $frame): ?>
                            <?php
                            $frameFile = $frame['file'] ?? '[internal function]';
                            $frameLine = $frame['line'] ?? 0;
                            $caller = ($frame['class'] ?? '') . ($frame['type'] ?? '') . ($frame['function'] ?? '') . '()';
                            ?>
                            <div class="p-4 hover:bg-gray-850 transition-colors">
                                <div class="flex items-baseline justify-between mb-1">
                                    <span class="font-mono text-sm font-semibold text-indigo-400"><?= htmlspecialchars($caller, ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="text-xs text-gray-500 font-mono">#<?= $idx ?></span>
                                </div>
                                <div class="text-xs text-gray-400 font-mono truncate">
                                    <?= htmlspecialchars($frameFile, ENT_QUOTES, 'UTF-8') ?><?= $frameLine ? ":<span class=\"text-amber-400\">{$frameLine}</span>" : '' ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Right 1 Col: Environment & Request Info -->
            <div class="space-y-6">
                <!-- Request Info -->
                <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6 shadow-xl">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-4">Request Details</h3>
                    <dl class="space-y-3 text-xs">
                        <div>
                            <dt class="text-gray-500 mb-0.5">Method</dt>
                            <dd class="font-mono text-gray-200"><?= htmlspecialchars($request['method'], ENT_QUOTES, 'UTF-8') ?></dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 mb-0.5">URL</dt>
                            <dd class="font-mono text-gray-200 break-all"><?= htmlspecialchars($request['url'], ENT_QUOTES, 'UTF-8') ?></dd>
                        </div>
                        <?php if (!empty($request['get'])): ?>
                        <div>
                            <dt class="text-gray-500 mb-0.5">Query Parameters</dt>
                            <dd class="font-mono bg-gray-950 p-2 rounded border border-gray-800 text-gray-300">
                                <pre class="whitespace-pre-wrap"><?= htmlspecialchars(json_encode($request['get'], JSON_PRETTY_PRINT), ENT_QUOTES, 'UTF-8') ?></pre>
                            </dd>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($request['post'])): ?>
                        <div>
                            <dt class="text-gray-500 mb-0.5">Post Data</dt>
                            <dd class="font-mono bg-gray-950 p-2 rounded border border-gray-800 text-gray-300">
                                <pre class="whitespace-pre-wrap"><?= htmlspecialchars(json_encode($request['post'], JSON_PRETTY_PRINT), ENT_QUOTES, 'UTF-8') ?></pre>
                            </dd>
                        </div>
                        <?php endif; ?>
                    </dl>
                </div>

                <!-- Environment Info -->
                <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6 shadow-xl">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-4">Environment</h3>
                    <dl class="space-y-3 text-xs">
                        <div>
                            <dt class="text-gray-500 mb-0.5">PHP Version</dt>
                            <dd class="font-mono text-gray-200"><?= PHP_VERSION ?></dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 mb-0.5">Server Software</dt>
                            <dd class="font-mono text-gray-200"><?= htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'PHP CLI Server', ENT_QUOTES, 'UTF-8') ?></dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 mb-0.5">Peak Memory Usage</dt>
                            <dd class="font-mono text-gray-200"><?= round(memory_get_peak_usage(true) / 1024 / 1024, 2) ?> MB</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
