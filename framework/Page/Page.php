<?php

declare(strict_types=1);

namespace StackFlow\Page;

use StackFlow\Core\Request;
use StackFlow\Core\Response;
use StackFlow\Widgets\Widget;

/**
 * Abstract base class for all StackFlow pages.
 *
 * Every application page extends this class and implements `build()` to
 * return the root Widget tree that represents the page's UI.
 *
 * Lifecycle:
 *  1. action(Request $request): Executed first. If it returns a Response (redirect, json),
 *     rendering is skipped and the response is dispatched immediately.
 *  2. build(Request $request): Assembles and returns the Widget tree (or Response).
 */
abstract class Page
{
    /**
     * Optional action handler for button clicks, form submissions, and mutations.
     *
     * Executed before build(). If a Response is returned (e.g. Response::redirect(),
     * Response::json()), that response is immediately dispatched to the client
     * and build() is skipped.
     *
     * @param Request $request
     * @return Response|null
     */
    public function action(Request $request): ?Response
    {
        return null;
    }

    /**
     * Master Layout class to wrap this page with.
     *
     * Set to null if the page defines its own root layout / Scaffold / Navbar.
     *
     * Example:
     *   protected ?string $layout = \App\Layouts\AdminLayout::class;
     *
     * @var class-string<\StackFlow\Layout\Layout>|class-string<\StackFlow\Widgets\Widget>|null
     */
    protected ?string $layout = null;

    /**
     * Get the master layout class for this page.
     *
     * Return null if the page renders standalone (has its own custom navbar/scaffold).
     *
     * @return class-string|null
     */
    public function layout(): ?string
    {
        return $this->layout;
    }

    /**
     * Optional props passed to the layout widget.
     *
     * @return array<string, mixed>
     */
    public function layoutProps(): array
    {
        return [];
    }

    /**
     * Content widget for pages extending a layout.
     *
     * When extending a layout, developers can implement either content() or build().
     *
     * @param Request $request
     * @return Widget|null
     */
    public function content(Request $request): ?Widget
    {
        return null;
    }

    /**
     * Build the page's widget tree (or return a Response).
     *
     * This is the primary method pages implement. If not overridden in a child class,
     * it delegates to content($request).
     *
     * @param Request $request The current HTTP request.
     * @return Widget|Response The root widget for this page, or a direct response.
     */
    public function build(Request $request): Widget|Response
    {
        $content = $this->content($request);
        if ($content !== null) {
            return $content;
        }

        throw new \RuntimeException(
            "Page [" . static::class . "] must implement either build() or content()."
        );
    }

    // -------------------------------------------------------------------------
    // Page metadata
    // -------------------------------------------------------------------------

    /**
     * The browser tab / document title for this page.
     *
     * Defaults to the application name from config when not overridden.
     *
     * @return string
     */
    public function title(): string
    {
        /** @var array<string, mixed> $config */
        $config = function_exists('config') ? config('app') : [];

        return (string) ($config['name'] ?? 'StackFlow App');
    }

    /**
     * SEO / Open Graph meta tags to inject into <head>.
     *
     * Return an array of associative arrays representing <meta> attributes.
     *
     * Example:
     * ```php
     * return [
     *     ['name'     => 'description', 'content' => 'My page description'],
     *     ['property' => 'og:title',    'content' => 'My Page'],
     * ];
     * ```
     *
     * @return array<int, array<string, string>>
     */
    public function meta(): array
    {
        return [
            ['name' => 'viewport',     'content' => 'width=device-width, initial-scale=1.0'],
            ['name' => 'generator',    'content' => 'StackFlow Framework'],
            ['charset' => 'UTF-8'],
        ];
    }

    // -------------------------------------------------------------------------
    // Middleware & Security
    // -------------------------------------------------------------------------

    /**
     * Middleware classes or short names to run before this page is invoked.
     *
     * Example: protected array $middleware = [AuthMiddleware::class];
     *
     * @var array<int, class-string|string>
     */
    protected array $middleware = [];

    /**
     * Return middleware to run before this page executes.
     *
     * @return array<int, class-string|string>
     */
    public function middleware(): array
    {
        return $this->middleware;
    }

    /**
     * Optional authorization check before executing page actions or rendering.
     *
     * Return true to allow access, or false to reject with 403 Forbidden.
     *
     * @param Request $request
     * @return bool
     */
    public function authorize(Request $request): bool
    {
        return true;
    }

    /**
     * Validate request input with StackFlow rules.
     *
     * Example: $data = $this->validate($request, ['email' => 'required|email']);
     *
     * @param Request $request
     * @param array<string, string> $rules
     * @return array<string, mixed>
     */
    protected function validate(Request $request, array $rules): array
    {
        return $request->validate($rules);
    }

    // -------------------------------------------------------------------------
    // Asset injection
    // -------------------------------------------------------------------------

    /**
     * Additional JavaScript URLs to inject as <script src="..."> tags.
     *
     * These are injected just before </body>.
     *
     * @return array<int, string>
     */
    public function scripts(): array
    {
        return [];
    }

    /**
     * Additional CSS stylesheet URLs to inject as <link rel="stylesheet"> tags.
     *
     * These are injected inside <head> after framework styles.
     *
     * @return array<int, string>
     */
    public function styles(): array
    {
        return [];
    }

    // -------------------------------------------------------------------------
    // Theme
    // -------------------------------------------------------------------------

    /**
     * Preferred color scheme for this page.
     *
     * Accepted values:
     *  - `'light'` — always render in light mode
     *  - `'dark'`  — always render in dark mode
     *  - `'auto'`  — respect the OS / browser preference (default)
     *
     * @return string 'light'|'dark'|'auto'
     */
    public function theme(): string
    {
        return 'light';
    }
}
