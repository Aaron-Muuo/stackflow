# StackFlow PHP Framework

<p align="">
  <img src="https://img.shields.io/badge/StackFlow-v1.0.0-6366f1?style=for-the-badge&logo=php&logoColor=white" alt="StackFlow Version">
  <img src="https://img.shields.io/badge/PHP-%3E%3D8.2-8892bf?style=for-the-badge&logo=php&logoColor=white" alt="PHP Version">
  <img src="https://img.shields.io/badge/Tailwind_CSS-Ready-38bdf8?style=for-the-badge&logo=tailwindcss&logoColor=white" alt="Tailwind CSS">
  <img src="https://img.shields.io/badge/AI_Native-Gemini%20|%20OpenAI%20|%20Ollama-10b981?style=for-the-badge" alt="AI Native">
  <img src="https://img.shields.io/badge/License-MIT-gray?style=for-the-badge" alt="License">
</p>

---

## Overview

**StackFlow** is an ultra-fast, modern PHP framework featuring a **declarative, Android Jetpack & Flutter-inspired widget-tree UI syntax**.

Instead of messy controllers passing arrays to separate template files, you write clean, typed PHP pages that return UI widget trees pre-styled with Tailwind CSS. It comes out of the box with zero-flicker dark mode, an Active Record database layer, native AI integration, and full CRUD generation both from the command line and directly in your browser.

> 🛠️ **Contributing to the framework engine itself?** See the [Framework Developer Technical Documentation](FRAMEWORK_DOCS.md) for micro-kernel internals, lifecycle pipelines, and core extension guides.

---

## Getting Started in 2 Minutes

### 1. Requirements
- **PHP 8.2** or higher (with `pdo`, `pdo_mysql` or `pdo_sqlite`, `mbstring`, `openssl`).
- Composer.

### 2. Setup
```bash
# 1. Install dependencies
composer install

# 2. Copy environment file and generate application key
cp .env.example .env
php stackflow key:generate

# 3. Start development server from project root
php stackflow serve
```

Visit **`http://127.0.0.1:8000`** in your browser!

---

## Application Workspace ("Where Do I Put My Code?")

All of your application code lives inside the **`app/`** directory. You do not need to touch or worry about core framework files in `framework/`:

```
├── app/
│   ├── config/              # App, Database, AI, and Auth configurations
│   ├── Layouts/             # Master layout shells (Admin, App, Blank)
│   ├── Models/              # Active Record database models
│   ├── Pages/               # UI Page classes (Widget Trees)
│   ├── Widgets/             # Custom & overridden widgets
│   ├── Api/                 # REST API controllers
│   └── migrations/          # Database schema migrations
├── routes/
│   ├── web.php              # Web page route mappings
│   └── api.php              # REST API endpoint mappings
├── public/                  # Public assets (images, CSS, JS, favicon)
├── storage/                 # SQLite database, logs, and caches
└── stackflow                # CLI tool (php stackflow ...)
```

---

## CLI Commands Cheat-Sheet

All framework operations are managed using the root `stackflow` CLI:

```bash
# Start local development server
php stackflow serve

# Generate complete CRUD (Model, Migration, 4 Widget Pages, API, Routes)
php stackflow generate:crud Product --fields="name:string,price:decimal,stock:integer"

# Create a new Page (standalone or extending a layout)
php stackflow make:page LoginPage
php stackflow make:page DashboardPage --layout=admin
php stackflow make:page ProfilePage --layout=app

# Create a new Master Layout Shell
php stackflow make:layout PartnerLayout --type=admin
php stackflow make:layout PublicLayout --type=app
php stackflow make:layout AuthLayout --type=blank

# Create a new Model (with optional migration and API flags)
php stackflow make:model Student --migration --api

# Create or override a Widget with custom Tailwind styles
php stackflow make:widget PrimaryButton --extends=Button

# Create a REST API Controller
php stackflow make:api StudentApi

# Database Migrations
php stackflow make:migration create_students_table
php stackflow migrate
php stackflow migrate:rollback

# Utilities
php stackflow key:generate
php stackflow cache:clear
```

