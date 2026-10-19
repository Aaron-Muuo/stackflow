<?php

declare(strict_types=1);

namespace StackFlow\Database;

use StackFlow\Database\Exceptions\ModelNotFoundException;

/**
 * Model — Active Record base class.
 *
 * Child classes define their schema via static properties and gain
 * full CRUD, casting, validation, mass-assignment protection, timestamps,
 * dirty-tracking, and a simple lifecycle-hook system.
 *
 * Example:
 *
 *   class User extends Model
 *   {
 *       protected static array $fillable = ['name', 'email'];
 *       protected static array $hidden   = ['password'];
 *       protected static array $casts    = ['is_admin' => 'bool'];
 *       protected static array $rules    = ['email' => 'required|email'];
 *   }
 *
 *   $user = User::create(['name' => 'Alice', 'email' => 'alice@example.com']);
 *   $user->name = 'Bob';
 *   $user->save();
 */
abstract class Model
{
    // -------------------------------------------------------------------------
    // Schema Configuration (override in child classes)
    // -------------------------------------------------------------------------

    /**
     * The database table for this model.
     * Auto-derived as snake_case plural of the class name when left empty.
     */
    protected static string $table = '';

    /** Primary-key column name. */
    protected static string $primaryKey = 'id';

    /** Columns that may be mass-assigned via create() / fill(). */
    protected static array $fillable = [];

    /** Columns excluded from toArray() / toJson(). */
    protected static array $hidden = [];

    /**
     * Validation rules keyed by attribute name.
     * Supported tokens: required, email, min:N, max:N, numeric, string
     */
    protected static array $rules = [];

    /**
     * Type casts keyed by attribute name.
     * Supported types: int, float, bool, string, json, datetime
     */
    protected static array $casts = [];

    /** Automatically maintain created_at / updated_at timestamps. */
    protected static bool $timestamps = true;

    /** Named connection from DB::configure() to use for this model. */
    protected static string $connection = 'default';

    // -------------------------------------------------------------------------
    // Instance State
    // -------------------------------------------------------------------------

    /** Current attribute values (after casting on write). */
    protected array $attributes = [];

    /** Snapshot of attributes at load/save time (for dirty detection). */
    protected array $original = [];

    /** Whether this instance represents a persisted database row. */
    protected bool $exists = false;

    /** Validation error bag [ field => [message, …] ]. */
    protected array $errors = [];

    // -------------------------------------------------------------------------
    // Constructor
    // -------------------------------------------------------------------------

    /**
     * @param  array<string, mixed> $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    // -------------------------------------------------------------------------
    // Magic Accessors
    // -------------------------------------------------------------------------

    /**
     * Read an attribute, applying casts.
     *
     * @param  string $key
     * @return mixed
     */
    public function __get(string $key): mixed
    {
        return $this->castGet($key, $this->attributes[$key] ?? null);
    }

    /**
     * Write an attribute, applying casts.
     *
     * @param  string $key
     * @param  mixed  $value
     * @return void
     */
    public function __set(string $key, mixed $value): void
    {
        $this->attributes[$key] = $this->castSet($key, $value);
    }

    /**
     * @param  string $key
     * @return bool
     */
    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    /**
     * @param  string $key
     * @return void
     */
    public function __unset(string $key): void
    {
        unset($this->attributes[$key]);
    }

    // -------------------------------------------------------------------------
    // Table Resolution
    // -------------------------------------------------------------------------

    /**
     * Resolve the table name for the calling subclass.
     *
     * @return string
     */
    public static function getTable(): string
    {
        if (static::$table !== '') {
            return static::$table;
        }

        // Derive: "App\Models\UserProfile" → "user_profiles"
        $shortName = (new \ReflectionClass(static::class))->getShortName();

        return static::toSnakeCase($shortName) . 's';
    }

    // -------------------------------------------------------------------------
    // Query Entry Points
    // -------------------------------------------------------------------------

    /**
     * Return a QueryBuilder scoped to this model's table and connection.
     *
     * @return QueryBuilder
     */
    public static function query(): QueryBuilder
    {
        return DB::table(static::getTable(), static::$connection);
    }

    /**
     * Find a record by primary key.
     *
     * @param  int|string $id
     * @return static|null
     */
    public static function find(int|string $id): ?static
    {
        $row = static::query()->find($id, static::$primaryKey);

        return $row ? static::hydrate((array) $row) : null;
    }

    /**
     * Find a record by primary key or throw ModelNotFoundException.
     *
     * @param  int|string $id
     * @return static
     *
     * @throws ModelNotFoundException
     */
    public static function findOrFail(int|string $id): static
    {
        return static::find($id)
            ?? throw ModelNotFoundException::forModel(static::class, $id);
    }

