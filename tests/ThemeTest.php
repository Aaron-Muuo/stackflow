<?php

declare(strict_types=1);

/**
 * Theme & Light Mode Verification Tests
 *
 * Verifies that all pages and layouts render in crisp light theme by default:
 *  - <html> tag has no 'dark' class
 *  - <body> tag defaults to light styling: bg-white text-gray-900 (no dark:bg overrides on default)
 *  - FOUC script explicitly removes 'dark' class unless explicitly requested
 *  - Tailwind CDN loads before tailwind.config
 */

require_once __DIR__ . '/../vendor/autoload.php';

use StackFlow\Core\Application;
use StackFlow\Core\Request;
use StackFlow\Page\PageRenderer;
use App\Pages\WelcomePage;
use App\Pages\LoginPage;
use App\Layouts\BlankLayout;
use App\Layouts\AppLayout;
use App\Layouts\AdminLayout;
use StackFlow\Widgets\Display\Text;

Application::boot(dirname(__DIR__));
$renderer = new PageRenderer();

echo "Starting Theme & Light Mode Tests...\n";

// Test 1: WelcomePage renders with pure white / light body
$resWelcome = $renderer->render(new WelcomePage(), Request::create('GET', '/'));
assert(!str_contains($resWelcome, '<html lang="en" class="dark">'), 'FAIL: <html> must not have dark class');
assert(str_contains($resWelcome, 'bg-white text-gray-900'), 'FAIL: WelcomePage body must have bg-white text-gray-900');
assert(!str_contains($resWelcome, 'dark:bg-[#050608]'), 'FAIL: WelcomePage body must not have dark:bg-[#050608]');
echo "PASS: Test 1 - WelcomePage defaults to clean light theme\n";

// Test 2: LoginPage renders in light theme
$resLogin = $renderer->render(new LoginPage(), Request::create('GET', '/login'));
assert(!str_contains($resLogin, '<html lang="en" class="dark">'), 'FAIL: LoginPage <html> must not have dark class');
assert(str_contains($resLogin, 'bg-white text-gray-900'), 'FAIL: LoginPage body must have bg-white text-gray-900');
assert(str_contains($resLogin, 'bg-gray-50/80'), 'FAIL: BlankLayout canvas must be soft light gray bg-gray-50/80');
echo "PASS: Test 2 - LoginPage and BlankLayout default to clean light theme\n";

// Test 3: Script order: Tailwind CDN is loaded before themeHead
$cdnPos = strpos($resWelcome, 'https://cdn.tailwindcss.com');
$configPos = strpos($resWelcome, 'tailwind.config =');
assert($cdnPos !== false, 'FAIL: Tailwind CDN script must be present');
assert($configPos !== false, 'FAIL: tailwind.config must be present');
assert($cdnPos < $configPos, 'FAIL: Tailwind CDN must load before tailwind.config to avoid ReferenceError');
echo "PASS: Test 3 - Tailwind CDN loads before tailwind.config\n";

// Test 4: Dark mode script explicitly defaults to light
assert(str_contains($resWelcome, "root.classList.remove('dark')"), 'FAIL: Dark mode script must remove dark class by default');
echo "PASS: Test 4 - Dark mode script enforces light mode removal by default\n";

// Test 5: Core widgets default to rounded-sm and shadow-none
$cardHtml = \StackFlow\Widgets\Container\Card::make(['title' => 'Test'])->render();
assert(str_contains($cardHtml, 'rounded-sm'), 'FAIL: Card must default to rounded-sm');
assert(str_contains($cardHtml, 'shadow-none'), 'FAIL: Card must default to shadow-none');

$btnHtml = \StackFlow\Widgets\Input\Button::make(['label' => 'Click'])->render();
assert(str_contains($btnHtml, 'rounded-sm'), 'FAIL: Button must default to rounded-sm');
assert(str_contains($btnHtml, 'shadow-none'), 'FAIL: Button must default to shadow-none');

$inputHtml = \StackFlow\Widgets\Input\Input::make(['name' => 'email'])->render();
assert(str_contains($inputHtml, 'rounded-sm'), 'FAIL: Input must default to rounded-sm');
assert(str_contains($inputHtml, 'shadow-none'), 'FAIL: Input must default to shadow-none');

$selectHtml = \StackFlow\Widgets\Input\Select::make(['name' => 'role'])->render();
assert(str_contains($selectHtml, 'rounded-sm'), 'FAIL: Select must default to rounded-sm');
assert(str_contains($selectHtml, 'shadow-none'), 'FAIL: Select must default to shadow-none');

