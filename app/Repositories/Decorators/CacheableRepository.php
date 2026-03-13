<?php

declare(strict_types=1);

namespace App\Repositories\Decorators;

use App\Contracts\RepositoryInterface;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Cacheable Repository Decorator
 * 
 * Adds caching functionality to any repository implementation using
 * the Decorator pattern. This allows for transparent caching without
 * modifying the original repository code.
 * 
 * Features:
 * - Automatic cache key generation
 * - TTL configuration per operation
 * - Cache invalidation on write operations
 * - Tag-based cache management
 * - Performance logging
 * 
 * @template TModel of \Illuminate\Database\Eloquent\Model
 * @implements RepositoryInterface<TModel>
 */
class CacheableRepository implements RepositoryInterface
{
    /**
     * Default cache TTL in seconds (1 hour).
     */
    private const DEFAULT_TTL = 3600;

    /**
     * Cache key prefix for this repository.
     */
    private readonly string $cachePrefix;

    /**
     * Cache tags for this repository.
     * 
     * @var array<string>
     */
    private readonly array $cacheTags;

    /**
     * Create a new cacheable repository decorator.
     * 
     * @param RepositoryInterface<TModel> $repository The repository to decorate
     * @param CacheRepository $cache The cache implementation
     * @param int $ttl Cache TTL in seconds
     */
    public function __construct(
        private readonly RepositoryInterface $repository,
        private readonly CacheRepository $cache,
        private readonly int $ttl = self::DEFAULT_TTL
    ) {
        $modelClass = get_class($this->repository->getModel());
        $this->cachePrefix = 'repo:' . strtolower(class_basename($modelClass));
        $this->cacheTags = [$this->cachePrefix, 'repositories'];
    }

    /**
     * {@inheritDoc}
     */
    public function find(mixed $id, array $columns = ['*']): ?Model
    {
        $cacheKey = $this->generateCacheKey('find', [$id, $columns]);
        
        return $this->cache->tags($this->cacheTags)->remember(
            $cacheKey,
            $this->ttl,
            fn() => $this->repository->find($id, $columns)
        );
    }

    /**
     * {@inheritDoc}
     */
    public function findOrFail(mixed $id, array $columns = ['*']): Model
    {
        $cacheKey = $this->generateCacheKey('findOrFail', [$id, $columns]);
        
        return $this->cache->tags($this->cacheTags)->remember(
            $cacheKey,
            $this->ttl,
            fn() => $this->repository->findOrFail($id, $columns)
        );
    }

    /**
     * {@inheritDoc}
     */
    public function findBy(string $field, mixed $value, array $columns = ['*']): Collection
    {
        $cacheKey = $this->generateCacheKey('findBy', [$field, $value, $columns]);
        
        return $this->cache->tags($this->cacheTags)->remember(
            $cacheKey,
            $this->ttl,
            fn() => $this->repository->findBy($field, $value, $columns)
        );
    }

    /**
     * {@inheritDoc}
     */
    public function findWhere(array $criteria, array $columns = ['*']): Collection
    {
        $cacheKey = $this->generateCacheKey('findWhere', [$criteria, $columns]);
        
        return $this->cache->tags($this->cacheTags)->remember(
            $cacheKey,
            $this->ttl,
            fn() => $this->repository->findWhere($criteria, $columns)
        );
    }

    /**
     * {@inheritDoc}
     */
    public function all(array $columns = ['*']): Collection
    {
        $cacheKey = $this->generateCacheKey('all', [$columns]);
        
        return $this->cache->tags($this->cacheTags)->remember(
            $cacheKey,
            $this->ttl,
            fn() => $this->repository->all($columns)
        );
    }

