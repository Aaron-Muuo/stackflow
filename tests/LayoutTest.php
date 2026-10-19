<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use StackFlow\Core\Request;
use StackFlow\Page\Page;
use StackFlow\Page\PageRenderer;
use StackFlow\Widgets\Widget;
use StackFlow\Widgets\Display\Text;
use StackFlow\Widgets\Layout\Scaffold;
use StackFlow\Widgets\Navigation\Navbar;
use App\Layouts\AdminLayout;
use App\Layouts\AppLayout;
use App\Layouts\BlankLayout;

// 1. Standalone Page (layout = null, defines own navbar)
class StandalonePage extends Page
{
    protected ?string $layout = null;

    public function title(): string
    {
        return 'Standalone';
    }

    public function build(Request $req): Widget
    {
        return Scaffold::make([
            'appBar' => Navbar::make(['brand' => 'CustomStandalone']),
            'body'   => Text::make(['value' => 'Standalone Body Content']),
        ]);
    }
}

// 2. Admin Portal Page (extends AdminLayout)
class DashboardAdminPage extends Page
{
    protected ?string $layout = AdminLayout::class;

    public function title(): string
    {
        return 'Admin Analytics';
    }

    public function layoutProps(): array
    {
        return ['brand' => 'Enterprise HQ'];
    }

    public function content(Request $req): ?Widget
    {
        return Text::make(['value' => 'Analytics Metric Cards']);
    }
}

// 3. Web App Page (extends AppLayout)
class WebStorePage extends Page
{
    protected ?string $layout = AppLayout::class;

    public function title(): string
    {
        return 'Web Store';
    }

    public function layoutProps(): array
    {
        return ['brand' => 'ShopSphere'];
    }

    public function content(Request $req): ?Widget
    {
        return Text::make(['value' => 'Product Catalog List']);
    }
}

// 4. Blank Page (extends BlankLayout)
class LoginPage extends Page
{
    protected ?string $layout = BlankLayout::class;

    public function title(): string
    {
        return 'Login Page';
    }

    public function content(Request $req): ?Widget
    {
        return Text::make(['value' => 'Login Form Here']);
    }
}

$renderer = new PageRenderer();
$req = Request::create('GET', '/admin');

// Test 1: Standalone
$html1 = $renderer->render(new StandalonePage(), $req);
assert(str_contains($html1, 'CustomStandalone'), 'CustomStandalone navbar missing');
assert(str_contains($html1, 'Standalone Body Content'), 'Standalone body missing');
assert(!str_contains($html1, 'admin-sidebar'), 'Standalone page should not have admin sidebar');
echo "PASS: Test 1 - Standalone page renders cleanly without layout wrapper\n";

// Test 2: Admin Layout
$html2 = $renderer->render(new DashboardAdminPage(), $req);
assert(str_contains($html2, 'Enterprise HQ'), 'Admin brand missing');
assert(str_contains($html2, 'admin-sidebar'), 'Admin sidebar missing');
assert(str_contains($html2, 'Analytics Metric Cards'), 'Admin content missing');
echo "PASS: Test 2 - AdminLayout wraps content with sidebar & navbar\n";

// Test 3: App Layout
$html3 = $renderer->render(new WebStorePage(), $req);
assert(str_contains($html3, 'ShopSphere'), 'App brand missing');
assert(str_contains($html3, 'Product Catalog List'), 'App content missing');
assert(!str_contains($html3, 'admin-sidebar'), 'AppLayout should not have admin sidebar');
echo "PASS: Test 3 - AppLayout wraps content with top navbar & container\n";

// Test 4: Blank Layout
$html4 = $renderer->render(new LoginPage(), $req);
assert(str_contains($html4, 'Login Form Here'), 'Blank content missing');
assert(str_contains($html4, 'max-w-md'), 'Blank max-w-md missing');
assert(!str_contains($html4, 'admin-sidebar'), 'BlankLayout should not have admin sidebar');
echo "PASS: Test 4 - BlankLayout renders focused card canvas\n";

echo "\n>>> ALL 4 LAYOUT VERIFICATION TESTS PASSED SUCCESSFULLY! <<<\n";
