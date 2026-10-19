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
 * make:page — Generate a new Page class
 *
 * Usage:
 *   php stackflow make:page LoginPage
 *   php stackflow make:page DashboardPage --layout=admin
 *   php stackflow make:page ProfilePage --layout=app
 */
class MakePageCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('make:page')
            ->setDescription('Create a new Page class')
            ->addArgument('name', InputArgument::REQUIRED, 'The page class name (e.g. LoginPage)')
            ->addOption('layout', 'l', InputOption::VALUE_OPTIONAL, 'The parent layout to extend (admin, app, blank, or layout class name)', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io     = new SymfonyStyle($input, $output);
        $name   = (string) $input->getArgument('name');
        $layout = $input->getOption('layout');

        // Normalize name
        if (!str_ends_with($name, 'Page')) {
            $name .= 'Page';
        }

        $basePath = defined('BASE_PATH') ? BASE_PATH : getcwd();
        $path = $basePath . '/app/Pages/' . $name . '.php';

        if (file_exists($path)) {
            $io->error("Page [{$name}] already exists at app/Pages/{$name}.php");
            return Command::FAILURE;
        }

        $stub = $this->getStub($name, $layout ? (string) $layout : null);
        @mkdir(dirname($path), 0755, true);
        file_put_contents($path, $stub);

        $io->success("Page created: app/Pages/{$name}.php");
        $io->text("Register it in routes/web.php:");
        $io->text("  Router::get('/" . strtolower(str_replace('Page', '', $name)) . "', App\\Pages\\{$name}::class);");

        return Command::SUCCESS;
    }

    private function getStub(string $name, ?string $layout): string
    {
        $routeName = strtolower(str_replace('Page', '', $name));

        if ($layout !== null && $layout !== '') {
            $layoutClass = match (strtolower($layout)) {
                'admin' => '\\App\\Layouts\\AdminLayout::class',
                'app'   => '\\App\\Layouts\\AppLayout::class',
                'blank' => '\\App\\Layouts\\BlankLayout::class',
                default => str_starts_with($layout, '\\') ? "{$layout}::class" : "\\App\\Layouts\\{$layout}::class",
            };

            return <<<PHP
<?php

declare(strict_types=1);

namespace App\Pages;

use StackFlow\Core\Request;
use StackFlow\Page\Page;
use StackFlow\Widgets\Widget;
use StackFlow\Widgets\Layout\Column;
use StackFlow\Widgets\Display\Text;
use StackFlow\Widgets\Container\Card;

class {$name} extends Page
{
    /**
     * Parent layout master shell.
     */
    protected ?string \$layout = {$layoutClass};

    public function title(): string
    {
        return '{$routeName}';
    }

    /**
     * Content rendered inside the parent layout shell.
     */
    public function content(Request \$request): ?Widget
    {
        return Column::make([
            'gap' => 'gap-6',
            'children' => [
                Text::make([
                    'value'  => '{$routeName}',
                    'tag'    => 'h1',
                    'size'   => 'text-2xl sm:text-3xl',
                    'weight' => 'font-bold',
                ]),
                Card::make([
                    'child' => Text::make([
                        'value' => 'This page is extending the parent layout.',
                        'color' => 'text-gray-600 dark:text-gray-400',
                    ]),
                ]),
            ],
        ]);
    }
}
PHP;
        }

        return <<<PHP
<?php

declare(strict_types=1);

namespace App\Pages;

use StackFlow\Core\Request;
use StackFlow\Page\Page;
use StackFlow\Widgets\Widget;
use StackFlow\Widgets\Layout\Scaffold;
use StackFlow\Widgets\Layout\Column;
use StackFlow\Widgets\Display\Text;
use StackFlow\Widgets\Navigation\Navbar;

class {$name} extends Page
{
    public function title(): string
    {
        return '{$routeName}';
    }

    public function build(Request \$request): Widget
    {
        return Scaffold::make([
            'appBar' => Navbar::make([
                'brand' => '{$routeName}',
                'links' => [
                    ['label' => 'Home', 'href' => '/'],
                ],
            ]),
            'body' => Column::make([
                'children' => [
                    Text::make([
                        'value' => 'Welcome to {$routeName}!',
                        'tag'   => 'h1',
                        'size'  => 'text-3xl',
                        'weight' => 'font-bold',
                    ]),
                ],
            ]),
        ]);
    }
}
PHP;
    }
}
