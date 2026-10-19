<?php

declare(strict_types=1);

namespace StackFlow\Database;

use PDO;
use PDOStatement;

/**
 * QueryBuilder — Fluent, PDO-backed SQL query builder.
 *
 * All value bindings go through PDO prepared statements; raw string
 * interpolation for user-supplied values is never used.
 *
 * Example:
 *   DB::table('users')
 *     ->select('id', 'name')
 *     ->where('active', '=', 1)
 *     ->orderBy('name')
 *     ->limit(10)
 *     ->get();
 */
class QueryBuilder
{
    // -------------------------------------------------------------------------
    // State
    // -------------------------------------------------------------------------

    /** Columns to SELECT (empty = *). */
    protected array $selects = [];

    /** WHERE clauses [ ['type', 'col', 'op', 'val'|null] ]. */
    protected array $wheres = [];

    /** ORDER BY clauses [ ['col', 'dir'] ]. */
    protected array $orderBys = [];

    /** JOIN clauses [ ['type', 'table', 'first', 'op', 'second'] ]. */
    protected array $joins = [];

    /** Collected positional PDO bindings (in build order). */
    protected array $bindings = [];

    protected ?int $limit  = null;
    protected ?int $offset = null;

    // -------------------------------------------------------------------------
    // Constructor
    // -------------------------------------------------------------------------

    public function __construct(
        protected readonly PDO    $pdo,
        public readonly string    $table,
    ) {}

    // -------------------------------------------------------------------------
    // SELECT
    // -------------------------------------------------------------------------

    /**
     * Specify columns to retrieve.
     *
     * @param  string ...$columns
     * @return static
     */
    public function select(string ...$columns): static
    {
        $clone          = clone $this;
        $clone->selects = array_merge($clone->selects, $columns);

        return $clone;
    }

    // -------------------------------------------------------------------------
    // WHERE
    // -------------------------------------------------------------------------

    /**
     * Add a basic WHERE condition.
     *
     * Two-argument shorthand: where('col', 'val') → col = val
     *
     * @param  string $column
     * @param  string $operatorOrValue  Operator ('=', '<', '>', 'LIKE', …) or value.
     * @param  mixed  $value            Value when three arguments are passed.
     * @return static
     */
    public function where(string $column, mixed $operatorOrValue, mixed $value = null): static
    {
        [$operator, $boundValue] = $this->parseOperatorAndValue($operatorOrValue, $value);

        $clone          = clone $this;
        $clone->wheres  = [...$clone->wheres, ['AND', $column, $operator, $boundValue]];

        return $clone;
    }

    /**
     * Add an OR WHERE condition.
     *
     * @param  string $column
     * @param  mixed  $operatorOrValue
     * @param  mixed  $value
     * @return static
     */
    public function orWhere(string $column, mixed $operatorOrValue, mixed $value = null): static
    {
        [$operator, $boundValue] = $this->parseOperatorAndValue($operatorOrValue, $value);

        $clone         = clone $this;
        $clone->wheres = [...$clone->wheres, ['OR', $column, $operator, $boundValue]];

        return $clone;
    }

    /**
     * Add a WHERE IN (…) condition.
     *
     * @param  string       $column
     * @param  array<mixed> $values
     * @return static
     */
    public function whereIn(string $column, array $values): static
    {
        $clone         = clone $this;
        $clone->wheres = [...$clone->wheres, ['AND', $column, 'IN', $values]];

        return $clone;
    }

    /**
     * Add a WHERE IS NULL condition.
     *
     * @param  string $column
     * @return static
     */
    public function whereNull(string $column): static
    {
        $clone         = clone $this;
        $clone->wheres = [...$clone->wheres, ['AND', $column, 'IS NULL', null]];

        return $clone;
    }

    /**
     * Add a WHERE IS NOT NULL condition.
     *
     * @param  string $column
     * @return static
     */
    public function whereNotNull(string $column): static
    {
        $clone         = clone $this;
        $clone->wheres = [...$clone->wheres, ['AND', $column, 'IS NOT NULL', null]];

        return $clone;
    }

    // -------------------------------------------------------------------------
    // ORDER BY / LIMIT / OFFSET
    // -------------------------------------------------------------------------

    /**
     * Add an ORDER BY clause.
     *
     * @param  string $column
     * @param  string $direction 'ASC' | 'DESC'
     * @return static
     */
    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        $dir   = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $clone = clone $this;
        $clone->orderBys = [...$clone->orderBys, [$column, $dir]];

