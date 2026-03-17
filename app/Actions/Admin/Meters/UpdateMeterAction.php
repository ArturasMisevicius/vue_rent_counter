<?php

namespace App\Actions\Admin\Meters;

use App\Enums\MeterStatus;
use App\Enums\MeterType;
use App\Models\Meter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

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
            'unit' => $validated['unit'] ?: $type->defaultUnit(),
        ]);

        return $meter->fresh();
    }

    /**
     * @return array{
     *     property_id: int,
     *     name: string,
     *     identifier: string,
     *     type: MeterType|string,
     *     unit: string|null,
     *     status: MeterStatus|string,
     *     installed_at: string|null
     * }
     */
    private function validate(int $organizationId, array $data): array
    {
        $data['type'] = $data['type'] instanceof MeterType
            ? $data['type']->value
            : $data['type'];
        $data['status'] = $data['status'] instanceof MeterStatus
            ? $data['status']->value
            : $data['status'];

        /** @var array{
         *     property_id: int,
         *     name: string,
         *     identifier: string,
         *     type: MeterType|string,
         *     unit: string|null,
         *     status: MeterStatus|string,
         *     installed_at: string|null
         * } $validated
         */
        $validated = Validator::make($data, [
            'property_id' => [
                'required',
                'integer',
                Rule::exists('properties', 'id')->where(
                    fn ($query) => $query->where('organization_id', $organizationId),
                ),
            ],
            'name' => ['required', 'string', 'max:255'],
            'identifier' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(collect(MeterType::cases())->map->value->all())],
            'unit' => ['nullable', 'string', 'max:50'],
            'status' => ['required', Rule::in(collect(MeterStatus::cases())->map->value->all())],
            'installed_at' => ['nullable', 'date'],
        ])->validate();

        return $validated;
    }
}
