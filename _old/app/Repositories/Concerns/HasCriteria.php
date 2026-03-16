<?php

declare(strict_types=1);

namespace App\Repositories\Concerns;

use App\Repositories\Criteria\CriteriaInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Has Criteria Trait
 * 
 * Adds criteria/specification pattern support to repositories.
 * Allows for composable, reusable query logic that can be
 * applied to any repository.
 * 
 * Usage:
 * ```php
 * $repository->pushCriteria(new ActiveUsers())
 *           ->pushCriteria(new DateRange('created_at', $start, $end))
 *           ->get();
 * ```
 */
trait HasCriteria
{
    /**
     * Collection of criteria to apply to queries.
     * 
     * @var Collection<int, CriteriaInterface>
     */
    protected Collection $criteria;

    /**
     * Whether to skip applying criteria.
     */
    protected bool $skipCriteria = false;

    /**
     * Initialize criteria collection.
     * 
     * @return void
     */
    protected function initializeCriteria(): void
    {
        $this->criteria = new Collection();
    }

    /**
     * Push a new criteria to the collection.
     * 
     * @param CriteriaInterface $criteria
     * @return static
     */
    public function pushCriteria(CriteriaInterface $criteria): static
    {
        if (!isset($this->criteria)) {
            $this->initializeCriteria();
        }

        $this->criteria->push($criteria);
        
        return $this;
    }

    /**
     * Pop the last criteria from the collection.
     * 
     * @return CriteriaInterface|null
     */
    public function popCriteria(): ?CriteriaInterface
    {
        if (!isset($this->criteria)) {
            $this->initializeCriteria();
        }

        return $this->criteria->pop();
    }

    /**
     * Get all criteria.
     * 
     * @return Collection<int, CriteriaInterface>
     */
    public function getCriteria(): Collection
    {
        if (!isset($this->criteria)) {
            $this->initializeCriteria();
        }

        return $this->criteria;
    }

    /**
     * Apply a criteria by class name.
     * 
     * @param string $criteriaClass
     * @param array<mixed> $parameters
     * @return static
     */
    public function getByCriteria(string $criteriaClass, array $parameters = []): static
    {
        $criteria = new $criteriaClass(...$parameters);
        
        return $this->pushCriteria($criteria);
    }

    /**
     * Skip applying criteria for the next query.
     * 
     * @param bool $skip
     * @return static
     */
    public function skipCriteria(bool $skip = true): static
    {
        $this->skipCriteria = $skip;
        
        return $this;
    }

    /**
     * Clear all criteria.
     * 
     * @return static
     */
    public function clearCriteria(): static
    {
        if (!isset($this->criteria)) {
            $this->initializeCriteria();
        }

        $this->criteria = new Collection();
        
        return $this;
    }

    /**
     * Apply all criteria to the query builder.
     * 
     * @param Builder $query
     * @return Builder
     */
    protected function applyCriteria(Builder $query): Builder
    {
        if ($this->skipCriteria || !isset($this->criteria)) {
            return $query;
        }

        foreach ($this->criteria as $criteria) {
            if ($criteria instanceof CriteriaInterface) {
                $query = $criteria->apply($query);
            }
        }

        return $query;
    }

    /**
     * Reset criteria state after query execution.
     * 
     * @return void
     */
    protected function resetCriteriaState(): void
    {
        $this->skipCriteria = false;
    }

    /**
     * Get criteria summary for debugging.
     * 
     * @return array<string, mixed>
     */
    public function getCriteriaSummary(): array
    {
        if (!isset($this->criteria)) {
            $this->initializeCriteria();
        }

        return [
            'count' => $this->criteria->count(),
            'skip_criteria' => $this->skipCriteria,
            'criteria' => $this->criteria->map(function (CriteriaInterface $criteria) {
                return [
                    'class' => get_class($criteria),
                    'description' => $criteria->getDescription(),
                    'parameters' => $criteria->getParameters(),
                ];
            })->toArray(),
        ];
    }

    /**
     * Apply criteria and execute a callback with the modified query.
     * 
     * @param callable $callback
     * @return mixed
     */
    public function withCriteria(callable $callback): mixed
    {
        $query = $this->applyCriteria($this->query);
        $result = $callback($query);
        $this->resetCriteriaState();
        
        return $result;
    }

    /**
     * Execute query with criteria applied.
     * 
     * @param string $method Query method to execute
     * @param array<mixed> $parameters Method parameters
     * @return mixed
     */
    protected function executeWithCriteria(string $method, array $parameters = []): mixed
    {
        return $this->withCriteria(function (Builder $query) use ($method, $parameters) {
            return $query->{$method}(...$parameters);
        });
    }
}