---

## Building Declarative UI Pages

In StackFlow, each page is a PHP class extending `StackFlow\Page\Page`. You compose your interface in the `build()` method using declarative widgets:

```php
<?php

declare(strict_types=1);

namespace App\Pages;

use StackFlow\Core\Request;
use StackFlow\Page\Page;
use StackFlow\Widgets\Widget;
use StackFlow\Widgets\Layout\Scaffold;
use StackFlow\Widgets\Layout\Container;
use StackFlow\Widgets\Container\Card;
use StackFlow\Widgets\Input\Form;
use StackFlow\Widgets\Input\Input;
use StackFlow\Widgets\Input\Button;
use StackFlow\Widgets\Navigation\Navbar;

class StudentPage extends Page
{
    public function title(): string
    {
        return 'Register Student';
    }

    public function build(Request $request): Widget
    {
        return Scaffold::make([
            'appBar' => Navbar::make(['brand' => 'Academics Portal']),
            'body'   => Container::make([
                'maxWidth' => 'lg',
                'child'    => Card::make([
                    'title'    => 'Student Registration',
                    'subtitle' => 'Enter student details below',
                    'child'    => Form::make([
                        'action'   => '/students',
                        'method'   => 'POST',
                        'children' => [
                            Input::make(['name' => 'name', 'label' => 'Full Name', 'required' => true]),
                            Input::make(['name' => 'email', 'label' => 'Email Address', 'type' => 'email', 'required' => true]),
                            Button::make(['label' => 'Register', 'type' => 'submit', 'variant' => 'primary', 'size' => 'lg']),
                        ],
                    ]),
                ]),
            ]),
        ]);
    }
}
```

Map your page to a URL in `routes/web.php`:
```php
use StackFlow\Core\Router;
use App\Pages\StudentPage;

Router::get('/students/create', StudentPage::class);
```

---

## Master Layouts & Page Inheritance

In modern applications, pages often share common master layouts (e.g. Admin Portals with sidebars and navbars, or Web Apps with standard top headers and footers). Other pages (such as Landing pages, Login screens, or custom onboarding wizards) render completely standalone with their own bespoke navbars or no navigation at all.

StackFlow supports both paradigms elegantly:

### 1. Extending a Parent Layout (Admin Portal / Dashboard)
To wrap your page inside a master layout shell, declare the layout class and return your inner UI widgets from `content()`:

```php
<?php

declare(strict_types=1);

namespace App\Pages;

use App\Layouts\AdminLayout;
use StackFlow\Core\Request;
use StackFlow\Page\Page;
use StackFlow\Widgets\Widget;
use StackFlow\Widgets\Container\Card;
use StackFlow\Widgets\Display\Text;

class AnalyticsPage extends Page
{
    /**
     * Extend the AdminLayout master shell.
     */
    protected ?string $layout = AdminLayout::class;

    public function title(): string
    {
        return 'Analytics Overview';
    }

    /**
     * Pass custom props to the master layout (e.g. brand, user profile, custom nav).
     */
    public function layoutProps(): array
    {
        return [
            'brand' => 'Enterprise HQ',
            'fullWidth' => false,
        ];
    }

    /**
     * Return only the inner content widget tree.
     */
    public function content(Request $request): ?Widget
    {
        return Card::make([
            'title' => 'Monthly Revenue',
            'child' => Text::make(['value' => '$128,450.00', 'size' => 'text-3xl', 'weight' => 'font-bold']),
        ]);
    }
}
```

### 2. Standalone Pages (Landing Pages, Login Screens)
When a page has `protected ?string $layout = null;` (the default), it renders completely standalone. It defines its own custom `Navbar`, `Scaffold`, or canvas without being wrapped by any parent layout:

