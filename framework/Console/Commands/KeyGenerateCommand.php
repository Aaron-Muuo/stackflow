<?php

declare(strict_types=1);

namespace StackFlow\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * key:generate — Generate a secure application key
 *
 * Usage: php stackflow key:generate
 */
class KeyGenerateCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('key:generate')
            ->setDescription('Set the application key in the environment file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $key = 'base64:' . base64_encode(random_bytes(32));

        $envPath = BASE_PATH . '/.env';
        if (!file_exists($envPath)) {
            $io->error(".env file does not exist. Create it first or copy from .env.example.");
            return Command::FAILURE;
        }

        $envContent = file_get_contents($envPath);
        if ($envContent === false) {
            $io->error("Failed to read .env file.");
            return Command::FAILURE;
        }

        if (preg_match('/^APP_KEY=.*$/m', $envContent)) {
            $envContent = preg_replace('/^APP_KEY=.*$/m', "APP_KEY={$key}", $envContent);
        } else {
            $envContent .= "\nAPP_KEY={$key}\n";
        }

        file_put_contents($envPath, $envContent);

        $io->success("Application key set successfully: [{$key}]");

        return Command::SUCCESS;
    }
}
