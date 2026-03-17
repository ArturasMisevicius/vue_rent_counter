<?php

namespace App\Filament\Actions\Admin\Properties;

use App\Filament\Support\Admin\SubscriptionLimitGuard;
use App\Http\Requests\Admin\Properties\PropertyRequest;
use App\Models\Organization;
use App\Models\Property;

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
        /** @var PropertyRequest $request */
        $request = new PropertyRequest;
        $validated = $request
            ->forOrganization($organizationId)
            ->validatePayload($data);

        return $validated;
    }
}