```php
class WelcomePage extends Page
{
    // No layout wrapper — page defines its own structure
    protected ?string $layout = null;

    public function build(Request $request): Widget
    {
        return Scaffold::make([
            'appBar' => Navbar::make(['brand' => 'MyLanding']),
            'body'   => Column::make([...]),
        ]);
    }
}
```

### 3. Built-in Layouts in `app/Layouts/`:
- **`AdminLayout`**: Full portal shell with responsive sidebar (collapsible on mobile), top navbar, dark mode toggle, user profile badge, and content container.
- **`AppLayout`**: Classic web application layout with top navbar, centered container (`max-w-7xl`), and footer.
- **`BlankLayout`**: Minimal centered canvas layout for authentication, wizards, and focused flows.

### 4. Creating Custom Layouts
Generate new layouts instantly using the CLI:
```bash
php stackflow make:layout PartnerLayout --type=admin
php stackflow make:layout StoreLayout --type=app
```
Custom layouts extend `StackFlow\Layout\Layout` and implement `wrap(Widget $content, Request $request, Page $page): Widget`.

---

## Standard Widget Catalog

StackFlow includes standard widgets for all web UI and HTML components so your entire application is built declaratively:

| Category | Widgets | Description |
| :--- | :--- | :--- |
| **Layout** | `Scaffold`, `Container`, `Column`, `Row`, `Grid` | Page shells, responsive containers, flex and grid builders |
| **Input / Forms** | `Form`, `Input`, `Button`, `Select`, `Checkbox`, `Radio`, `SwitchToggle`, `FileInput`, `Textarea`, `Hidden` | Full HTML forms with automatic CSRF, method spoofing, errors, and styling |
| **Display** | `Text`, `Heading`, `Link`, `Image`, `Icon`, `Avatar`, `Badge`, `Divider` | Typography (`h1`-`h6`, `p`), responsive images, SVG icons, badges, avatars |
| **Containers** | `Card`, `Section`, `Modal`, `Drawer` | Surface cards, semantic sections, modal dialogs, and slide-out drawers |
| **Navigation** | `Navbar`, `Sidebar`, `Tabs`, `Dropdown`, `Breadcrumb` | Top navbars, vertical sidebars, client-side tabs, and dropdown menus |
| **Data** | `Table`, `ListView`, `Pagination` | Responsive data tables, dynamic item lists, and numbered pagination |
| **Feedback** | `Alert`, `Toast`, `ConfirmDialog`, `Spinner`, `ProgressBar`, `Skeleton`, `EmptyState` | Dismissible toasts (6 positions), confirm dialogs, progress bars, spinners, and skeletons |

---

## Automatic AJAX & API Endpoints on All Pages

By default, **every Page in StackFlow automatically doubles as an API endpoint**. You do not need to build separate controllers or manually serialize JSON redirects:

1. **Automatic AJAX Interception**: Any `Form` (or button) submitted via AJAX (`fetch()` or `X-Requested-With: XMLHttpRequest`) automatically receives a JSON response instead of a hard browser reload.
2. **Seamless Redirection**: If your Page's `action()` returns `Response::redirect('/students')`, StackFlow automatically formats the AJAX response as:
   ```json
   {
       "status": "redirect",
       "redirect": "/students",
       "toast": {
           "message": "Student created successfully!",
           "type": "success"
       }
   }
   ```
   The client-side runtime automatically triggers the toast and navigates the browser smoothly.

3. **Interactive Toast Notifications**:
   ```php
   use StackFlow\Widgets\Feedback\Toast;

   Toast::make([
       'message'     => 'Profile updated!',
       'variant'     => 'success',
       'position'    => 'top-center', // top-center, bottom-center, top-right, top-left, bottom-right, bottom-left
       'duration'    => 3500,        // Disappears after 3.5s
       'dismissible' => true,        // Includes ✕ close button
   ]);
   ```
   Or trigger directly in JavaScript anywhere:
   ```javascript
   StackFlow.toast.success('Saved!', 'top-center');
   StackFlow.toast.error('An error occurred.', 'top-center');
   ```

