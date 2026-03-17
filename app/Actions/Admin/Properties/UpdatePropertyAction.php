<?php

namespace App\Actions\Admin\Properties;

use App\Enums\PropertyType;
use App\Models\Building;
use App\Models\Property;
use Illuminate\Validation\ValidationException;

class UpdatePropertyAction
{
    /**
     * @param  array{
     *     building_id: int,
     *     name: string,
     *     unit_number: string,
     *     type: PropertyType|string,
     *     floor_area_sqm: float|string|null
     * }  $attributes
     *
     * @throws ValidationException
     */
    public function handle(Property $property, array $attributes): Property
    {
        $building = Building::query()
            ->select(['id', 'organization_id'])
            ->whereKey($attributes['building_id'])
            ->firstOrFail();

        if ($building->organization_id !== $property->organization_id) {
            throw ValidationException::withMessages([
                'building_id' => __('admin.properties.messages.invalid_building'),
            ]);
        }

        $property->fill([
            'building_id' => $building->id,
            'name' => $attributes['name'],
            'unit_number' => $attributes['unit_number'],
            'type' => $this->normalizeType($attributes['type']),
            'floor_area_sqm' => $attributes['floor_area_sqm'],
        ]);

        $property->save();

        return $property->refresh();
    }

    protected function normalizeType(PropertyType|string $type): string
    {
        return $type instanceof PropertyType ? $type->value : $type;
    }
}
