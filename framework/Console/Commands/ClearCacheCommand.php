<?php

declare(strict_types=1);

namespace StackFlow\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * cache:clear — Clear cached application files and temporary storage
 *
 * Usage: php stackflow cache:clear
 */
class ClearCacheCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('cache:clear')
            ->setDescription('Clear application cache and temporary files');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $storageCache = BASE_PATH . '/storage/cache';
        if (is_dir($storageCache)) {
            $files = glob($storageCache . '/*');
            if ($files) {
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
            }
        }

        $io->success("Application cache cleared successfully!");

        return Command::SUCCESS;
    }
}
