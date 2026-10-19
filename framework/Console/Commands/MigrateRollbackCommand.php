<?php

declare(strict_types=1);

namespace StackFlow\Console\Commands;

use PDO;
use StackFlow\Core\Config;
use StackFlow\Database\DB;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * migrate:rollback — Rollback the last database migration batch
 *
 * Usage: php stackflow migrate:rollback
 */
class MigrateRollbackCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('migrate:rollback')
            ->setDescription('Rollback the last batch of database migrations');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        Config::load(BASE_PATH);
        $dbConfig = Config::get('database');
        if (is_array($dbConfig)) {
            DB::configure($dbConfig);
        }

        try {
            $pdo = DB::connection();
        } catch (\Throwable $e) {
            $io->error("Database connection failed: " . $e->getMessage());
            return Command::FAILURE;
        }

        // Get last batch
        $batchStmt = $pdo->query("SELECT MAX(batch) as max_batch FROM migrations");
        $lastBatch = $batchStmt ? (int) $batchStmt->fetch(PDO::FETCH_OBJ)?->max_batch : 0;

        if ($lastBatch === 0) {
            $io->info("No migrations to rollback.");
            return Command::SUCCESS;
        }

        $stmt = $pdo->prepare("SELECT migration FROM migrations WHERE batch = ? ORDER BY id DESC");
        $stmt->execute([$lastBatch]);
        $migrations = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $migrationsDir = BASE_PATH . '/app/migrations';
        $count = 0;

        foreach ($migrations as $migrationName) {
            $file = $migrationsDir . '/' . $migrationName . '.php';

            if (!file_exists($file)) {
                $io->warning("Migration file [{$file}] not found, skipping down().");
                continue;
            }

            require_once $file;

            $pureName = preg_replace('/^\d{4}_\d{2}_\d{2}_\d{6}_/', '', $migrationName);
            $className = str_replace(' ', '', ucwords(str_replace('_', ' ', $pureName)));

            if (class_exists($className)) {
                $io->text("Rolling back: <comment>{$migrationName}</comment>");
                /** @var \StackFlow\Database\Migration $instance */
                $instance = new $className();
                $instance->down();
            }

            $delStmt = $pdo->prepare("DELETE FROM migrations WHERE migration = ?");
            $delStmt->execute([$migrationName]);

            $io->text("Rolled back:  <info>{$migrationName}</info>");
            $count++;
        }

        $io->success("Successfully rolled back {$count} migration(s).");

        return Command::SUCCESS;
    }
}
