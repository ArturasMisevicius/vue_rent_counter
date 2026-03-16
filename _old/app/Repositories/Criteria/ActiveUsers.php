<?php

declare(strict_types=1);

namespace App\Repositories\Criteria;

use Illuminate\Database\Eloquent\Builder;

/**
 * Active Users Criteria
 * 
 * Filters query to include only active users (is_active = true).
 * This criteria can be applied to any User query to exclude
 * deactivated or suspended accounts.
 */
class ActiveUsers implements CriteriaInterface
{
    /**
     * {@inheritDoc}
     */
    public function apply(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(): string
    {
        return 'Filter to include only active users';
    }

    /**
     * {@inheritDoc}
     */
    public function getParameters(): array
    {
        return [
            'is_active' => true,
        ];
    }
}