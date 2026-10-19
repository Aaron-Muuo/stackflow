<?php

declare(strict_types=1);

namespace StackFlow\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * serve — Start the PHP development server from the project root
 *
 * Emulates `php artisan serve` for StackFlow. Runs from the root directory
 * using `server.php` to route requests to `public/index.php` and serve static assets.
 *
 * Usage:
 *   php stackflow serve
 *   php stackflow serve --port=8080 --host=0.0.0.0
 */
class ServeCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('serve')
            ->setDescription('Serve the application on the PHP development server')
            ->addOption('host', null, InputOption::VALUE_OPTIONAL, 'The host address to serve the application on', '127.0.0.1')
            ->addOption('port', 'p', InputOption::VALUE_OPTIONAL, 'The port to serve the application on', '8000')
            ->addOption('tries', null, InputOption::VALUE_OPTIONAL, 'The max number of ports to attempt to serve on', '10');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $host = (string) $input->getOption('host');
        $port = (int) $input->getOption('port');
        $tries = (int) $input->getOption('tries');

        $serverFile = BASE_PATH . '/server.php';

        if (!file_exists($serverFile)) {
            $io->error("Missing server.php router file in project root.");
            return Command::FAILURE;
        }

        // Check port availability and auto-increment if taken
        $port = $this->findAvailablePort($host, $port, $tries);

        $url = "http://{$host}:{$port}";

        $output->writeln('');
        $output->writeln("  <fg=black;bg=green;options=bold> INFO </> Server running on [<href={$url}>{$url}</>].");
        $output->writeln('');
        $output->writeln('  <fg=gray>Press Ctrl+C to stop the server</>');
        $output->writeln('');

        // Run PHP built-in web server from project root with server.php as the routing script
        $command = sprintf(
            'php -S %s:%d %s',
            escapeshellarg($host),
            $port,
            escapeshellarg($serverFile)
        );

        passthru($command, $status);

        return $status === 0 ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * Check if a port is in use and find an available one.
     */
    private function findAvailablePort(string $host, int $port, int $tries): int
    {
        for ($i = 0; $i < $tries; $i++) {
            $currentPort = $port + $i;
            $connection = @fsockopen($host, $currentPort);

            if (is_resource($connection)) {
                fclose($connection);
                continue; // Port is busy, try next
            }

            return $currentPort;
        }

        return $port;
    }
}
