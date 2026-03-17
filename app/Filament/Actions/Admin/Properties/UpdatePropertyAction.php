<?php

namespace App\Filament\Actions\Admin\Properties;

use App\Filament\Support\Admin\SubscriptionLimitGuard;
use App\Http\Requests\Admin\Properties\PropertyRequest;
use App\Models\Property;

class UpdatePropertyAction
{
    public function __construct(
        private readonly SubscriptionLimitGuard $subscriptionLimitGuard,
    ) {}

    public function handle(Property $property, array $data): Property
    {
        $this->subscriptionLimitGuard->ensureCanWrite($property->organization_id);

        $validated = $this->validate($property->organization_id, $data);

        $property->update($validated);

        return $property->fresh();
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