    /**
     * {@inheritDoc}
     */
    public function paginate(int $perPage = 15, array $columns = ['*'], string $pageName = 'page', ?int $page = null): LengthAwarePaginator
    {
        // Don't cache paginated results as they change frequently
        return $this->repository->paginate($perPage, $columns, $pageName, $page);
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $data): Model
    {
        $model = $this->repository->create($data);
        
        // Invalidate cache after write operation
        $this->invalidateCache();
        
        $this->logCacheOperation('create', ['id' => $model->getKey()]);
        
        return $model;
    }

    /**
     * {@inheritDoc}
     */
    public function update(mixed $id, array $data): Model
    {
        $model = $this->repository->update($id, $data);
        
        // Invalidate cache after write operation
        $this->invalidateCache();
        
        $this->logCacheOperation('update', ['id' => $id]);
        
        return $model;
    }

    /**
     * {@inheritDoc}
     */
    public function delete(mixed $id): bool
    {
        $result = $this->repository->delete($id);
        
        // Invalidate cache after write operation
        $this->invalidateCache();
        
        $this->logCacheOperation('delete', ['id' => $id]);
        
        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function with(array|string $relations): static
    {
        // Return a new instance with the same configuration
        $newRepository = $this->repository->with($relations);
        
        return new static($newRepository, $this->cache, $this->ttl);
    }

    /**
     * {@inheritDoc}
     */
    public function orderBy(string $column, string $direction = 'asc'): static
    {
        $newRepository = $this->repository->orderBy($column, $direction);
        
        return new static($newRepository, $this->cache, $this->ttl);
    }

    /**
     * {@inheritDoc}
     */
    public function chunk(int $count, callable $callback): bool
    {
        // Don't cache chunk operations
        return $this->repository->chunk($count, $callback);
    }

    /**
     * {@inheritDoc}
     */
    public function count(): int
    {
        $cacheKey = $this->generateCacheKey('count');
        
        return $this->cache->tags($this->cacheTags)->remember(
            $cacheKey,
            $this->ttl,
            fn() => $this->repository->count()
        );
    }

    /**
     * {@inheritDoc}
     */
    public function exists(): bool
    {
        $cacheKey = $this->generateCacheKey('exists');
        
        return $this->cache->tags($this->cacheTags)->remember(
            $cacheKey,
            $this->ttl,
            fn() => $this->repository->exists()
        );
    }

    /**
     * {@inheritDoc}
     */
    public function firstOrCreate(array $attributes, array $values = []): Model
    {
        $model = $this->repository->firstOrCreate($attributes, $values);
        
        if ($model->wasRecentlyCreated) {
            $this->invalidateCache();
            $this->logCacheOperation('firstOrCreate', ['id' => $model->getKey(), 'created' => true]);
        }
        
        return $model;
    }

    /**
     * {@inheritDoc}
     */
    public function updateOrCreate(array $attributes, array $values = []): Model
    {
        $model = $this->repository->updateOrCreate($attributes, $values);
        
        // Always invalidate cache for updateOrCreate
        $this->invalidateCache();
        
        $action = $model->wasRecentlyCreated ? 'created' : 'updated';
        $this->logCacheOperation('updateOrCreate', ['id' => $model->getKey(), 'action' => $action]);
        
        return $model;
    }

    /**
     * {@inheritDoc}
     */
    public function where(string $column, mixed $operator = null, mixed $value = null): static
    {
        $newRepository = $this->repository->where($column, $operator, $value);
        
        return new static($newRepository, $this->cache, $this->ttl);
    }

    /**
     * {@inheritDoc}
     */
    public function whereIn(string $column, array $values): static
    {
        $newRepository = $this->repository->whereIn($column, $values);
        
        return new static($newRepository, $this->cache, $this->ttl);
    }

    /**
     * {@inheritDoc}
     */
    public function whereNotIn(string $column, array $values): static
    {
        $newRepository = $this->repository->whereNotIn($column, $values);
        
        return new static($newRepository, $this->cache, $this->ttl);
    }

    /**
     * {@inheritDoc}
     */
    public function whereBetween(string $column, array $values): static
    {
        $newRepository = $this->repository->whereBetween($column, $values);
        
        return new static($newRepository, $this->cache, $this->ttl);
    }

    /**
     * {@inheritDoc}
     */
    public function whereNull(string $column): static
    {
        $newRepository = $this->repository->whereNull($column);
        
        return new static($newRepository, $this->cache, $this->ttl);
    }

    /**
     * {@inheritDoc}
     */
    public function whereNotNull(string $column): static
    {
        $newRepository = $this->repository->whereNotNull($column);
        
        return new static($newRepository, $this->cache, $this->ttl);
    }

    /**
     * {@inheritDoc}
     */
    public function limit(int $limit): static
    {
        $newRepository = $this->repository->limit($limit);
        
        return new static($newRepository, $this->cache, $this->ttl);
    }

    /**
     * {@inheritDoc}
     */
    public function offset(int $offset): static
    {
        $newRepository = $this->repository->offset($offset);
        
        return new static($newRepository, $this->cache, $this->ttl);
    }

    /**
     * {@inheritDoc}
     */
    public function fresh(): static
    {
        $newRepository = $this->repository->fresh();
        
        return new static($newRepository, $this->cache, $this->ttl);
    }

    /**
     * {@inheritDoc}
     */
    public function get(array $columns = ['*']): Collection
    {
        $cacheKey = $this->generateCacheKey('get', [$columns]);
        
        return $this->cache->tags($this->cacheTags)->remember(
            $cacheKey,
            $this->ttl,
            fn() => $this->repository->get($columns)
        );
    }

    /**
     * {@inheritDoc}
     */
    public function first(array $columns = ['*']): ?Model
    {
        $cacheKey = $this->generateCacheKey('first', [$columns]);
        
        return $this->cache->tags($this->cacheTags)->remember(
            $cacheKey,
            $this->ttl,
            fn() => $this->repository->first($columns)
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getModel(): Model
    {
        return $this->repository->getModel();
    }

    /**
     * {@inheritDoc}
     */
    public function setModel(Model $model): static
    {
        $newRepository = $this->repository->setModel($model);
        
        return new static($newRepository, $this->cache, $this->ttl);
    }

    /**
     * Invalidate all cache entries for this repository.
     * 
     * @return void
     */
    public function invalidateCache(): void
    {
        $this->cache->tags($this->cacheTags)->flush();
        
        $this->logCacheOperation('invalidate');
    }

    /**
     * Invalidate specific cache entry.
     * 
     * @param string $key Cache key to invalidate
     * @return void
     */
    public function invalidateCacheKey(string $key): void
    {
        $this->cache->forget($key);
        
        $this->logCacheOperation('invalidateKey', ['key' => $key]);
    }

    /**
     * Get cache statistics.
     * 
     * @return array<string, mixed>
     */
    public function getCacheStats(): array
    {
        return [
            'prefix' => $this->cachePrefix,
            'tags' => $this->cacheTags,
            'ttl' => $this->ttl,
            'model' => get_class($this->repository->getModel()),
        ];
    }

    /**
     * Generate a cache key for the given method and parameters.
     * 
     * @param string $method Method name
     * @param array<mixed> $parameters Method parameters
     * @return string
     */
    private function generateCacheKey(string $method, array $parameters = []): string
    {
        $keyParts = [
            $this->cachePrefix,
            $method,
            md5(serialize($parameters))
        ];
        
        return implode(':', $keyParts);
    }

    /**
     * Log cache operations for monitoring and debugging.
     * 
     * @param string $operation Operation name
     * @param array<string, mixed> $context Additional context
     * @return void
     */
    private function logCacheOperation(string $operation, array $context = []): void
    {
        Log::debug('Repository cache operation', array_merge([
            'repository' => get_class($this->repository),
            'operation' => $operation,
            'cache_prefix' => $this->cachePrefix,
        ], $context));
    }
}