$textareaHtml = \StackFlow\Widgets\Input\Textarea::make(['name' => 'bio'])->render();
assert(str_contains($textareaHtml, 'rounded-sm'), 'FAIL: Textarea must default to rounded-sm');
assert(str_contains($textareaHtml, 'shadow-none'), 'FAIL: Textarea must default to shadow-none');

$tableHtml = \StackFlow\Widgets\Data\Table::make(['columns' => []])->render();
assert(str_contains($tableHtml, 'rounded-sm'), 'FAIL: Table must default to rounded-sm');
assert(str_contains($tableHtml, 'shadow-none'), 'FAIL: Table must default to shadow-none');

$alertHtml = \StackFlow\Widgets\Feedback\Alert::make(['message' => 'Hello'])->render();
assert(str_contains($alertHtml, 'rounded-sm'), 'FAIL: Alert must default to rounded-sm');
assert(str_contains($alertHtml, 'shadow-none'), 'FAIL: Alert must default to shadow-none');

$badgeHtml = \StackFlow\Widgets\Display\Badge::make(['label' => 'New'])->render();
assert(str_contains($badgeHtml, 'rounded-sm'), 'FAIL: Badge must default to rounded-sm');

echo "PASS: Test 5 - Core widgets default to rounded-sm and shadow-none\n";

// Test 6: Span widget accepts text as first parameter
$span1 = \StackFlow\Widgets\Display\Span::make("Don't have an account?")->render();
assert(str_contains($span1, "Don&#039;t have an account?") || str_contains($span1, "Don't have an account?"), "FAIL: Span::make with string must render span text");

$span2 = \StackFlow\Widgets\Display\Span::make("Important", ['class' => 'font-bold text-red-600'])->render();
assert(str_contains($span2, 'class="font-bold text-red-600"'), "FAIL: Span::make with string and extra classes");
assert(str_contains($span2, 'Important'), "FAIL: Span::make must contain text");

$span3 = span("Helper function text", ['color' => 'text-gray-500'])->render();
assert(str_contains($span3, 'class="text-gray-500"'), "FAIL: span() helper with color");
assert(str_contains($span3, 'Helper function text'), "FAIL: span() helper must contain text");

echo "PASS: Test 6 - Span widget and span() helper render correctly with text as first parameter\n";

// Test 7: Debug toolbar renders as a non-disruptive collapsible bottom-right tab with bug icon and Stackflow branding
$debugHtml = \StackFlow\Debug\Debugger::renderToolbar();
assert(str_contains($debugHtml, 'id="sf-debug-tab"'), 'FAIL: Must include #sf-debug-tab');
assert(str_contains($debugHtml, 'right: 24px'), 'FAIL: Must be positioned on the far right');
assert(!str_contains($debugHtml, 'left: 0;'), 'FAIL: Must not span full width with left: 0');
assert(str_contains($debugHtml, 'sfToggleToolbar'), 'FAIL: Must include toggle function');
assert(str_contains($debugHtml, 'sf-bug-icon'), 'FAIL: Must use bug icon');
assert(str_contains($debugHtml, 'Stackflow'), 'FAIL: Must use Stackflow without capitalized F');
assert(!str_contains($debugHtml, '⚡'), 'FAIL: Must not use lightning power icon');
assert(str_contains($debugHtml, 'font-family: -apple-system'), 'FAIL: Must use sans-serif font');
echo "PASS: Test 7 - Debug toolbar renders as a collapsible bottom-right tab with bug icon & sans font\n";

// Test 8: Compact Tailwind toasts with colored backgrounds, icon, single-line text, and close button
$toastScript = \StackFlow\Widgets\Feedback\Toast::script();
assert(str_contains($toastScript, 'bg-emerald-600'), 'FAIL: Must have green bg for success');
assert(str_contains($toastScript, 'bg-rose-600'), 'FAIL: Must have red bg for danger/error');
assert(str_contains($toastScript, 'bg-orange-500'), 'FAIL: Must have orange bg for warning');
assert(str_contains($toastScript, 'bg-blue-600'), 'FAIL: Must have blue bg for info');
assert(str_contains($toastScript, 'truncate'), 'FAIL: Must have truncate for one-line text');
assert(str_contains($toastScript, 'aria-label="Close"'), 'FAIL: Must have close button');
assert(str_contains($toastScript, 'window.Stackflow.toast'), 'FAIL: Must expose window.Stackflow.toast');
echo "PASS: Test 8 - Toast component renders compact colored Tailwind toasts with icon and close button\n";

echo "\n>>> ALL THEME & LIGHT MODE TESTS PASSED! <<<\n";

