<?php

declare(strict_types=1);

namespace StackFlow\Database;

use Closure;

/**
 * Migration — Abstract base for all schema migrations.
 *
 * Extend this class to define an up() (apply) and down() (rollback) migration.
 * Use the protected helpers create(), table(), drop(), and dropIfExists()
 * with a Blueprint closure to define schema changes.
 *
 * Example:
 *
 *   class CreateUsersTable extends Migration
 *   {
 *       public function up(): void
 *       {
 *           $this->create('users', function (Blueprint $table) {
 *               $table->id();
 *               $table->string('name');
 *               $table->string('email')->unique();
 *               $table->string('password');
 *               $table->timestamps();
 *           });
 *       }
 *
 *       public function down(): void
 *       {
 *           $this->dropIfExists('users');
 *       }
 *   }
 */
abstract class Migration
{
    /** Named connection to run this migration against. */
    protected string $connection = 'default';

    // -------------------------------------------------------------------------
    // Abstract Interface
    // -------------------------------------------------------------------------

    /**
     * Apply the migration (create tables, add columns, etc.).
     *
     * @return void
     */
    abstract public function up(): void;

    /**
     * Reverse the migration.
     *
     * @return void
     */
    abstract public function down(): void;

    // -------------------------------------------------------------------------
    // Schema Helpers
    // -------------------------------------------------------------------------

    /**
     * Create a new table.
     *
     * @param  string  $table    Table name.
     * @param  Closure $callback Receives a Blueprint and defines columns.
     * @return void
     */
    protected function create(string $table, Closure $callback): void
    {
        $blueprint = new Blueprint($table);
        $callback($blueprint);

        $sql = $blueprint->toCreateSql();
        DB::statement($sql, connection: $this->connection);
    }

    /**
     * Modify an existing table.
     *
     * @param  string  $table
     * @param  Closure $callback Receives a Blueprint and defines alterations.
     * @return void
     */
    protected function table(string $table, Closure $callback): void
    {
        $blueprint = new Blueprint($table, alter: true);
        $callback($blueprint);

        foreach ($blueprint->toAlterSql() as $sql) {
            DB::statement($sql, connection: $this->connection);
        }
    }

    /**
     * Drop a table.
     *
     * @param  string $table
     * @return void
     */
    protected function drop(string $table): void
    {
        DB::statement("DROP TABLE `{$table}`", connection: $this->connection);
    }

    /**
     * Drop a table if it exists.
     *
     * @param  string $table
     * @return void
     */
    protected function dropIfExists(string $table): void
    {
        DB::statement("DROP TABLE IF EXISTS `{$table}`", connection: $this->connection);
    }
}

// =============================================================================
// Blueprint — Schema Definition DSL
// =============================================================================

/**
 * Blueprint collects column/index definitions and compiles them to SQL DDL.
 *
 * Column methods return a ColumnDefinition to allow chaining modifiers:
 *   $table->string('email', 191)->unique()->nullable();
 */
class Blueprint
{
    /** @var array<int, ColumnDefinition> */
    private array $columns = [];

    /** @var array<int, string>  Extra DDL constraints appended after columns. */
    private array $constraints = [];

    // -------------------------------------------------------------------------

    public function __construct(
        private readonly string $table,
        private readonly bool   $alter = false,
    ) {}

    // -------------------------------------------------------------------------
    // Column Factories
    // -------------------------------------------------------------------------

    /**
     * Add an auto-incrementing BIGINT primary key named 'id'.
     *
     * @return ColumnDefinition
     */
    public function id(): ColumnDefinition
    {
        return $this->addColumn('id', 'BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY');
    }

    /**
     * Add a VARCHAR column.
     *
     * @param  string $column
     * @param  int    $length
     * @return ColumnDefinition
     */
    public function string(string $column, int $length = 255): ColumnDefinition
    {
        return $this->addColumn($column, "VARCHAR({$length})");
    }

    /**
     * Add an INT column.
     *
     * @param  string $column
     * @return ColumnDefinition
     */
    public function integer(string $column): ColumnDefinition
    {
        return $this->addColumn($column, 'INT');
    }

    /**
     * Add a BIGINT column.
     *
     * @param  string $column
     * @return ColumnDefinition
     */
    public function bigInteger(string $column): ColumnDefinition
    {
        return $this->addColumn($column, 'BIGINT');
    }

