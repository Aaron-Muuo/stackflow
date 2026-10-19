<?php

declare(strict_types=1);

namespace StackFlow\Database\Drivers;

use PDO;

/**
 * MySQL PDO driver factory.
 *
 * Builds a PDO connection configured for MySQL/MariaDB with sensible
 * defaults (utf8mb4 charset, exception error mode, native-type emulation off).
 */
class MySQLDriver
{
    // -------------------------------------------------------------------------
    // Connection
    // -------------------------------------------------------------------------

    /**
     * Build and return a PDO connection for MySQL.
     *
     * Supported $config keys:
     *   host     – server hostname (default: 127.0.0.1)
     *   port     – TCP port       (default: 3306)
     *   database – database name  (required)
     *   username – DB user        (required)
     *   password – DB password    (default: '')
     *   charset  – charset        (default: utf8mb4)
     *   options  – extra PDO options array (merged on top of defaults)
     *
     * @param  array<string, mixed> $config
     * @return PDO
     */
    public function connect(array $config): PDO
    {
        $dsn = $this->buildDsn($config);

        $username = (string) ($config['username'] ?? '');
        $password = (string) ($config['password'] ?? '');

        $options = $this->defaultOptions();

        if (isset($config['options']) && is_array($config['options'])) {
            $options = $config['options'] + $options;   // user options take precedence
        }

        $pdo = new PDO($dsn, $username, $password, $options);

        // Apply charset at session level as well for robustness.
        $charset = (string) ($config['charset'] ?? 'utf8mb4');
        $pdo->exec("SET NAMES '{$charset}'");

        return $pdo;
    }

    // -------------------------------------------------------------------------
    // Internals
    // -------------------------------------------------------------------------

    /**
     * Build the MySQL DSN string.
     *
     * @param  array<string, mixed> $config
     * @return string
     */
    private function buildDsn(array $config): string
    {
        $host    = (string) ($config['host']     ?? '127.0.0.1');
        $port    = (int)    ($config['port']     ?? 3306);
        $dbname  = (string) ($config['database'] ?? '');
        $charset = (string) ($config['charset']  ?? 'utf8mb4');

        return "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";
    }

    /**
     * Default PDO options applied to every MySQL connection.
     *
     * @return array<int, mixed>
     */
    private function defaultOptions(): array
    {
        return [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_PERSISTENT         => false,
        ];
    }
}
