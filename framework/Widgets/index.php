<?php

declare(strict_types=1);

/**
 * StackFlow Declarative Widget System
 *
 * All widgets live under the `StackFlow\Widgets` namespace.
 * Users can compose or extend any widget.
 *
 * Layout Widgets:
 *  - StackFlow\Widgets\Layout\Scaffold       — Full-page application shell
 *  - StackFlow\Widgets\Layout\Container      — Centered max-width container
 *  - StackFlow\Widgets\Layout\Column         — Vertical flex layout
 *  - StackFlow\Widgets\Layout\Row            — Horizontal flex layout
 *  - StackFlow\Widgets\Layout\Grid           — Responsive grid layout (1-12 columns)
 *
 * Input Widgets:
 *  - StackFlow\Widgets\Input\Form            — HTML <form> with CSRF & method spoofing
 *  - StackFlow\Widgets\Input\Button          — Customizable interactive buttons & links
 *  - StackFlow\Widgets\Input\Input           — Labeled text/email/password inputs
 *  - StackFlow\Widgets\Input\Select          — Dropdown selection input
 *  - StackFlow\Widgets\Input\Checkbox        — Checkbox with label
 *  - StackFlow\Widgets\Input\Radio           — Radio button groups (inline or stacked)
 *  - StackFlow\Widgets\Input\SwitchToggle    — Animated iOS/Android toggle switch
 *  - StackFlow\Widgets\Input\FileInput       — Styled file upload input
 *  - StackFlow\Widgets\Input\Textarea        — Multiline text input
 *  - StackFlow\Widgets\Input\Hidden          — Hidden input field
 *
 * Display Widgets:
 *  - StackFlow\Widgets\Display\Text          — Typographic text (p, span, labels)
 *  - StackFlow\Widgets\Display\Heading       — Scaled headings (h1 to h6) with subtitles
 *  - StackFlow\Widgets\Display\Link          — Accessible anchor tags with variants
 *  - StackFlow\Widgets\Display\Image         — Responsive images with rounded & aspect styles
 *  - StackFlow\Widgets\Display\Icon          — Built-in SVG icon library
 *  - StackFlow\Widgets\Display\Avatar        — User avatars with initials fallback & status dot
 *  - StackFlow\Widgets\Display\Badge         — Status & tag badges
 *  - StackFlow\Widgets\Display\Divider       — Visual horizontal dividers & labeled separators
 *
 * Container Widgets:
 *  - StackFlow\Widgets\Container\Card        — Surface cards with shadow & padding
 *  - StackFlow\Widgets\Container\Section     — Semantic <section> with headers
 *  - StackFlow\Widgets\Container\Modal       — Overlay dialogs with JS controls
 *  - StackFlow\Widgets\Container\Drawer      — Slide-out offcanvas sheets
 *
 * Navigation Widgets:
 *  - StackFlow\Widgets\Navigation\Navbar     — Responsive navbar with mobile drawer
 *  - StackFlow\Widgets\Navigation\Sidebar    — Vertical sidebar navigation
 *  - StackFlow\Widgets\Navigation\Tabs       — Client-side interactive tabs
 *  - StackFlow\Widgets\Navigation\Dropdown   — Click-toggle dropdown menus
 *  - StackFlow\Widgets\Navigation\Breadcrumb — Hierarchical page path navigation
 *
 * Data Widgets:
 *  - StackFlow\Widgets\Data\Table            — Responsive styled data tables
 *  - StackFlow\Widgets\Data\ListView         — Dynamic item list builders
 *  - StackFlow\Widgets\Data\Pagination       — Numbered page pagination
 *
 * Feedback Widgets:
 *  - StackFlow\Widgets\Feedback\Alert        — Color-coded alert boxes
 *  - StackFlow\Widgets\Feedback\Toast        — Floating toast notifications (multiple positions, auto-dismiss)
 *  - StackFlow\Widgets\Feedback\ConfirmDialog— Accessible confirmation dialog boxes for sensitive operations
 *  - StackFlow\Widgets\Feedback\Spinner      — Animated loading spinners
 *  - StackFlow\Widgets\Feedback\ProgressBar  — Linear progress bars (0-100%, striped, animated, gradients)
 *  - StackFlow\Widgets\Feedback\Skeleton     — Animated shimmer loading placeholders
 *  - StackFlow\Widgets\Feedback\EmptyState   — Placeholder screen for zero-data states
 */