    /**
     * Add a TEXT column.
     *
     * @param  string $column
     * @return ColumnDefinition
     */
    public function text(string $column): ColumnDefinition
    {
        return $this->addColumn($column, 'TEXT');
    }

    /**
     * Add a LONGTEXT column.
     *
     * @param  string $column
     * @return ColumnDefinition
     */
    public function longText(string $column): ColumnDefinition
    {
        return $this->addColumn($column, 'LONGTEXT');
    }

    /**
     * Add a TINYINT(1) column (maps to boolean in most drivers).
     *
     * @param  string $column
     * @return ColumnDefinition
     */
    public function boolean(string $column): ColumnDefinition
    {
        return $this->addColumn($column, 'TINYINT(1)');
    }

    /**
     * Add a DECIMAL column.
     *
     * @param  string $column
     * @param  int    $precision Total significant digits.
     * @param  int    $scale     Digits after the decimal point.
     * @return ColumnDefinition
     */
    public function decimal(string $column, int $precision = 8, int $scale = 2): ColumnDefinition
    {
        return $this->addColumn($column, "DECIMAL({$precision},{$scale})");
    }

    /**
     * Add a FLOAT column.
     *
     * @param  string $column
     * @return ColumnDefinition
     */
    public function float(string $column): ColumnDefinition
    {
        return $this->addColumn($column, 'FLOAT');
    }

    /**
     * Add a TIMESTAMP column.
     *
     * @param  string $column
     * @return ColumnDefinition
     */
    public function timestamp(string $column): ColumnDefinition
    {
        return $this->addColumn($column, 'TIMESTAMP');
    }

    /**
     * Add a DATE column.
     *
     * @param  string $column
     * @return ColumnDefinition
     */
    public function date(string $column): ColumnDefinition
    {
        return $this->addColumn($column, 'DATE');
    }

    /**
     * Add a DATETIME column.
     *
     * @param  string $column
     * @return ColumnDefinition
     */
    public function dateTime(string $column): ColumnDefinition
    {
        return $this->addColumn($column, 'DATETIME');
    }

    /**
     * Add JSON column.
     *
     * @param  string $column
     * @return ColumnDefinition
     */
    public function json(string $column): ColumnDefinition
    {
        return $this->addColumn($column, 'JSON');
    }

    /**
     * Add nullable `created_at` and `updated_at` TIMESTAMP columns.
     *
     * @return void
     */
    public function timestamps(): void
    {
        $this->addColumn('created_at', 'TIMESTAMP')->nullable();
        $this->addColumn('updated_at', 'TIMESTAMP')->nullable();
    }

    /**
     * Add a nullable `deleted_at` TIMESTAMP column for soft-deletes.
     *
     * @return void
     */
    public function softDeletes(): void
    {
        $this->addColumn('deleted_at', 'TIMESTAMP')->nullable();
    }

    /**
     * Add an UNSIGNED BIGINT foreign-key column and return a ForeignKeyDefinition
     * for fluent ->references('id')->on('users') chaining.
     *
     * @param  string $column
     * @return ForeignKeyDefinition
     */
    public function foreign(string $column): ForeignKeyDefinition
    {
        $colDef = $this->addColumn($column, 'BIGINT UNSIGNED');

        return new ForeignKeyDefinition($this, $column, $colDef);
    }

    // -------------------------------------------------------------------------
    // Constraint Helpers (called internally by ForeignKeyDefinition)
    // -------------------------------------------------------------------------

    /**
     * @internal
     * @param  string $sql
     * @return void
     */
    public function addConstraint(string $sql): void
    {
        $this->constraints[] = $sql;
    }

    // -------------------------------------------------------------------------
    // SQL Compilation
    // -------------------------------------------------------------------------

