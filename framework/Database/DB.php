<?php

declare(strict_types=1);

namespace StackFlow\Database;

use PDO;
use StackFlow\Database\Drivers\MySQLDriver;
use StackFlow\Database\Drivers\SQLiteDriver;

/**
 * DB — Static connection manager and query entry point.
 *
 * Usage:
 *   DB::configure($config);                  // usually done in bootstrap
 *   DB::table('users')->where('active', 1)->get();
 *   DB::raw('SELECT * FROM users WHERE id = ?', [1]);
 */
class DB
{
    // -------------------------------------------------------------------------
    // State
    // -------------------------------------------------------------------------

    /** @var array<string, array<string, mixed>> Registered connection configs. */
    private static array $config = [];

    /** @var array<string, PDO> Lazily-built live PDO instances keyed by name. */
    private static array $connections = [];

    // -------------------------------------------------------------------------
    // Configuration
    // -------------------------------------------------------------------------

    /**
     * Register database configurations.
     *
     * The $config array mirrors `app/config/database.php`:
     *
     *   [
     *     'default' => 'mysql',
     *     'connections' => [
     *       'mysql'  => ['driver'=>'mysql',  'host'=>'...', ...],
     *       'sqlite' => ['driver'=>'sqlite', 'database'=>'...'],
     *     ],
     *   ]
     *
     * @param  array<string, mixed> $config
     * @return void
     */
    public static function configure(array $config): void
    {
        $connections = $config['connections'] ?? [];
        $default     = (string) ($config['default'] ?? 'default');

        // Re-key so 'default' always resolves.
        foreach ($connections as $name => $connectionConfig) {
            self::$config[(string) $name] = (array) $connectionConfig;
        }

        // Map the 'default' alias if a different name is marked as default.
        if ($default !== 'default' && isset(self::$config[$default])) {
            self::$config['default'] = self::$config[$default];
        }
    }

    // -------------------------------------------------------------------------
    // Connection
    // -------------------------------------------------------------------------

    /**
     * Return (or lazily create) a PDO connection by name.
     *
     * @param  string $name Connection name (matches a key in 'connections').
     * @return PDO
     *
     * @throws \InvalidArgumentException When the connection name is not configured.
     * @throws \RuntimeException         When the driver is unsupported.
     */
    public static function connection(string $name = 'default'): PDO
    {
        if (isset(self::$connections[$name])) {
            return self::$connections[$name];
        }

        if (!isset(self::$config[$name])) {
            throw new \InvalidArgumentException(
                "Database connection [{$name}] is not configured."
            );
        }

        $connectionConfig = self::$config[$name];
        $driver           = strtolower((string) ($connectionConfig['driver'] ?? 'mysql'));

        $pdo = match ($driver) {
            'mysql'  => (new MySQLDriver())->connect($connectionConfig),
            'sqlite' => (new SQLiteDriver())->connect($connectionConfig),
            default  => throw new \RuntimeException(
                "Unsupported database driver [{$driver}]."
            ),
        };

        self::$connections[$name] = $pdo;

        return $pdo;
    }

    /**
     * Close and forget a connection (useful in tests or long-running processes).
     *
     * @param  string $name Connection name.
     * @return void
     */
    public static function disconnect(string $name = 'default'): void
    {
        unset(self::$connections[$name]);
    }

    /**
     * Close all open connections.
     *
     * @return void
     */
    public static function disconnectAll(): void
    {
        self::$connections = [];
    }

    // -------------------------------------------------------------------------
    // Fluent Entry Points
    // -------------------------------------------------------------------------

    /**
     * Begin a fluent query on a table.
     *
     * @param  string $table    Table name.
     * @param  string $connection Connection name (default: 'default').
     * @return QueryBuilder
     */
    public static function table(string $table, string $connection = 'default'): QueryBuilder
    {
        return new QueryBuilder(
            pdo:   self::connection($connection),
            table: $table,
        );
    }

    // -------------------------------------------------------------------------
    // Raw Queries
    // -------------------------------------------------------------------------

    /**
     * Execute a raw SELECT query and return all results as stdClass objects.
     *
     * @param  string               $sql
     * @param  array<int|string, mixed> $bindings
     * @param  string               $connection
     * @return array<int, object>
     */
    public static function raw(
        string $sql,
        array  $bindings    = [],
        string $connection  = 'default',
    ): array {
        $pdo  = self::connection($connection);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($bindings);

        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Execute a raw non-SELECT statement (INSERT / UPDATE / DELETE / DDL).
     *
     * @param  string               $sql
     * @param  array<int|string, mixed> $bindings
     * @param  string               $connection
     * @return bool                 True on success.
     */
    public static function statement(
        string $sql,
        array  $bindings    = [],
        string $connection  = 'default',
    ): bool {
        $pdo  = self::connection($connection);
        $stmt = $pdo->prepare($sql);

        return $stmt->execute($bindings);
    }

    // -------------------------------------------------------------------------
    // Transactions
    // -------------------------------------------------------------------------

    /**
     * Begin a database transaction.
     *
     * @param  string $connection
     * @return void
     */
    public static function beginTransaction(string $connection = 'default'): void
    {
        self::connection($connection)->beginTransaction();
    }

    /**
     * Commit the current transaction.
     *
     * @param  string $connection
     * @return void
     */
    public static function commit(string $connection = 'default'): void
    {
        self::connection($connection)->commit();
    }

    /**
     * Roll back the current transaction.
     *
     * @param  string $connection
     * @return void
     */
    public static function rollback(string $connection = 'default'): void
    {
        self::connection($connection)->rollBack();
    }

    /**
     * Execute a callable inside a transaction; auto-commits or rolls back.
     *
     * @param  callable $callback Receives no arguments.
     * @param  string   $connection
     * @return mixed    The return value of $callback.
     *
     * @throws \Throwable Re-throws any exception after rolling back.
     */
    public static function transaction(callable $callback, string $connection = 'default'): mixed
    {
        self::beginTransaction($connection);

        try {
            $result = $callback();
            self::commit($connection);

            return $result;
        } catch (\Throwable $e) {
            self::rollback($connection);
            throw $e;
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Return the last inserted ID from the given connection.
     *
     * @param  string      $connection
     * @param  string|null $name       Sequence name (PostgreSQL-style, ignored by MySQL/SQLite).
     * @return string
     */
    public static function lastInsertId(string $connection = 'default', ?string $name = null): string
    {
        return self::connection($connection)->lastInsertId($name);
    }
}
