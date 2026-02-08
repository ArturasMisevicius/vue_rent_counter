<?php

declare(strict_types=1);

namespace App\Repositories\Criteria;

use Illuminate\Database\Eloquent\Builder;

/**
 * Criteria Interface
 * 
 * Defines the contract for query criteria/specifications that can be
 * applied to Eloquent query builders for reusable query logic.
 * 
 * This follows the Specification pattern to encapsulate business rules
 * and query logic in reusable, composable objects.
 */
interface CriteriaInterface
{
    /**
     * Apply the criteria to the query builder.
     * 
     * @param Builder $query The Eloquent query builder
     * @return Builder The modified query builder
     */
    public function apply(Builder $query): Builder;

    /**
     * Get a description of what this criteria does.
     * 
     * @return string
     */
    public function getDescription(): string;

    /**
     * Get the criteria parameters.
     * 
     * @return array<string, mixed>
     */
    public function getParameters(): array;
}