<?php

declare(strict_types=1);

namespace StackFlow\Layout;

use StackFlow\Core\Request;
use StackFlow\Page\Page;
use StackFlow\Widgets\Widget;

/**
 * Abstract Base Layout
 *
 * Layouts act as master shells for application pages (e.g. Admin portals,
 * Dashboards, Customer portals, Guest layouts).
 *
 * A layout wraps child page content widgets and provides common scaffolding such as
 * Navbars, Sidebars, Drawers, Footers, and user status controls.
 *
 * Usage:
 * ```php
 * class AdminLayout extends Layout
 * {
 *     public function wrap(Widget $content, Request $request, Page $page): Widget
 *     {
 *         return Scaffold::make([
 *             'appBar' => Navbar::make(['brand' => 'Admin Portal']),
 *             'drawer' => Sidebar::make([...]),
 *             'body'   => Container::make(['child' => $content]),
 *         ]);
 *     }
 * }
 * ```
 *
 * @package StackFlow\Layout
 */
abstract class Layout
{
    /**
     * Wrap the child page content in the layout's master widget tree.
     *
     * @param Widget  $content The content widget built by the child page.
     * @param Request $request The current HTTP request.
     * @param Page    $page    The child page instance.
     * @return Widget          The complete layout widget tree containing the content.
     */
    abstract public function wrap(Widget $content, Request $request, Page $page): Widget;
}
