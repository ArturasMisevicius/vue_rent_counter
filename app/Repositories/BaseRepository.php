<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\RepositoryInterface;
use App\Exceptions\RepositoryException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Base Repository Implementation
 * 
 * Provides common repository functionality with:
 * - Complete CRUD operations
 * - Query builder pattern support
 * - Transaction handling
 * - Error handling and logging
 * - Tenant scope awareness
 * - Soft delete handling
 * - Performance optimizations
 * 
 * @template TModel of \Illuminate\Database\Eloquent\Model
 * @implements RepositoryInterface<TModel>
 */
abstract class BaseRepository implements RepositoryInterface
{
    /**
     * The Eloquent model instance.
     * 
     * @var TModel
     */
    protected Model $model;

    /**
     * The current query builder instance.
     * 
     * @var Builder<TModel>
     */
    protected Builder $query;

    /**
     * Relationships to eager load.
     * 
     * @var array<string>
     */
    protected array $with = [];

    /**
     * Create a new repository instance.
     * 
     * @param TModel $model
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
        $this->resetQuery();
    }

    /**
     * {@inheritDoc}
     */
    public function find(mixed $id, array $columns = ['*']): ?Model
    {
        try {
            return $this->query->select($columns)->find($id);
        } catch (Throwable $e) {
            $this->handleException($e, ['method' => 'find', 'id' => $id]);
            return null;
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findOrFail(mixed $id, array $columns = ['*']): Model
    {
        try {
            $result = $this->query->select($columns)->findOrFail($id);
            $this->resetQuery();
            return $result;
        } catch (ModelNotFoundException $e) {
            $this->resetQuery();
            throw $e;
        } catch (Throwable $e) {
            $this->resetQuery();
            $this->handleException($e, ['method' => 'findOrFail', 'id' => $id]);
            throw new RepositoryException("Failed to find model with ID: {$id}", 0, $e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findBy(string $field, mixed $value, array $columns = ['*']): Collection
    {
        try {
            $result = $this->query->select($columns)->where($field, $value)->get();
            $this->resetQuery();
            return $result;
        } catch (Throwable $e) {
            $this->resetQuery();
            $this->handleException($e, ['method' => 'findBy', 'field' => $field, 'value' => $value]);
            return new Collection();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findWhere(array $criteria, array $columns = ['*']): Collection
    {
        try {
            $query = $this->query->select($columns);
            
            foreach ($criteria as $field => $value) {
                if (is_array($value)) {
                    $query->whereIn($field, $value);
                } else {
                    $query->where($field, $value);
                }
            }
            
            $result = $query->get();
            $this->resetQuery();
            return $result;
        } catch (Throwable $e) {
            $this->resetQuery();
            $this->handleException($e, ['method' => 'findWhere', 'criteria' => $criteria]);
            return new Collection();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function all(array $columns = ['*']): Collection
    {
        try {
            $result = $this->query->select($columns)->get();
            $this->resetQuery();
            return $result;
        } catch (Throwable $e) {
            $this->resetQuery();
            $this->handleException($e, ['method' => 'all']);
            return new Collection();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function paginate(int $perPage = 15, array $columns = ['*'], string $pageName = 'page', ?int $page = null): LengthAwarePaginator
    {
        try {
            $result = $this->query->select($columns)->paginate($perPage, $columns, $pageName, $page);
            $this->resetQuery();
            return $result;
        } catch (Throwable $e) {
            $this->resetQuery();
            $this->handleException($e, ['method' => 'paginate', 'perPage' => $perPage]);
            throw new RepositoryException('Failed to paginate results', 0, $e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $data): Model
    {
        try {
            return DB::transaction(function () use ($data) {
                $model = $this->model->newInstance($data);
                $model->save();
                
                $this->logOperation('create', ['id' => $model->getKey()]);
                return $model;
            });
        } catch (Throwable $e) {
            $this->handleException($e, ['method' => 'create', 'data' => $data]);
            throw new RepositoryException('Failed to create model', 0, $e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function update(mixed $id, array $data): Model
    {
        try {
            return DB::transaction(function () use ($id, $data) {
                $model = $this->findOrFail($id);
                $model->fill($data);
                $model->save();
                
                $this->logOperation('update', ['id' => $id, 'changes' => $model->getChanges()]);
                return $model;
            });
        } catch (ModelNotFoundException $e) {
            throw $e;
        } catch (Throwable $e) {
            $this->handleException($e, ['method' => 'update', 'id' => $id, 'data' => $data]);
            throw new RepositoryException("Failed to update model with ID: {$id}", 0, $e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function delete(mixed $id): bool
    {
        try {
            return DB::transaction(function () use ($id) {
                $model = $this->findOrFail($id);
                $result = $model->delete();
                
                $this->logOperation('delete', ['id' => $id]);
                return $result;
            });
        } catch (ModelNotFoundException $e) {
            throw $e;
        } catch (Throwable $e) {
            $this->handleException($e, ['method' => 'delete', 'id' => $id]);
            throw new RepositoryException("Failed to delete model with ID: {$id}", 0, $e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function with(array|string $relations): static
    {
        $this->with = array_merge($this->with, is_array($relations) ? $relations : [$relations]);
        $this->query->with($this->with);
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function orderBy(string $column, string $direction = 'asc'): static
    {
        $this->query->orderBy($column, $direction);
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function chunk(int $count, callable $callback): bool
    {
        try {
            $result = $this->query->chunk($count, $callback);
            $this->resetQuery();
            return $result;
        } catch (Throwable $e) {
            $this->resetQuery();
            $this->handleException($e, ['method' => 'chunk', 'count' => $count]);
            return false;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function count(): int
    {
        try {
            $result = $this->query->count();
            $this->resetQuery();
            return $result;
        } catch (Throwable $e) {
            $this->resetQuery();
            $this->handleException($e, ['method' => 'count']);
            return 0;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function exists(): bool
    {
        try {
            $result = $this->query->exists();
            $this->resetQuery();
            return $result;
        } catch (Throwable $e) {
            $this->resetQuery();
            $this->handleException($e, ['method' => 'exists']);
            return false;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function firstOrCreate(array $attributes, array $values = []): Model
    {
        try {
            return DB::transaction(function () use ($attributes, $values) {
                $model = $this->model->firstOrCreate($attributes, $values);
                
                if ($model->wasRecentlyCreated) {
                    $this->logOperation('firstOrCreate', ['id' => $model->getKey(), 'created' => true]);
                }
                
                return $model;
            });
        } catch (Throwable $e) {
            $this->handleException($e, ['method' => 'firstOrCreate', 'attributes' => $attributes]);
            throw new RepositoryException('Failed to find or create model', 0, $e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function updateOrCreate(array $attributes, array $values = []): Model
    {
        try {
            return DB::transaction(function () use ($attributes, $values) {
                $model = $this->model->updateOrCreate($attributes, $values);
                
                $action = $model->wasRecentlyCreated ? 'created' : 'updated';
                $this->logOperation('updateOrCreate', ['id' => $model->getKey(), 'action' => $action]);
                
                return $model;
            });
        } catch (Throwable $e) {
            $this->handleException($e, ['method' => 'updateOrCreate', 'attributes' => $attributes]);
            throw new RepositoryException('Failed to update or create model', 0, $e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function where(string $column, mixed $operator = null, mixed $value = null): static
    {
        if (func_num_args() === 2) {
            $this->query->where($column, $operator);
        } else {
            $this->query->where($column, $operator, $value);
        }
        
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function whereIn(string $column, array $values): static
    {
        $this->query->whereIn($column, $values);
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function whereNotIn(string $column, array $values): static
    {
        $this->query->whereNotIn($column, $values);
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function whereBetween(string $column, array $values): static
    {
        $this->query->whereBetween($column, $values);
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function whereNull(string $column): static
    {
        $this->query->whereNull($column);
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function whereNotNull(string $column): static
    {
        $this->query->whereNotNull($column);
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function limit(int $limit): static
    {
        $this->query->limit($limit);
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function offset(int $offset): static
    {
        $this->query->offset($offset);
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function fresh(): static
    {
        $this->resetQuery();
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function get(array $columns = ['*']): Collection
    {
        try {
            $result = $this->query->select($columns)->get();
            $this->resetQuery();
            return $result;
        } catch (Throwable $e) {
            $this->resetQuery();
            $this->handleException($e, ['method' => 'get']);
            return new Collection();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function first(array $columns = ['*']): ?Model
    {
        try {
            $result = $this->query->select($columns)->first();
            $this->resetQuery();
            return $result;
        } catch (Throwable $e) {
            $this->resetQuery();
            $this->handleException($e, ['method' => 'first']);
            return null;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getModel(): Model
    {
        return $this->model;
    }

    /**
     * {@inheritDoc}
     */
    public function setModel(Model $model): static
    {
        $this->model = $model;
        $this->resetQuery();
        return $this;
    }

    /**
     * Bulk create multiple models.
     * 
     * @param array<array<string, mixed>> $data Array of model data
     * @return Collection<int, TModel>
     */
    public function bulkCreate(array $data): Collection
    {
        try {
            return DB::transaction(function () use ($data) {
                $models = new Collection();
                
                foreach ($data as $attributes) {
                    $model = $this->model->newInstance($attributes);
                    $model->save();
                    $models->push($model);
                }
                
                $this->logOperation('bulkCreate', ['count' => count($data)]);
                return $models;
            });
        } catch (Throwable $e) {
            $this->handleException($e, ['method' => 'bulkCreate', 'count' => count($data)]);
            throw new RepositoryException('Failed to bulk create models', 0, $e);
        }
    }

    /**
     * Bulk update multiple models.
     * 
     * @param array<mixed> $ids Array of model IDs
     * @param array<string, mixed> $data Update data
     * @return int Number of updated models
     */
    public function bulkUpdate(array $ids, array $data): int
    {
        try {
            return DB::transaction(function () use ($ids, $data) {
                $count = $this->model->whereIn($this->model->getKeyName(), $ids)->update($data);
                
                $this->logOperation('bulkUpdate', ['ids' => $ids, 'count' => $count]);
                return $count;
            });
        } catch (Throwable $e) {
            $this->handleException($e, ['method' => 'bulkUpdate', 'ids' => $ids]);
            throw new RepositoryException('Failed to bulk update models', 0, $e);
        }
    }

    /**
     * Bulk delete multiple models.
     * 
     * @param array<mixed> $ids Array of model IDs
     * @return int Number of deleted models
     */
    public function bulkDelete(array $ids): int
    {
        try {
            return DB::transaction(function () use ($ids) {
                $count = $this->model->whereIn($this->model->getKeyName(), $ids)->delete();
                
                $this->logOperation('bulkDelete', ['ids' => $ids, 'count' => $count]);
                return $count;
            });
        } catch (Throwable $e) {
            $this->handleException($e, ['method' => 'bulkDelete', 'ids' => $ids]);
            throw new RepositoryException('Failed to bulk delete models', 0, $e);
        }
    }

    /**
     * Execute a callback within a database transaction.
     * 
     * @param callable $callback
     * @return mixed
     */
    public function transaction(callable $callback): mixed
    {
        try {
            return DB::transaction($callback);
        } catch (Throwable $e) {
            $this->handleException($e, ['method' => 'transaction']);
            throw $e;
        }
    }

    /**
     * Reset the query builder to a fresh state.
     * 
     * @return void
     */
    protected function resetQuery(): void
    {
        $this->query = $this->model->newQuery();
        $this->with = [];
    }

    /**
     * Handle exceptions with logging.
     * 
     * @param Throwable $e
     * @param array<string, mixed> $context
     * @return void
     */
    protected function handleException(Throwable $e, array $context = []): void
    {
        Log::error('Repository operation failed', array_merge([
            'repository' => static::class,
            'model' => get_class($this->model),
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ], $context));
    }

    /**
     * Log repository operations.
     * 
     * @param string $operation
     * @param array<string, mixed> $context
     * @return void
     */
    protected function logOperation(string $operation, array $context = []): void
    {
        Log::info('Repository operation completed', array_merge([
            'repository' => static::class,
            'model' => get_class($this->model),
            'operation' => $operation,
        ], $context));
    }
}