<?php

namespace App\Filament\Actions\Admin\Properties;

use App\Filament\Support\Admin\SubscriptionLimitGuard;
use App\Http\Requests\Admin\Properties\PropertyRequest;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class UpdatePropertyAction
{
    public function __construct(
        private readonly SubscriptionLimitGuard $subscriptionLimitGuard,
    ) {}

    public function handle(Property $property, array $data): Property
    {
        if (! $this->isSuperadmin()) {
            $this->subscriptionLimitGuard->ensureCanWrite($property->organization_id);
        }

        $validated = $this->validate($property->organization_id, $data);

        $property->update($validated);

        return $property->fresh();
    }

    /**
     * @return array{name: string, building_id: int, floor: int|null, unit_number: string|null, type: string, floor_area_sqm: float|int|null}
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

    private function isSuperadmin(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->isSuperadmin();
    }
}
