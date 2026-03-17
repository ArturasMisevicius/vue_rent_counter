<?php

namespace App\Actions\Admin\Properties;

use App\Enums\PropertyType;
use App\Models\Organization;
use App\Models\Property;
use App\Support\Admin\SubscriptionLimitGuard;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CreatePropertyAction
{
    public function __construct(
        private readonly SubscriptionLimitGuard $subscriptionLimitGuard,
    ) {}

    public function handle(Organization $organization, array $data): Property
    {
        $this->subscriptionLimitGuard->ensureCanCreateProperty($organization);

        $validated = $this->validate($organization->id, $data);

        return Property::query()->create([
            ...$validated,
            'organization_id' => $organization->id,
        ]);
    }

    /**
     * @return array{name: string, building_id: int, unit_number: string, type: string, floor_area_sqm: float|int|null}
     */
    private function validate(int $organizationId, array $data): array
    {
        $data['type'] = $data['type'] instanceof PropertyType
            ? $data['type']->value
            : $data['type'];

        /** @var array{name: string, building_id: int, unit_number: string, type: string, floor_area_sqm: float|int|null} $validated */
        $validated = Validator::make($data, [
            'building_id' => [
                'required',
                'integer',
                Rule::exists('buildings', 'id')->where(
                    fn ($query) => $query->where('organization_id', $organizationId),
                ),
            ],
            'name' => ['required', 'string', 'max:255'],
            'unit_number' => ['required', 'string', 'max:50'],
            'type' => ['required', Rule::in(collect(PropertyType::cases())->map->value->all())],
            'floor_area_sqm' => ['nullable', 'numeric', 'min:0'],
        ])->validate();

        return $validated;
    }
}
