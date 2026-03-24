<?php

namespace App\Filament\Actions\Admin\Meters;

use App\Enums\MeterStatus;
use App\Enums\MeterType;
use App\Enums\UnitOfMeasurement;
use App\Http\Requests\Admin\Meters\MeterRequest;
use App\Models\Meter;

class UpdateMeterAction
{
    public function handle(Meter $meter, array $data): Meter
    {
        $validated = $this->validate($meter->organization_id, $data);
        $type = $validated['type'] instanceof MeterType
            ? $validated['type']
            : MeterType::from($validated['type']);

        $meter->update([
            ...$validated,
            'type' => $type,
            'unit' => $this->resolveUnitValue($validated['unit'] ?? null, $type),
        ]);

        return $meter->fresh();
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