    /**
     * Compile the blueprint into a CREATE TABLE statement.
     *
     * @return string
     */
    public function toCreateSql(): string
    {
        $parts = array_map(
            fn(ColumnDefinition $col) => '    ' . $col->toSql(),
            $this->columns,
        );

        foreach ($this->constraints as $constraint) {
            $parts[] = '    ' . $constraint;
        }

        $body = implode(",\n", $parts);

        return "CREATE TABLE `{$this->table}` (\n{$body}\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    }

    /**
     * Compile each column into an ALTER TABLE … ADD COLUMN statement.
     *
     * @return array<int, string>
     */
    public function toAlterSql(): array
    {
        $statements = [];

        foreach ($this->columns as $col) {
            $statements[] = "ALTER TABLE `{$this->table}` ADD COLUMN " . $col->toSql();
        }

        foreach ($this->constraints as $constraint) {
            $statements[] = "ALTER TABLE `{$this->table}` ADD {$constraint}";
        }

        return $statements;
    }

    // -------------------------------------------------------------------------
    // Internal
    // -------------------------------------------------------------------------

    /**
     * @param  string $name
     * @param  string $type  Raw SQL type fragment.
     * @return ColumnDefinition
     */
    private function addColumn(string $name, string $type): ColumnDefinition
    {
        $col             = new ColumnDefinition($name, $type);
        $this->columns[] = $col;

        return $col;
    }
}

// =============================================================================
// ColumnDefinition — Single column with chainable modifiers
// =============================================================================

/**
 * Represents a single table column with chainable modifier methods.
 */
class ColumnDefinition
{
    private bool   $nullable  = false;
    private bool   $unsigned  = false;
    private bool   $uniqueFlag = false;
    private bool   $indexFlag  = false;
    private mixed  $defaultValue = null;
    private bool   $hasDefault  = false;

    public function __construct(
        public readonly string $name,
        private string         $type,
    ) {}

    // -------------------------------------------------------------------------
    // Modifiers
    // -------------------------------------------------------------------------

    /**
     * Allow NULL values for this column.
     *
     * @return static
     */
    public function nullable(): static
    {
        $this->nullable = true;

        return $this;
    }

    /**
     * Set a default value.
     *
     * @param  mixed $value
     * @return static
     */
    public function default(mixed $value): static
    {
        $this->defaultValue = $value;
        $this->hasDefault   = true;

        return $this;
    }

    /**
     * Add a UNIQUE constraint on this column.
     *
     * @return static
     */
    public function unique(): static
    {
        $this->uniqueFlag = true;

        return $this;
    }

    /**
     * Add a plain INDEX on this column.
     *
     * @return static
     */
    public function index(): static
    {
        $this->indexFlag = true;

        return $this;
    }

    /**
     * Mark integer columns as UNSIGNED.
     *
     * @return static
     */
    public function unsigned(): static
    {
        $this->unsigned = true;

        return $this;
    }

    // -------------------------------------------------------------------------
    // SQL Compilation
    // -------------------------------------------------------------------------

    /**
     * Compile to a SQL column definition fragment.
     *
     * @return string
     */
    public function toSql(): string
    {
        $sql = "`{$this->name}` {$this->type}";

        if ($this->unsigned && !str_contains($this->type, 'UNSIGNED')) {
            $sql .= ' UNSIGNED';
        }

        $sql .= $this->nullable ? ' NULL' : ' NOT NULL';

        if ($this->hasDefault) {
            $default = is_string($this->defaultValue)
                ? "'" . addslashes((string) $this->defaultValue) . "'"
                : (is_null($this->defaultValue) ? 'NULL' : (string) $this->defaultValue);

            $sql .= " DEFAULT {$default}";
        }

        if ($this->uniqueFlag) {
            $sql .= " UNIQUE";
        }

        return $sql;
    }
}

// =============================================================================
// ForeignKeyDefinition — Fluent foreign-key constraint builder
// =============================================================================

/**
 * Builds a FOREIGN KEY constraint via references()->on() chaining.
 *
 * Usage:
 *   $table->foreign('user_id')->references('id')->on('users');
 */
class ForeignKeyDefinition
{
    private ?string $referencedColumn = null;

    public function __construct(
        private readonly Blueprint        $blueprint,
        private readonly string           $column,
        private readonly ColumnDefinition $columnDefinition,
    ) {}

    /**
     * Specify the referenced column name.
     *
     * @param  string $column
     * @return static
     */
    public function references(string $column): static
    {
        $this->referencedColumn = $column;

        return $this;
    }

    /**
     * Specify the referenced table and register the constraint on the Blueprint.
     *
     * @param  string $table
     * @return ColumnDefinition  The original column definition for further chaining.
     */
    public function on(string $table): ColumnDefinition
    {
        $refCol = $this->referencedColumn ?? 'id';

        $this->blueprint->addConstraint(
            "FOREIGN KEY (`{$this->column}`) REFERENCES `{$table}` (`{$refCol}`)"
        );

        return $this->columnDefinition;
    }
}
