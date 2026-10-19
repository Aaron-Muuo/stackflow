<?php

declare(strict_types=1);

namespace StackFlow\Database\Exceptions;

/**
 * Thrown when a Model record cannot be located via findOrFail().
 */
class ModelNotFoundException extends \RuntimeException
{
    /**
     * The name of the model that was not found.
     */
    private string $model = '';

    /**
     * The primary-key value that was searched for.
     */
    private int|string $id = 0;

    // -------------------------------------------------------------------------
    // Factory
    // -------------------------------------------------------------------------

    /**
     * Create a pre-formatted instance with model and id context.
     *
     * @param  string     $model Class name of the model.
     * @param  int|string $id    Primary-key value that was searched.
     * @return static
     */
    public static function forModel(string $model, int|string $id): static
    {
        $instance        = new static("No record found for model [{$model}] with id [{$id}].");
        $instance->model = $model;
        $instance->id    = $id;

        return $instance;
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    /**
     * Return the model class name that triggered the exception.
     */
    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * Return the primary-key value that was not found.
     */
    public function getId(): int|string
    {
        return $this->id;
    }
}
