<?php

declare(strict_types=1);

namespace App\Repositories\Criteria;

use App\Enums\PropertyType;
use Illuminate\Database\Eloquent\Builder;

/**
 * Properties By Type Criteria
 * 
 * Filters properties by their type (apartment, house, office, etc.).
 * Supports single type or multiple types.
 */
class PropertiesByType implements CriteriaInterface
{
    /**
     * Create a new properties by type criteria.
     * 
     * @param PropertyType|array<PropertyType> $types Type or array of types
     */
    public function __construct(
        private readonly PropertyType|array $types
    ) {}

    /**
     * {@inheritDoc}
     */
    public function apply(Builder $query): Builder
    {
        if (is_array($this->types)) {
            return $query->whereIn('type', $this->types);
        }

        return $query->where('type', $this->types);
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(): string
    {
        if (is_array($this->types)) {
            $typeNames = array_map(fn($type) => $type->value, $this->types);
            return 'Filter properties by types: ' . implode(', ', $typeNames);
        }

        return "Filter properties by type: {$this->types->value}";
    }

    /**
     * {@inheritDoc}
     */
    public function getParameters(): array
    {
        return [
            'types' => is_array($this->types) 
                ? array_map(fn($type) => $type->value, $this->types)
                : $this->types->value,
        ];
    }

    /**
     * Create criteria for residential properties.
     * 
     * @return static
     */
    public static function residential(): static
    {
        return new static([
            PropertyType::APARTMENT,
            PropertyType::HOUSE,
            PropertyType::STUDIO,
        ]);
    }

    /**
     * Create criteria for commercial properties.
     * 
     * @return static
     */
    public static function commercial(): static
    {
        return new static([
            PropertyType::OFFICE,
            PropertyType::RETAIL,
            PropertyType::WAREHOUSE,
            PropertyType::COMMERCIAL,
        ]);
    }

    /**
     * Create criteria for apartments only.
     * 
     * @return static
     */
    public static function apartments(): static
    {
        return new static(PropertyType::APARTMENT);
    }

    /**
     * Create criteria for houses only.
     * 
     * @return static
     */
    public static function houses(): static
    {
        return new static(PropertyType::HOUSE);
    }

    /**
     * Create criteria for offices only.
     * 
     * @return static
     */
    public static function offices(): static
    {
        return new static(PropertyType::OFFICE);
    }
}