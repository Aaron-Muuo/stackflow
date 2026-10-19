<?php

declare(strict_types=1);

namespace StackFlow\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * make:widget — Generate a custom Widget or override an existing Widget
 *
 * Usage:
 *   php stackflow make:widget HeaderWidget
 *   php stackflow make:widget PrimaryButton --extends=Button
 */
class MakeWidgetCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('make:widget')
            ->setDescription('Create a custom widget class')
            ->addArgument('name', InputArgument::REQUIRED, 'The widget class name (e.g. HeaderWidget)')
            ->addOption('extends', 'e', InputOption::VALUE_OPTIONAL, 'The widget class to extend/override (e.g. Button, Card, Widget)', 'Widget');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $name = ucfirst($input->getArgument('name'));
        $extends = ucfirst((string) $input->getOption('extends'));

        $dir = BASE_PATH . '/app/Widgets';
        $path = $dir . '/' . $name . '.php';

        if (file_exists($path)) {
            $io->error("Widget [{$name}] already exists at app/Widgets/{$name}.php");
            return Command::FAILURE;
        }

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $stub = $this->getStub($name, $extends);
        file_put_contents($path, $stub);

        $io->success("Widget created: app/Widgets/{$name}.php");
        if ($extends !== 'Widget') {
            $io->text("This widget extends <comment>{$extends}</comment>, allowing you to override its render() method and custom Tailwind styling.");
        }

        return Command::SUCCESS;
    }

    private function getStub(string $name, string $extends): string
    {
        $import = match ($extends) {
            'Button' => "use StackFlow\\Widgets\\Input\\Button as Base{$extends};",
            'Input' => "use StackFlow\\Widgets\\Input\\Input as Base{$extends};",
            'Card' => "use StackFlow\\Widgets\\Container\\Card as Base{$extends};",
            'Navbar' => "use StackFlow\\Widgets\\Navigation\\Navbar as Base{$extends};",
            default => "use StackFlow\\Widgets\\Widget as BaseWidget;",
        };

        $baseClass = ($extends === 'Widget') ? 'BaseWidget' : "Base{$extends}";

        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace App\Widgets;

        {$import}

        /**
         * {$name} Widget
         *
         * Extends {$extends}. Customize your HTML output and Tailwind classes here.
         */
        class {$name} extends {$baseClass}
        {
            /**
             * Render the widget to an HTML string.
             */
            public function render(): string
            {
                // Retrieve props passed via make(['label' => '...'])
                \$label = \$this->prop('label', 'Default Text');
                \$extraClass = \$this->prop('class', '');

                \$classes = \$this->classes(
                    'p-4 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-medium transition-colors',
                    \$extraClass
                );

                return "<div class=\"{\$classes}\">{\$this->e(\$label)}</div>";
            }
        }
        PHP;
    }
}
