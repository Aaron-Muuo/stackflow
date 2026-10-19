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
 * migrate — Run pending database migrations
 *
 * Usage: php stackflow migrate
 */
class MigrateCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('migrate')
            ->setDescription('Run all pending database migrations');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Ensure Config & DB are initialized
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

        $this->ensureMigrationTable($pdo);

        $migrationsDir = BASE_PATH . '/app/migrations';
        if (!is_dir($migrationsDir)) {
            $io->info("No migrations directory found at app/migrations.");
            return Command::SUCCESS;
        }

        $files = glob($migrationsDir . '/*.php');
        if (empty($files)) {
            $io->info("No migrations found.");
            return Command::SUCCESS;
        }

        sort($files);

        // Fetch already ran migrations
        $stmt = $pdo->query("SELECT migration FROM migrations");
        $ran = $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];

        // Determine current max batch
        $batchStmt = $pdo->query("SELECT MAX(batch) as max_batch FROM migrations");
        $maxBatch = $batchStmt ? (int) $batchStmt->fetch(PDO::FETCH_OBJ)?->max_batch : 0;
        $nextBatch = $maxBatch + 1;

        $count = 0;

        foreach ($files as $file) {
            $migrationName = basename($file, '.php');

            if (in_array($migrationName, $ran, true)) {
                continue;
            }

            $io->text("Migrating: <comment>{$migrationName}</comment>");

            require_once $file;

            // Extract class name from filename: e.g. 2026_10_18_120000_create_users_table -> CreateUsersTable
            $pureName = preg_replace('/^\d{4}_\d{2}_\d{2}_\d{6}_/', '', $migrationName);
            $className = str_replace(' ', '', ucwords(str_replace('_', ' ', $pureName)));

            if (!class_exists($className)) {
                $io->error("Migration class [{$className}] not found in {$file}");
                return Command::FAILURE;
            }

            /** @var \StackFlow\Database\Migration $migration */
            $migration = new $className();
            $migration->up();

            // Record migration
            $insertStmt = $pdo->prepare("INSERT INTO migrations (migration, batch) VALUES (?, ?)");
            $insertStmt->execute([$migrationName, $nextBatch]);

            $io->text("Migrated:  <info>{$migrationName}</info>");
            $count++;
        }

        if ($count === 0) {
            $io->info("Nothing to migrate.");
        } else {
            $io->success("Successfully ran {$count} migration(s).");
        }

        return Command::SUCCESS;
    }

    private function ensureMigrationTable(PDO $pdo): void
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            $sql = "CREATE TABLE IF NOT EXISTS migrations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                migration VARCHAR(255) NOT NULL,
                batch INTEGER NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )";
        } else {
            $sql = "CREATE TABLE IF NOT EXISTS `migrations` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `migration` VARCHAR(255) NOT NULL,
                `batch` INT NOT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        }

        $pdo->exec($sql);
    }
}
