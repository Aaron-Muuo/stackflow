<?php

declare(strict_types=1);

namespace StackFlow\Database\Drivers;

use PDO;

/**
 * SQLite PDO driver factory.
 *
 * Builds a PDO connection configured for SQLite (file-based or in-memory).
 * Also enables WAL mode and foreign-key enforcement for file databases.
 */
class SQLiteDriver
{
    // -------------------------------------------------------------------------
    // Connection
    // -------------------------------------------------------------------------

    /**
     * Build and return a PDO connection for SQLite.
     *
     * Supported $config keys:
     *   database – absolute file path OR ':memory:' (required)
     *
     * @param  array<string, mixed> $config
     * @return PDO
     */
    public function connect(array $config): PDO
    {
        $database = (string) ($config['database'] ?? ':memory:');

        $dsn = "sqlite:{$database}";

        $pdo = new PDO($dsn, options: $this->defaultOptions());

        // Enable foreign-key constraints (SQLite ignores them by default).
        $pdo->exec('PRAGMA foreign_keys = ON;');

        // WAL journal mode improves concurrent read performance on file DBs.
        if ($database !== ':memory:') {
            $pdo->exec('PRAGMA journal_mode = WAL;');
        }

        return $pdo;
    }

    // -------------------------------------------------------------------------
    // Internals
    // -------------------------------------------------------------------------

    /**
     * Default PDO options applied to every SQLite connection.
     *
     * @return array<int, mixed>
     */
    private function defaultOptions(): array
    {
        return [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
    }
}
