<?php

declare(strict_types=1);

namespace StackFlow\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * make:layout — Generate a new StackFlow Layout class
 *
 * Usage:
 *   php stackflow make:layout PortalLayout
 *   php stackflow make:layout AdminLayout --type=admin
 */
class MakeLayoutCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('make:layout')
            ->setDescription('Create a new Layout master shell class')
            ->addArgument('name', InputArgument::REQUIRED, 'The layout class name (e.g. PortalLayout)')
            ->addOption('type', 't', InputOption::VALUE_OPTIONAL, 'The layout template type (custom, admin, app, blank)', 'custom');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io   = new SymfonyStyle($input, $output);
        $name = (string) $input->getArgument('name');
        $type = strtolower((string) ($input->getOption('type') ?? 'custom'));

        if (!str_ends_with($name, 'Layout')) {
            $name .= 'Layout';
        }

        $basePath = defined('BASE_PATH') ? BASE_PATH : getcwd();
        $targetDir = $basePath . '/app/Layouts';
        $targetFile = $targetDir . '/' . $name . '.php';

        if (file_exists($targetFile)) {
            $io->error("Layout [{$name}] already exists at app/Layouts/{$name}.php");
            return Command::FAILURE;
        }

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $content = match ($type) {
            'admin' => $this->getAdminStub($name),
            'app'   => $this->getAppStub($name),
            'blank' => $this->getBlankStub($name),
            default => $this->getCustomStub($name),
        };

        file_put_contents($targetFile, $content);

        $io->success("Layout created: app/Layouts/{$name}.php");
        $io->text("To use in any Page, declare:");
        $io->text("  protected ?string \$layout = \\App\\Layouts\\{$name}::class;");

        return Command::SUCCESS;
    }

    private function getCustomStub(string $name): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace App\Layouts;

use StackFlow\Core\Request;
use StackFlow\Layout\Layout;
use StackFlow\Page\Page;
use StackFlow\Widgets\Widget;
use StackFlow\Widgets\Layout\Scaffold;
use StackFlow\Widgets\Layout\Container;
use StackFlow\Widgets\Navigation\Navbar;

/**
 * {$name} — Master Layout Shell
 */
class {$name} extends Layout
{
    /**
     * Wrap the child page content in the layout's master widget tree.
     */
    public function wrap(Widget $content, Request \$request, Page \$page): Widget
    {
        \$props = \$page->layoutProps();
        \$brand = \$props['brand'] ?? 'StackFlow';

        return Scaffold::make([
            'appBar' => Navbar::make([
                'brand' => \$brand,
                'links' => [
                    ['label' => 'Home', 'href' => '/', 'active' => \$request->path === '/'],
                ],
            ]),
            'body' => Container::make([
                'maxWidth' => 'xl',
                'class'    => 'py-8',
                'child'    => \$content,
            ]),
        ]);
    }
}

PHP;
    }

    private function getAdminStub(string $name): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace App\Layouts;

use StackFlow\Core\Request;
use StackFlow\Layout\Layout;
use StackFlow\Page\Page;
use StackFlow\Widgets\Widget;

/**
 * {$name} — Portal / Dashboard Layout
 */
class {$name} extends Layout
{
    public function wrap(Widget \$content, Request \$request, Page \$page): Widget
    {
        return new class(\$content, \$request, \$page) extends Widget {
            public function __construct(
                private readonly Widget \$contentWidget,
                private readonly Request \$request,
                private readonly Page \$page,
            ) {
                parent::__construct();
            }

            public function render(): string
            {
                \$props = \$this->page->layoutProps();
                \$brand = \$props['brand'] ?? 'Admin Portal';
                \$title = \$props['title'] ?? \$this->page->title();
                \$contentHtml = \$this->contentWidget->render();

                return <<<HTML
                <div class="min-h-screen bg-gray-50 dark:bg-gray-950 flex flex-col antialiased">
                    <header class="h-16 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between px-6">
                        <div class="font-bold text-gray-900 dark:text-white">{\$brand}</div>
                        <div class="text-sm text-gray-500">{\$title}</div>
                    </header>
                    <main class="flex-1 max-w-7xl w-full mx-auto p-6">
                        {\$contentHtml}
                    </main>
                </div>
HTML;
            }
        };
    }
}

PHP;
    }

    private function getAppStub(string $name): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace App\Layouts;

use StackFlow\Core\Request;
use StackFlow\Layout\Layout;
use StackFlow\Page\Page;
use StackFlow\Widgets\Widget;
use StackFlow\Widgets\Layout\Scaffold;
use StackFlow\Widgets\Layout\Container;
use StackFlow\Widgets\Navigation\Navbar;

/**
 * {$name} — Application Web Layout
 */
class {$name} extends Layout
{
    public function wrap(Widget \$content, Request \$request, Page \$page): Widget
    {
        return Scaffold::make([
            'appBar' => Navbar::make([
                'brand' => 'MyApp',
                'links' => [
                    ['label' => 'Home', 'href' => '/'],
                ],
            ]),
            'body' => Container::make([
                'maxWidth' => 'xl',
                'class'    => 'py-8',
                'child'    => \$content,
            ]),
        ]);
    }
}

PHP;
    }

    private function getBlankStub(string $name): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace App\Layouts;

use StackFlow\Core\Request;
use StackFlow\Layout\Layout;
use StackFlow\Page\Page;
use StackFlow\Widgets\Widget;

/**
 * {$name} — Minimal / Blank Canvas Layout
 */
class {$name} extends Layout
{
    public function wrap(Widget \$content, Request \$request, Page \$page): Widget
    {
        return new class(\$content) extends Widget {
            public function __construct(private readonly Widget \$contentWidget)
            {
                parent::__construct();
            }

            public function render(): string
            {
                \$html = \$this->contentWidget->render();
                return <<<HTML
                <div class="min-h-screen bg-gray-50 dark:bg-gray-950 flex items-center justify-center p-4">
                    <div class="w-full max-w-md">{\$html}</div>
                </div>
HTML;
            }
        };
    }
}

PHP;
    }
}
