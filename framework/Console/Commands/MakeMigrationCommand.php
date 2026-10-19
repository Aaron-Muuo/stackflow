<?php

declare(strict_types=1);

namespace StackFlow\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * make:migration — Generate a new database migration file
 *
 * Usage: php stackflow make:migration create_users_table
 */
class MakeMigrationCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('make:migration')
            ->setDescription('Create a new migration file')
            ->addArgument('name', InputArgument::REQUIRED, 'The migration name (e.g. create_users_table)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $name = strtolower(trim($input->getArgument('name')));

        $table = '';
        if (preg_match('/^create_(.+)_table$/', $name, $matches)) {
            $table = $matches[1];
        } else {
            $table = $name;
        }

        $timestamp = date('Y_m_d_His');
        $fileName = "{$timestamp}_{$name}.php";
        $dir = BASE_PATH . '/app/migrations';
        $path = $dir . '/' . $fileName;

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $className = str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));

        $stub = <<<PHP
        <?php

        declare(strict_types=1);

        use StackFlow\Database\Migration;
        use StackFlow\Database\Blueprint;

        class {$className} extends Migration
        {
            public function up(): void
            {
                \$this->create('{$table}', function (Blueprint \$table) {
                    \$table->id();
                    \$table->string('name');
                    \$table->timestamps();
                });
            }

            public function down(): void
            {
                \$this->dropIfExists('{$table}');
            }
        }
        PHP;

        file_put_contents($path, $stub);

        $io->success("Created Migration: app/migrations/{$fileName}");

        return Command::SUCCESS;
    }
}