        return $clone;
    }

    /**
     * Set the maximum number of rows to return.
     *
     * @param  int $limit
     * @return static
     */
    public function limit(int $limit): static
    {
        $clone        = clone $this;
        $clone->limit = $limit;

        return $clone;
    }

    /**
     * Set the row offset.
     *
     * @param  int $offset
     * @return static
     */
    public function offset(int $offset): static
    {
        $clone         = clone $this;
        $clone->offset = $offset;

        return $clone;
    }

    // -------------------------------------------------------------------------
    // JOIN
    // -------------------------------------------------------------------------

    /**
     * Add an INNER JOIN clause.
     *
     * @param  string $table
     * @param  string $first    Left-hand column.
     * @param  string $operator Join operator (=, <, >, etc.).
     * @param  string $second   Right-hand column.
     * @return static
     */
    public function join(string $table, string $first, string $operator, string $second): static
    {
        $clone        = clone $this;
        $clone->joins = [...$clone->joins, ['INNER', $table, $first, $operator, $second]];

        return $clone;
    }

    /**
     * Add a LEFT JOIN clause.
     *
     * @param  string $table
     * @param  string $first
     * @param  string $operator
     * @param  string $second
     * @return static
     */
    public function leftJoin(string $table, string $first, string $operator, string $second): static
    {
        $clone        = clone $this;
        $clone->joins = [...$clone->joins, ['LEFT', $table, $first, $operator, $second]];

        return $clone;
    }

    // -------------------------------------------------------------------------
    // Execution — SELECT
    // -------------------------------------------------------------------------

    /**
     * Execute the query and return all matching rows.
     *
     * @return array<int, object>
     */
    public function get(): array
    {
        $bindings = [];
        $sql      = $this->compileSelect($bindings);
        $stmt     = $this->execute($sql, $bindings);

        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Return the first matching row or null.
     *
     * @return object|null
     */
    public function first(): ?object
    {
        $results = $this->limit(1)->get();

        return $results[0] ?? null;
    }

    /**
     * Find a row by its primary-key value.
     *
     * @param  int|string $id
     * @param  string     $primaryKey Column name (default: 'id').
     * @return object|null
     */
    public function find(int|string $id, string $primaryKey = 'id'): ?object
    {
        return $this->where($primaryKey, '=', $id)->first();
    }

    /**
     * Return the total count of matching rows.
     *
     * @return int
     */
    public function count(): int
    {
        $bindings = [];
        $sql      = $this->compileCount($bindings);
        $stmt     = $this->execute($sql, $bindings);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Paginate the results.
     *
     * @param  int $perPage   Rows per page.
     * @param  int $page      Current page (1-based).
     * @return array{data: array<int, object>, total: int, per_page: int, current_page: int, last_page: int}
     */
    public function paginate(int $perPage = 15, int $page = 1): array
    {
        $page  = max(1, $page);
        $total = $this->count();

        $data = $this
            ->limit($perPage)
            ->offset(($page - 1) * $perPage)
            ->get();

        return [
            'data'         => $data,
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => (int) ceil($total / max(1, $perPage)),
        ];
    }

    // -------------------------------------------------------------------------
    // Execution — WRITE
    // -------------------------------------------------------------------------

    /**
     * Insert a single row and return the last insert ID.
     *
     * @param  array<string, mixed> $data
     * @return int  Last insert ID (0 when the driver doesn't support it).
     */
    public function insert(array $data): int
    {
        if (empty($data)) {
            return 0;
        }

        $columns     = array_keys($data);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $columnList  = implode(', ', array_map($this->quoteIdentifier(...), $columns));

        $sql  = "INSERT INTO {$this->quoteIdentifier($this->table)} ({$columnList}) VALUES ({$placeholders})";

        $this->execute($sql, array_values($data));

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Update rows matching the current WHERE conditions.
     *
     * @param  array<string, mixed> $data
     * @return int  Number of rows affected.
     */
    public function update(array $data): int
    {
        if (empty($data)) {
            return 0;
        }

        $setClauses = implode(
            ', ',
            array_map(
                fn(string $col) => "{$this->quoteIdentifier($col)} = ?",
                array_keys($data),
            )
        );

        $bindings     = array_values($data);
        $whereString  = $this->compileWheres($bindings);

        $sql  = "UPDATE {$this->quoteIdentifier($this->table)} SET {$setClauses}{$whereString}";
        $stmt = $this->execute($sql, $bindings);

        return $stmt->rowCount();
    }

    /**
     * Delete rows matching the current WHERE conditions.
     *
     * @return int  Number of rows affected.
     */
    public function delete(): int
    {
        $bindings    = [];
        $whereString = $this->compileWheres($bindings);

        $sql  = "DELETE FROM {$this->quoteIdentifier($this->table)}{$whereString}";
        $stmt = $this->execute($sql, $bindings);

        return $stmt->rowCount();
    }

    // -------------------------------------------------------------------------
    // Debugging
    // -------------------------------------------------------------------------

    /**
     * Return the compiled SQL string (values shown as ? placeholders).
     *
     * @return string
     */
    public function toSql(): string
    {
        $bindings = [];

        return $this->compileSelect($bindings);
    }

    /**
     * Return both the compiled SQL and the bound values array.
     *
     * @return array{sql: string, bindings: array<int, mixed>}
     */
    public function toSqlWithBindings(): array
    {
        $bindings = [];
        $sql      = $this->compileSelect($bindings);

        return compact('sql', 'bindings');
    }

    // -------------------------------------------------------------------------
    // SQL Compilation (internal)
    // -------------------------------------------------------------------------

    /**
     * Compile a full SELECT statement.
     *
     * @param  array<int, mixed> &$bindings  Populated with values during compilation.
     * @return string
     */
    protected function compileSelect(array &$bindings): string
    {
        $columns = empty($this->selects)
            ? '*'
            : implode(', ', array_map($this->quoteIdentifier(...), $this->selects));

        $sql = "SELECT {$columns} FROM {$this->quoteIdentifier($this->table)}";

        // JOINs
        foreach ($this->joins as [$type, $joinTable, $first, $op, $second]) {
            $sql .= " {$type} JOIN {$this->quoteIdentifier($joinTable)}"
                  . " ON {$this->quoteIdentifier($first)} {$op} {$this->quoteIdentifier($second)}";
        }

        // WHERE
        $sql .= $this->compileWheres($bindings);

        // ORDER BY
        if (!empty($this->orderBys)) {
            $parts = array_map(
                fn(array $ob) => "{$this->quoteIdentifier($ob[0])} {$ob[1]}",
                $this->orderBys,
            );
            $sql .= ' ORDER BY ' . implode(', ', $parts);
        }

        // LIMIT / OFFSET
        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }

        if ($this->offset !== null) {
            $sql .= " OFFSET {$this->offset}";
        }

        return $sql;
    }

    /**
     * Compile a COUNT(*) SELECT statement.
     *
     * @param  array<int, mixed> &$bindings
     * @return string
     */
    protected function compileCount(array &$bindings): string
    {
        $sql = "SELECT COUNT(*) FROM {$this->quoteIdentifier($this->table)}";

        // JOINs
        foreach ($this->joins as [$type, $joinTable, $first, $op, $second]) {
            $sql .= " {$type} JOIN {$this->quoteIdentifier($joinTable)}"
                  . " ON {$this->quoteIdentifier($first)} {$op} {$this->quoteIdentifier($second)}";
        }

        $sql .= $this->compileWheres($bindings);

        return $sql;
    }

    /**
     * Compile WHERE clauses and append bindings.
     *
     * @param  array<int, mixed> &$bindings  Values are appended in-place.
     * @return string  Leading space included when non-empty, '' otherwise.
     */
    protected function compileWheres(array &$bindings): string
    {
        if (empty($this->wheres)) {
            return '';
        }

        $parts = [];

        foreach ($this->wheres as $index => [$boolean, $column, $operator, $value]) {
            $col    = $this->quoteIdentifier($column);
            $prefix = $index === 0 ? '' : " {$boolean}";

            if ($operator === 'IN') {
                /** @var array<mixed> $value */
                $placeholders = implode(', ', array_fill(0, count($value), '?'));
                $parts[]      = "{$prefix} {$col} IN ({$placeholders})";
                array_push($bindings, ...$value);
            } elseif ($operator === 'IS NULL') {
                $parts[] = "{$prefix} {$col} IS NULL";
            } elseif ($operator === 'IS NOT NULL') {
                $parts[] = "{$prefix} {$col} IS NOT NULL";
            } else {
                $parts[]     = "{$prefix} {$col} {$operator} ?";
                $bindings[]  = $value;
            }
        }

        return ' WHERE' . implode('', $parts);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Execute a prepared statement with the given bindings.
     *
     * @param  string            $sql
     * @param  array<int, mixed> $bindings
     * @return PDOStatement
     */
    protected function execute(string $sql, array $bindings): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_values($bindings));

        return $stmt;
    }

    /**
     * Quote a column/table identifier using backticks.
     * Dots (table.column notation) are handled by quoting each segment.
     *
     * @param  string $identifier
     * @return string
     */
    protected function quoteIdentifier(string $identifier): string
    {
        // Leave raw expressions (containing spaces or parentheses) unquoted.
        if (str_contains($identifier, ' ') || str_contains($identifier, '(')) {
            return $identifier;
        }

        return implode(
            '.',
            array_map(
                static fn(string $part) => '`' . str_replace('`', '``', $part) . '`',
                explode('.', $identifier),
            )
        );
    }

    /**
     * Normalize the 2-arg shorthand where('col', 'val') into ['=', 'val'].
     *
     * @param  mixed $operatorOrValue
     * @param  mixed $value
     * @return array{0: string, 1: mixed}
     */
    private function parseOperatorAndValue(mixed $operatorOrValue, mixed $value): array
    {
        $validOperators = ['=', '!=', '<>', '<', '>', '<=', '>=', 'LIKE', 'NOT LIKE', 'ILIKE'];

        if ($value === null && !in_array(strtoupper((string) $operatorOrValue), $validOperators, true)) {
            // Two-arg shorthand: where('col', 'value')
            return ['=', $operatorOrValue];
        }

        return [strtoupper((string) $operatorOrValue), $value];
    }
}
