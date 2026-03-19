<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Base Repository Interface
 *
 * Defines the contract for all repository implementations providing
 * standardized CRUD operations, query building, and data access patterns.
 *
 * This interface ensures consistency across all repositories while
 * maintaining flexibility for entity-specific operations.
 *
 * @template TModel of \Illuminate\Database\Eloquent\Model
 */
interface RepositoryInterface
{
    /**
     * Find a model by its primary key.
     *
     * @param  mixed  $id  The primary key value
     * @param  array<string>  $columns  Columns to select
     * @return TModel|null
     */
    public function find(mixed $id, array $columns = ['*']): ?Model;

    /**
     * Find a model by its primary key or throw an exception.
     *
     * @param  mixed  $id  The primary key value
     * @param  array<string>  $columns  Columns to select
     * @return TModel
     *
     * @throws ModelNotFoundException
     */
    public function findOrFail(mixed $id, array $columns = ['*']): Model;

    /**
     * Find models by a specific field value.
     *
     * @param  string  $field  The field name
     * @param  mixed  $value  The field value
     * @param  array<string>  $columns  Columns to select
     * @return Collection<int, TModel>
     */
    public function findBy(string $field, mixed $value, array $columns = ['*']): Collection;

    /**
     * Find models matching multiple criteria.
     *
     * @param  array<string, mixed>  $criteria  Field-value pairs
     * @param  array<string>  $columns  Columns to select
     * @return Collection<int, TModel>
     */
    public function findWhere(array $criteria, array $columns = ['*']): Collection;

    /**
     * Get all models.
     *
     * @param  array<string>  $columns  Columns to select
     * @return Collection<int, TModel>
     */
    public function all(array $columns = ['*']): Collection;

    /**
     * Paginate models.
     *
     * @param  int  $perPage  Number of items per page
     * @param  array<string>  $columns  Columns to select
     * @param  string  $pageName  Page parameter name
     * @param  int|null  $page  Current page number
     */
    public function paginate(int $perPage = 15, array $columns = ['*'], string $pageName = 'page', ?int $page = null): LengthAwarePaginator;

    /**
     * Create a new model.
     *
     * @param  array<string, mixed>  $data  Model attributes
     * @return TModel
     */
    public function create(array $data): Model;

    /**
     * Update a model by its primary key.
     *
     * @param  mixed  $id  The primary key value
     * @param  array<string, mixed>  $data  Updated attributes
     * @return TModel
     *
     * @throws ModelNotFoundException
     */
    public function update(mixed $id, array $data): Model;

    /**
     * Delete a model by its primary key.
     *
     * @param  mixed  $id  The primary key value
     *
     * @throws ModelNotFoundException
     */
    public function delete(mixed $id): bool;

    /**
     * Eager load relationships.
     *
     * @param  array<string>|string  $relations  Relationship names
     */
    public function with(array|string $relations): static;

    /**
     * Order results by a column.
     *
     * @param  string  $column  Column name
     * @param  string  $direction  Sort direction (asc|desc)
     */
    public function orderBy(string $column, string $direction = 'asc'): static;

    /**
     * Process models in chunks.
     *
     * @param  int  $count  Chunk size
     * @param  callable  $callback  Processing callback
     */
    public function chunk(int $count, callable $callback): bool;

    /**
     * Count total models.
     */
    public function count(): int;

    /**
     * Check if any models exist.
     */
    public function exists(): bool;

    /**
     * Get the first model or create if not exists.
     *
     * @param  array<string, mixed>  $attributes  Search attributes
     * @param  array<string, mixed>  $values  Additional values for creation
     * @return TModel
     */
    public function firstOrCreate(array $attributes, array $values = []): Model;

    /**
     * Update or create a model.
     *
     * @param  array<string, mixed>  $attributes  Search attributes
     * @param  array<string, mixed>  $values  Update/create values
     * @return TModel
     */
    public function updateOrCreate(array $attributes, array $values = []): Model;

    /**
     * Apply a where clause.
     *
     * @param  string  $column  Column name
     * @param  mixed  $operator  Operator or value
     * @param  mixed  $value  Value (if operator provided)
     */
    public function where(string $column, mixed $operator = null, mixed $value = null): static;

    /**
     * Apply a whereIn clause.
     *
     * @param  string  $column  Column name
     * @param  array<mixed>  $values  Values array
     */
    public function whereIn(string $column, array $values): static;

    /**
     * Apply a whereNotIn clause.
     *
     * @param  string  $column  Column name
     * @param  array<mixed>  $values  Values array
     */
    public function whereNotIn(string $column, array $values): static;

    /**
     * Apply a whereBetween clause.
     *
     * @param  string  $column  Column name
     * @param  array<mixed>  $values  Range values [min, max]
     */
    public function whereBetween(string $column, array $values): static;

    /**
     * Apply a whereNull clause.
     *
     * @param  string  $column  Column name
     */
    public function whereNull(string $column): static;

    /**
     * Apply a whereNotNull clause.
     *
     * @param  string  $column  Column name
     */
    public function whereNotNull(string $column): static;

    /**
     * Apply a limit clause.
     *
     * @param  int  $limit  Maximum number of results
     */
    public function limit(int $limit): static;

    /**
     * Apply an offset clause.
     *
     * @param  int  $offset  Number of results to skip
     */
    public function offset(int $offset): static;

    /**
     * Get fresh query instance.
     */
    public function fresh(): static;

    /**
     * Execute the query and get results.
     *
     * @param  array<string>  $columns  Columns to select
     * @return Collection<int, TModel>
     */
    public function get(array $columns = ['*']): Collection;

    /**
     * Get the first result.
     *
     * @param  array<string>  $columns  Columns to select
     * @return TModel|null
     */
    public function first(array $columns = ['*']): ?Model;

    /**
     * Get the underlying model instance.
     *
     * @return TModel
     */
    public function getModel(): Model;

    /**
     * Set the underlying model instance.
     *
     * @param  TModel  $model
     */
    public function setModel(Model $model): static;
}
