<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\Tenants;

use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Actions\Admin\Properties\AssignTenantToPropertyAction;
use App\Filament\Support\Admin\SubscriptionLimitGuard;
use App\Http\Requests\Admin\Tenants\StoreTenantRequest;
use App\Models\Organization;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateTenantAction
{
    public function __construct(
        private readonly SubscriptionLimitGuard $subscriptionLimitGuard,
        private readonly AssignTenantToPropertyAction $assignTenantToPropertyAction,
        private readonly SendTenantInvitation $sendTenantInvitation,
    ) {}

    public function handle(User $actor, array $data, ?Organization $organization = null): User
    {
        $organization ??= $actor->organization;

        if ((! $actor->isAdmin() && ! $actor->isManager() && ! $actor->isSuperadmin()) || $organization === null) {
            abort(403);
        }

        if (! $actor->isSuperadmin()) {
            $this->subscriptionLimitGuard->ensureCanCreateTenant($organization);
        }

        $validated = $this->validate($actor, $organization->id, $data);

        return DB::transaction(function () use ($actor, $organization, $validated): User {
            $tenant = User::query()->create([
                'organization_id' => $organization->id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'role' => UserRole::TENANT,
                'status' => UserStatus::INACTIVE,
                'tenant_status' => TenantStatus::DRAFT,
                'portal_access_enabled' => false,
                'locale' => $validated['locale'],
                'password' => Str::random(32),
            ]);

            if ($validated['create_portal_access'] && $validated['send_invitation_now']) {
                $this->sendTenantInvitation->handle(
                    $actor->isSuperadmin() ? $this->resolveInviterForSuperadmin($organization) : $actor,
                    $tenant,
                    $validated['invitation_expiration_days'],
                );
            }

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
     *     phone: string|null,
     *     locale: string,
     *     create_portal_access: bool,
     *     send_invitation_now: bool,
     *     invitation_expiration_days: int,
     *     property_id: int|null,
     *     unit_area_sqm: float|int|null,
     *     property: Property|null
     * }
     */
    private function validate(User $actor, int $organizationId, array $data): array
    {
        /** @var StoreTenantRequest $request */
        $request = new StoreTenantRequest;
        $validated = $request
            ->forOrganization($organizationId)
            ->validatePayload($data, $actor);

        /** @var Property|null $property */
        $property = null;

        if ($validated['property_id'] !== null) {
            $property = Property::query()
                ->select(['id', 'organization_id', 'building_id', 'name', 'floor', 'unit_number', 'type', 'floor_area_sqm'])
                ->where('organization_id', $organizationId)
                ->find($validated['property_id']);
        }

        return [
            ...$validated,
            'property' => $property,
        ];
    }

    private function resolveInviterForSuperadmin(Organization $organization): User
    {
        $organization->loadMissing([
            'owner:id,organization_id,name,email,role,status',
        ]);

        $inviter = $organization->owner;

        if (! $inviter instanceof User || ! $inviter->isAdminLike()) {
            $inviter = $organization->users()
                ->select(['id', 'organization_id', 'name', 'email', 'role', 'status'])
                ->adminLike()
                ->orderedByName()
                ->first();
        }

        if (! $inviter instanceof User) {
            throw ValidationException::withMessages([
                'organization_id' => __('superadmin.organizations.messages.no_primary_admin'),
            ]);
        }

        return $inviter;
    }
}
