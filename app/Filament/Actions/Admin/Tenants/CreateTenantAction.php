<?php

namespace App\Filament\Actions\Admin\Tenants;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Actions\Admin\Properties\AssignTenantToPropertyAction;
use App\Filament\Actions\Auth\CreateOrganizationInvitationAction;
use App\Filament\Support\Admin\SubscriptionLimitGuard;
use App\Http\Requests\Admin\Tenants\StoreTenantRequest;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateTenantAction
{
    public function __construct(
        private readonly SubscriptionLimitGuard $subscriptionLimitGuard,
        private readonly AssignTenantToPropertyAction $assignTenantToPropertyAction,
        private readonly CreateOrganizationInvitationAction $createOrganizationInvitationAction,
    ) {}

    public function handle(User $actor, array $data): User
    {
        $organization = $actor->organization;

        if ((! $actor->isAdmin() && ! $actor->isManager()) || $organization === null) {
            abort(403);
        }

        $this->subscriptionLimitGuard->ensureCanCreateTenant($organization);

        $validated = $this->validate($organization->id, $data);

        return DB::transaction(function () use ($actor, $validated): User {
            $tenant = User::query()->create([
                'organization_id' => $actor->organization_id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'role' => UserRole::TENANT,
                'status' => $validated['status'],
                'locale' => $validated['locale'],
                'password' => Str::random(32),
            ]);

            $this->createOrganizationInvitationAction->handle($actor, [
                'email' => $tenant->email,
                'role' => UserRole::TENANT,
                'full_name' => $tenant->name,
                'existing_user_id' => $tenant->id,
            ]);
            if ($validated['property'] !== null) {
                $this->assignTenantToPropertyAction->handle(
                    $validated['property'],
                    $tenant,
                    $validated['unit_area_sqm'],
                );
            }

            return $tenant->fresh([
                'currentPropertyAssignment.property',
            ]);
        });
    }

    /**
     * @return array{
     *     name: string,
     *     email: string,
     *     locale: string,
     *     status: UserStatus,
     *     property_id: int|null,
     *     unit_area_sqm: float|int|null,
     *     property: Property|null
     * }
     */
    private function validate(int $organizationId, array $data): array
    {
        /** @var StoreTenantRequest $request */
        $request = new StoreTenantRequest;
        $validated = $request
            ->forOrganization($organizationId)
            ->validatePayload($data);

        /** @var Property|null $property */
        $property = null;

        if ($validated['property_id'] !== null) {
            $property = Property::query()
                ->select(['id', 'organization_id', 'building_id', 'name', 'unit_number', 'type', 'floor_area_sqm'])
                ->where('organization_id', $organizationId)
                ->find($validated['property_id']);
        }

        return [
            ...$validated,
            'property' => $property,
        ];
    }
}