4. **Confirmation Dialogs**:
   ```php
   use StackFlow\Widgets\Feedback\ConfirmDialog;

   ConfirmDialog::make([
       'id'          => 'delete-dialog',
       'title'       => 'Delete Student',
       'message'     => 'Are you sure you want to delete this record? This action cannot be undone.',
       'confirmText' => 'Delete',
       'variant'     => 'danger',
       'action'      => '/students/1',
       'method'      => 'DELETE',
   ]);
   ```
   Or declaratively on any button/link with `data-sf-confirm`:
   ```html
   <button data-sf-confirm="Are you sure you want to delete this student?" class="...">Delete</button>
   ```

5. **Modal Dialogs**:
   Open or close any modal cleanly with `StackFlow.modal.open('my-modal')` or `StackFlow.modal.close('my-modal')` (includes automatic ESC key handling and backdrop dismiss).

---

## Customizing & Extending Widgets

Need custom button styles, gradients, or special animations? Extend any widget and override its `render()` method:

```bash
php stackflow make:widget GradientButton --extends=Button
```

In `app/Widgets/GradientButton.php`:
```php
namespace App\Widgets;

use StackFlow\Widgets\Input\Button as BaseButton;

class GradientButton extends BaseButton
{
    public function render(): string
    {
        $label = $this->prop('label', 'Click Me');
        $classes = $this->classes(
            'px-6 py-3 rounded-2xl bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-bold shadow-lg hover:shadow-xl hover:scale-105 transition-all'
        );

        return "<button class=\"{$classes}\">{$this->e($label)}</button>";
    }
}
```

---

## In-Browser Visual CRUD Generator

Prefer a graphical interface over the command line? StackFlow includes a built-in visual code generator:

1. Start your local server: `php stackflow serve`
2. Open **`http://localhost:8000/stackflow/crud`** in your browser
3. Type your Model Name (e.g. `Product`), specify your fields (e.g. `title:string, price:decimal, qty:integer`), and click **Generate CRUD**.
4. The Model, Migration, Widget Pages, and REST API controller are created instantly on disk.

---

## Native AI Facade

Connect to Google Gemini, OpenAI, or local Ollama models with zero setup:

```php
use StackFlow\AI\AI;

// 1. Text generation with default driver (.env AI_DRIVER=gemini)
$response = AI::generate('Summarize our quarterly goals in 3 bullet points.');

// 2. Chat completion
$reply = AI::driver('openai')->chat([
    ['role' => 'system', 'content' => 'You are an academic advisor.'],
    ['role' => 'user', 'content' => 'Recommend introductory CS electives.'],
]);

// 3. Local offline AI with Ollama
$localOutput = AI::driver('ollama')->generate('Write a SQL query for top 10 customers.');
```

Set your API keys in `app/config/ai.php` or `.env`.

---

## Database & Active Record

StackFlow supports **MySQL** and **SQLite** out-of-the-box:

```php
namespace App\Models;

use StackFlow\Database\Model;

class Student extends Model
{
    protected static string $table = 'students';
    protected static array $fillable = ['name', 'email', 'course'];
    protected static array $rules = [
        'name'  => 'required|min:2',
        'email' => 'required|email',
    ];

    // Static action method (API & Web ready)
    public static function register(array $data): static
    {
        return static::create($data);
    }
}
```

```php
// Finding records
$student = Student::find(1);

// Querying
$students = Student::where('course', '=', 'Computer Science')->get();

// Updating
$student->course = 'Software Engineering';
$student->save();
```

---

## 🛡️ License

StackFlow PHP is open-source software licensed under the [MIT license](LICENSE).
