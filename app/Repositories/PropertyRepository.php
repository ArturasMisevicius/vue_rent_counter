<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\PropertyRepositoryInterface;
use App\Enums\PropertyType;
use App\Models\Property;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Property Repository Implementation
 * 
 * Provides property-specific data access operations with tenant awareness,
 * type-based filtering, and property management functionality.
 * 
 * @extends BaseRepository<Property>
 */
class PropertyRepository extends BaseRepository implements PropertyRepositoryInterface
{
    /**
     * Create a new property repository instance.
     * 
     * @param Property $model
     */
    public function __construct(Property $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritDoc}
     */
    public function findByType(PropertyType $type): Collection
    {
        try {
            return $this->query->ofType($type)->get();
        } catch (\Throwable $e) {
            $this->handleException($e, ['method' => 'findByType', 'type' => $type->value]);
            return new Collection();
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findByBuilding(int $buildingId): Collection
    {
        try {
            return $this->query->where('building_id', $buildingId)->get();
        } catch (\Throwable $e) {
            $this->handleException($e, ['method' => 'findByBuilding', 'buildingId' => $buildingId]);
            return new Collection();
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findOccupied(): Collection
    {
        try {
            return $this->query->occupied()->get();
        } catch (\Throwable $e) {
            $this->handleException($e, ['method' => 'findOccupied']);
            return new Collection();
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findVacant(): Collection
    {
        try {
            return $this->query->vacant()->get();
        } catch (\Throwable $e) {
            $this->handleException($e, ['method' => 'findVacant']);
            return new Collection();
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findWithActiveMeters(): Collection
    {
        try {
            return $this->query->withActiveMeters()->get();
        } catch (\Throwable $e) {
            $this->handleException($e, ['method' => 'findWithActiveMeters']);
            return new Collection();
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findResidential(): Collection
    {
        try {
            return $this->query->residential()->get();
        } catch (\Throwable $e) {
            $this->handleException($e, ['method' => 'findResidential']);
            return new Collection();
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findCommercial(): Collection
    {
        try {
            return $this->query->commercial()->get();
        } catch (\Throwable $e) {
            $this->handleException($e, ['method' => 'findCommercial']);
            return new Collection();
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function searchByAddress(string $search): Collection
    {
        try {
            return $this->query
                ->where(function ($query) use ($search) {
                    $query->where('address', 'LIKE', "%{$search}%")
                          ->orWhere('unit_number', 'LIKE', "%{$search}%");
                })
                ->get();
        } catch (\Throwable $e) {
            $this->handleException($e, ['method' => 'searchByAddress', 'search' => $search]);
            return new Collection();
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findWithTags(array $tags): Collection
    {
        try {
            return $this->query->withTags($tags)->get();
        } catch (\Throwable $e) {
            $this->handleException($e, ['method' => 'findWithTags', 'tags' => $tags]);
            return new Collection();
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findByAreaRange(float $minArea, float $maxArea): Collection
    {
        try {
            return $this->query
                ->whereBetween('area_sqm', [$minArea, $maxArea])
                ->get();
        } catch (\Throwable $e) {
            $this->handleException($e, [
                'method' => 'findByAreaRange',
                'minArea' => $minArea,
                'maxArea' => $maxArea
            ]);
            return new Collection();
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findWithUnitNumbers(): Collection
    {
        try {
            return $this->query->whereNotNull('unit_number')->get();
        } catch (\Throwable $e) {
            $this->handleException($e, ['method' => 'findWithUnitNumbers']);
            return new Collection();
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findWithoutUnitNumbers(): Collection
    {
        try {
            return $this->query->whereNull('unit_number')->get();
        } catch (\Throwable $e) {
            $this->handleException($e, ['method' => 'findWithoutUnitNumbers']);
            return new Collection();
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getWithCommonRelations(): Collection
    {
        try {
            return Property::withCommonRelations()->get();
        } catch (\Throwable $e) {
            $this->handleException($e, ['method' => 'getWithCommonRelations']);
            return new Collection();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function countByType(PropertyType $type): int
    {
        try {
            return $this->query->ofType($type)->count();
        } catch (\Throwable $e) {
            $this->handleException($e, ['method' => 'countByType', 'type' => $type->value]);
            return 0;
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function countOccupied(): int
    {
        try {
            return $this->query->occupied()->count();
        } catch (\Throwable $e) {
            $this->handleException($e, ['method' => 'countOccupied']);
            return 0;
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function countVacant(): int
    {
        try {
            return $this->query->vacant()->count();
        } catch (\Throwable $e) {
            $this->handleException($e, ['method' => 'countVacant']);
            return 0;
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findByTenantId(int $tenantId): Collection
    {
        try {
            return $this->query
                ->whereHas('tenants', function ($query) use ($tenantId) {
                    $query->where('tenant_id', $tenantId);
                })
                ->get();
        } catch (\Throwable $e) {
            $this->handleException($e, ['method' => 'findByTenantId', 'tenantId' => $tenantId]);
            return new Collection();
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findAvailableForAssignment(): Collection
    {
        try {
            return $this->query->vacant()->get();
        } catch (\Throwable $e) {
            $this->handleException($e, ['method' => 'findAvailableForAssignment']);
            return new Collection();
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getPropertyStats(): array
    {
        try {
            return [
                'total_properties' => $this->count(),
                'occupied_properties' => $this->countOccupied(),
                'vacant_properties' => $this->countVacant(),
                'apartment_count' => $this->countByType(PropertyType::APARTMENT),
                'house_count' => $this->countByType(PropertyType::HOUSE),
                'studio_count' => $this->countByType(PropertyType::STUDIO),
                'office_count' => $this->countByType(PropertyType::OFFICE),
                'retail_count' => $this->countByType(PropertyType::RETAIL),
                'warehouse_count' => $this->countByType(PropertyType::WAREHOUSE),
                'commercial_count' => $this->countByType(PropertyType::COMMERCIAL),
                'properties_with_meters' => $this->query->withActiveMeters()->count(),
                'properties_with_units' => $this->query->whereNotNull('unit_number')->count(),
            ];
        } catch (\Throwable $e) {
            $this->handleException($e, ['method' => 'getPropertyStats']);
            return [];
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findWithServiceConfigurations(): Collection
    {
        try {
            return $this->query
                ->whereHas('serviceConfigurations')
                ->with('serviceConfigurations')
                ->get();
        } catch (\Throwable $e) {
            $this->handleException($e, ['method' => 'findWithServiceConfigurations']);
            return new Collection();
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findByServiceType(string $serviceType): Collection
    {
        try {
            return $this->query
                ->whereHas('serviceConfigurations', function ($query) use ($serviceType) {
                    $query->where('service_type', $serviceType);
                })
                ->get();
        } catch (\Throwable $e) {
            $this->handleException($e, ['method' => 'findByServiceType', 'serviceType' => $serviceType]);
            return new Collection();
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function assignTenant(int $propertyId, int $tenantId): Property
    {
        try {
            return $this->transaction(function () use ($propertyId, $tenantId) {
                $property = $this->findOrFail($propertyId);
                
                // Check if property can be assigned
                if (!$property->canAssignTenant()) {
                    throw new \App\Exceptions\RepositoryException('Property is already occupied and cannot be assigned');
                }
                
                $property->tenants()->attach($tenantId, [
                    'assigned_at' => now(),
                ]);
                
                $this->logOperation('assignTenant', [
                    'propertyId' => $propertyId,
                    'tenantId' => $tenantId
                ]);
                
                return $property->load('tenants');
            });
        } catch (ModelNotFoundException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->handleException($e, [
                'method' => 'assignTenant',
                'propertyId' => $propertyId,
                'tenantId' => $tenantId
            ]);
            throw new \App\Exceptions\RepositoryException("Failed to assign tenant to property", 0, $e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function removeTenant(int $propertyId, int $tenantId): Property
    {
        try {
            return $this->transaction(function () use ($propertyId, $tenantId) {
                $property = $this->findOrFail($propertyId);
                
                $property->tenants()->updateExistingPivot($tenantId, [
                    'vacated_at' => now(),
                ]);
                
                $this->logOperation('removeTenant', [
                    'propertyId' => $propertyId,
                    'tenantId' => $tenantId
                ]);
                
                return $property->load('tenants');
            });
        } catch (ModelNotFoundException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->handleException($e, [
                'method' => 'removeTenant',
                'propertyId' => $propertyId,
                'tenantId' => $tenantId
            ]);
            throw new \App\Exceptions\RepositoryException("Failed to remove tenant from property", 0, $e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findWithActiveProjects(): Collection
    {
        try {
            return $this->query
                ->whereHas('activeProjects')
                ->with('activeProjects')
                ->get();
        } catch (\Throwable $e) {
            $this->handleException($e, ['method' => 'findWithActiveProjects']);
            return new Collection();
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * Find properties by building address.
     * 
     * @param string $buildingAddress
     * @return Collection<int, Property>
     */
    public function findByBuildingAddress(string $buildingAddress): Collection
    {
        try {
            return $this->query
                ->whereHas('building', function ($query) use ($buildingAddress) {
                    $query->where('address', 'LIKE', "%{$buildingAddress}%");
                })
                ->get();
        } catch (\Throwable $e) {
            $this->handleException($e, ['method' => 'findByBuildingAddress', 'buildingAddress' => $buildingAddress]);
            return new Collection();
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * Find properties with specific meter types.
     * 
     * @param array<string> $meterTypes
     * @return Collection<int, Property>
     */
    public function findWithMeterTypes(array $meterTypes): Collection
    {
        try {
            return $this->query
                ->whereHas('meters', function ($query) use ($meterTypes) {
                    $query->whereIn('type', $meterTypes);
                })
                ->get();
        } catch (\Throwable $e) {
            $this->handleException($e, ['method' => 'findWithMeterTypes', 'meterTypes' => $meterTypes]);
            return new Collection();
        } finally {
            $this->resetQuery();
        }
    }

    /**
     * Get property occupancy rate.
     * 
     * @return float
     */
    public function getOccupancyRate(): float
    {
        try {
            $total = $this->count();
            if ($total === 0) {
                return 0.0;
            }
            
            $occupied = $this->countOccupied();
            return round(($occupied / $total) * 100, 2);
        } catch (\Throwable $e) {
            $this->handleException($e, ['method' => 'getOccupancyRate']);
            return 0.0;
        }
    }
}