<?php

namespace App\Filament\Actions\Admin\Meters;

use App\Enums\MeterStatus;
use App\Enums\MeterType;
use App\Enums\UnitOfMeasurement;
use App\Http\Requests\Admin\Meters\MeterRequest;
use App\Models\Meter;
use App\Models\Organization;

class CreateMeterAction
{
    public function handle(Organization $organization, array $data): Meter
    {
        $validated = $this->validate($organization->id, $data);
        $type = $validated['type'] instanceof MeterType
            ? $validated['type']
            : MeterType::from($validated['type']);

        return Meter::query()->create([
            ...$validated,
            'organization_id' => $organization->id,
            'type' => $type,
            'unit' => $this->resolveUnitValue($validated['unit'] ?? null, $type),
        ]);
    }

    /**
     * @return array{
     *     property_id: int,
     *     name: string,
     *     identifier: string,
     *     type: MeterType|string,
     *     unit: UnitOfMeasurement|string|null,
     *     status: MeterStatus|string,
     *     installed_at: string|null
     * }
     */
    private function validate(int $organizationId, array $data): array
    {
        /** @var MeterRequest $request */
        $request = new MeterRequest;
        $validated = $request
            ->forOrganization($organizationId)
            ->validatePayload($data);

        return $validated;
    }

    private function resolveUnitValue(UnitOfMeasurement|string|null $unit, MeterType $type): string
    {
        if ($unit instanceof UnitOfMeasurement) {
            return $unit->value;
        }

        if (is_string($unit) && $unit !== '') {
            return $unit;
        }

        return $type->defaultUnit()->value;
    }
}