    /**
     * Return all records.
     *
     * @return array<int, static>
     */
    public static function all(): array
    {
        return array_map(
            static fn(object $row) => static::hydrate((array) $row),
            static::query()->get(),
        );
    }

    /**
     * Begin a WHERE clause on the model's query builder.
     *
     * Supports both 2-arg shorthand and explicit operator forms.
     *
     * @param  string $column
     * @param  mixed  $operatorOrValue
     * @param  mixed  $value
     * @return QueryBuilder
     */
    public static function where(string $column, mixed $operatorOrValue, mixed $value = null): QueryBuilder
    {
        return static::query()->where($column, $operatorOrValue, $value);
    }

    // -------------------------------------------------------------------------
    // Mass Assignment & Creation
    // -------------------------------------------------------------------------

    /**
     * Fill the model with an array of attributes, respecting $fillable.
     *
     * @param  array<string, mixed> $attributes
     * @return static
     */
    public function fill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) {
                $this->__set($key, $value);
            }
        }

        return $this;
    }

    /**
     * Create and persist a new model instance.
     *
     * @param  array<string, mixed> $attributes
     * @return static
     */
    public static function create(array $attributes): static
    {
        $model = new static($attributes);
        $model->save();

        return $model;
    }

    // -------------------------------------------------------------------------
    // Persistence
    // -------------------------------------------------------------------------

    /**
     * Persist the model (INSERT when new, UPDATE when existing).
     *
     * @return bool
     */
    public function save(): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        $this->beforeSave();

        $now = date('Y-m-d H:i:s');

        if (static::$timestamps) {
            if (!$this->exists) {
                $this->attributes['created_at'] = $now;
            }
            $this->attributes['updated_at'] = $now;
        }

        if ($this->exists) {
            $dirty = $this->getDirtyAttributes();

            if (!empty($dirty)) {
                static::query()
                    ->where(static::$primaryKey, '=', $this->getKey())
                    ->update($dirty);
            }
        } else {
            $id = static::query()->insert($this->attributes);

            if (is_numeric($id) && $id > 0) {
                $this->attributes[static::$primaryKey] = $id;
            }

            $this->exists = true;
        }

        $this->syncOriginal();
        $this->afterSave();

        return true;
    }

    /**
     * Update specific attributes and persist.
     *
     * @param  array<string, mixed> $attributes
     * @return bool
     */
    public function update(array $attributes): bool
    {
        $this->fill($attributes);

        return $this->save();
    }

    /**
     * Delete this model from the database.
     *
     * @return bool
     */
    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }

        $this->beforeDelete();

        static::query()
            ->where(static::$primaryKey, '=', $this->getKey())
            ->delete();

        $this->exists = false;

        $this->afterDelete();

        return true;
    }

    // -------------------------------------------------------------------------
    // Serialization
    // -------------------------------------------------------------------------

    /**
     * Convert the model to a plain array, excluding $hidden keys.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [];

        foreach ($this->attributes as $key => $value) {
            if (!in_array($key, static::$hidden, true)) {
                $result[$key] = $this->castGet($key, $value);
            }
        }

        return $result;
    }

    /**
     * Serialize the model to JSON.
     *
     * @return string
     */
    public function toJson(): string
    {
        return (string) json_encode($this->toArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    // -------------------------------------------------------------------------
    // Dirty Tracking
    // -------------------------------------------------------------------------

    /**
     * Determine if any (or a specific) attribute has changed since the last sync.
     *
     * @param  string|null $key  Null checks any attribute.
     * @return bool
     */
    public function isDirty(?string $key = null): bool
    {
        if ($key !== null) {
            return ($this->attributes[$key] ?? null) !== ($this->original[$key] ?? null);
        }

        return !empty($this->getDirtyAttributes());
    }

    /**
     * Return only the attributes that differ from $original.
     *
     * @return array<string, mixed>
     */
    protected function getDirtyAttributes(): array
    {
        $dirty = [];

        foreach ($this->attributes as $key => $value) {
            if (!array_key_exists($key, $this->original) || $this->original[$key] !== $value) {
                $dirty[$key] = $value;
            }
        }

        return $dirty;
    }

    /**
     * Snapshot the current attributes as the "original" baseline.
     *
     * @return void
     */
    protected function syncOriginal(): void
    {
        $this->original = $this->attributes;
    }

    // -------------------------------------------------------------------------
    // Validation
    // -------------------------------------------------------------------------

    /**
     * Run the model's $rules against current attributes.
     *
     * @return array<string, array<int, string>>  [ field => [message, …] ]
     */
    protected function validate(): array
    {
        $errors = [];

        foreach (static::$rules as $field => $ruleString) {
            $rules = explode('|', $ruleString);
            $value = $this->attributes[$field] ?? null;

            foreach ($rules as $rule) {
                [$ruleName, $ruleParam] = array_pad(explode(':', $rule, 2), 2, null);

                $error = match (strtolower($ruleName)) {
                    'required' => ($value === null || $value === '')
                        ? "{$field} is required."
                        : null,
                    'email' => ($value !== null && !filter_var($value, FILTER_VALIDATE_EMAIL))
                        ? "{$field} must be a valid email address."
                        : null,
                    'numeric' => ($value !== null && !is_numeric($value))
                        ? "{$field} must be numeric."
                        : null,
                    'string' => ($value !== null && !is_string($value))
                        ? "{$field} must be a string."
                        : null,
                    'min' => ($value !== null && strlen((string) $value) < (int) $ruleParam)
                        ? "{$field} must be at least {$ruleParam} characters."
                        : null,
                    'max' => ($value !== null && strlen((string) $value) > (int) $ruleParam)
                        ? "{$field} must not exceed {$ruleParam} characters."
                        : null,
                    default => null,
                };

                if ($error !== null) {
                    $errors[$field][] = $error;
                }
            }
        }

        return $errors;
    }

    /**
     * Run validation and cache errors; return true when the model is valid.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        $this->errors = $this->validate();

        return empty($this->errors);
    }

    /**
     * Return the validation error bag.
     *
     * @return array<string, array<int, string>>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    // -------------------------------------------------------------------------
    // Lifecycle Hooks (override in child classes)
    // -------------------------------------------------------------------------

    /** Called before every save() attempt. */
    protected function beforeSave(): void {}

    /** Called after a successful save(). */
    protected function afterSave(): void {}

    /** Called before delete(). */
    protected function beforeDelete(): void {}

    /** Called after a successful delete(). */
    protected function afterDelete(): void {}

    // -------------------------------------------------------------------------
    // Internal Helpers
    // -------------------------------------------------------------------------

    /**
     * Return the primary-key value for this instance.
     *
     * @return int|string|null
     */
    public function getKey(): int|string|null
    {
        return $this->attributes[static::$primaryKey] ?? null;
    }

    /**
     * Determine whether a column is mass-assignable.
     *
     * @param  string $key
     * @return bool
     */
    protected function isFillable(string $key): bool
    {
        return empty(static::$fillable) || in_array($key, static::$fillable, true);
    }

    /**
     * Hydrate a new model instance from a raw database row array.
     * Marks the instance as existing and syncs the original snapshot.
     *
     * @param  array<string, mixed> $row
     * @return static
     */
    protected static function hydrate(array $row): static
    {
        $instance             = new static();
        $instance->attributes = $row;
        $instance->original   = $row;
        $instance->exists     = true;

        return $instance;
    }

    /**
     * Apply a cast when reading an attribute value.
     *
     * @param  string $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function castGet(string $key, mixed $value): mixed
    {
        $cast = static::$casts[$key] ?? null;

        if ($value === null || $cast === null) {
            return $value;
        }

        return match (strtolower($cast)) {
            'int', 'integer'  => (int) $value,
            'float', 'double' => (float) $value,
            'bool', 'boolean' => (bool) $value,
            'string'          => (string) $value,
            'json', 'array'   => is_string($value) ? json_decode($value, true) : $value,
            'datetime'        => new \DateTimeImmutable((string) $value),
            default           => $value,
        };
    }

    /**
     * Apply a cast when writing an attribute value.
     *
     * @param  string $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function castSet(string $key, mixed $value): mixed
    {
        $cast = static::$casts[$key] ?? null;

        if ($value === null || $cast === null) {
            return $value;
        }

        return match (strtolower($cast)) {
            'int', 'integer'  => (int) $value,
            'float', 'double' => (float) $value,
            'bool', 'boolean' => (bool) $value,
            'string'          => (string) $value,
            'json', 'array'   => is_array($value) ? json_encode($value, JSON_THROW_ON_ERROR) : $value,
            'datetime'        => ($value instanceof \DateTimeInterface)
                ? $value->format('Y-m-d H:i:s')
                : (string) $value,
            default => $value,
        };
    }

    /**
     * Convert a PascalCase class name to snake_case.
     *
     * @param  string $name
     * @return string
     */
    protected static function toSnakeCase(string $name): string
    {
        $snake = preg_replace('/([A-Z])/', '_$1', $name) ?? $name;

        return strtolower(ltrim($snake, '_'));
    }
}
