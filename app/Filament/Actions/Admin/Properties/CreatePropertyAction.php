<?php

namespace App\Filament\Actions\Admin\Properties;

use App\Filament\Support\Admin\SubscriptionLimitGuard;
use App\Http\Requests\Admin\Properties\StorePropertyRequest;
use App\Models\Organization;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class CreatePropertyAction
{
    public function __construct(
        private readonly SubscriptionLimitGuard $subscriptionLimitGuard,
    ) {}

    public function handle(Organization $organization, array $data): Property
    {
        if (! $this->isSuperadmin()) {
            $this->subscriptionLimitGuard->ensureCanCreateProperty($organization);
        }

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
        /** @var StorePropertyRequest $request */
        $request = new StorePropertyRequest;
        $validated = $request
            ->forOrganization($organizationId)
            ->validatePayload($data);

        unset($validated['subscription_limit']);

        return $validated;
    }

    private function isSuperadmin(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->isSuperadmin();
    }
}
