<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Enums\PropertyType;
use App\Models\Property;
use Illuminate\Database\Eloquent\Collection;

/**
 * Property Repository Interface
 *
 * Defines property-specific repository operations for managing
 * properties, their relationships, and property-related queries.
 *
 * @extends RepositoryInterface<Property>
 */
interface PropertyRepositoryInterface extends RepositoryInterface
{
    /**
     * Find properties by type.
     *
     * @return Collection<int, Property>
     */
    public function findByType(PropertyType $type): Collection;

    /**
     * Find properties by building ID.
     *
     * @return Collection<int, Property>
     */
    public function findByBuilding(int $buildingId): Collection;

    /**
     * Find occupied properties.
     *
     * @return Collection<int, Property>
     */
    public function findOccupied(): Collection;

    /**
     * Find vacant properties.
     *
     * @return Collection<int, Property>
     */
    public function findVacant(): Collection;

    /**
     * Find properties with active meters.
     *
     * @return Collection<int, Property>
     */
    public function findWithActiveMeters(): Collection;

    /**
     * Find residential properties.
     *
     * @return Collection<int, Property>
     */
    public function findResidential(): Collection;

    /**
     * Find commercial properties.
     *
     * @return Collection<int, Property>
     */
    public function findCommercial(): Collection;

    /**
     * Find properties by address search.
     *
     * @return Collection<int, Property>
     */
    public function searchByAddress(string $search): Collection;

    /**
     * Find properties with specific tags.
     *
     * @param  array<string|int>  $tags
     * @return Collection<int, Property>
     */
    public function findWithTags(array $tags): Collection;

    /**
     * Find properties by area range.
     *
     * @return Collection<int, Property>
     */
    public function findByAreaRange(float $minArea, float $maxArea): Collection;

    /**
     * Find properties with unit numbers.
     *
     * @return Collection<int, Property>
     */
    public function findWithUnitNumbers(): Collection;

    /**
     * Find properties without unit numbers.
     *
     * @return Collection<int, Property>
     */
    public function findWithoutUnitNumbers(): Collection;

    /**
     * Get properties with common relations loaded.
     *
     * @return Collection<int, Property>
     */
    public function getWithCommonRelations(): Collection;

    /**
     * Count properties by type.
     */
    public function countByType(PropertyType $type): int;

    /**
     * Count occupied properties.
     */
    public function countOccupied(): int;

    /**
     * Count vacant properties.
     */
    public function countVacant(): int;

    /**
     * Find properties by tenant ID.
     *
     * @return Collection<int, Property>
     */
    public function findByTenantId(int $tenantId): Collection;

    /**
     * Find properties available for assignment.
     *
     * @return Collection<int, Property>
     */
    public function findAvailableForAssignment(): Collection;

    /**
     * Get property statistics summary.
     *
     * @return array<string, mixed>
     */
    public function getPropertyStats(): array;

    /**
     * Find properties with service configurations.
     *
     * @return Collection<int, Property>
     */
    public function findWithServiceConfigurations(): Collection;

    /**
     * Find properties by service type.
     *
     * @return Collection<int, Property>
     */
    public function findByServiceType(string $serviceType): Collection;

    /**
     * Assign tenant to property.
     */
    public function assignTenant(int $propertyId, int $tenantId): Property;

    /**
     * Remove tenant from property.
     */
    public function removeTenant(int $propertyId, int $tenantId): Property;

    /**
     * Find properties with active projects.
     *
     * @return Collection<int, Property>
     */
    public function findWithActiveProjects(): Collection;
}
