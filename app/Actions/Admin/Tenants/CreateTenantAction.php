<?php

namespace App\Actions\Admin\Tenants;

use App\Actions\Admin\Properties\AssignTenantToPropertyAction;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\OrganizationInvitation;
use App\Models\Property;
use App\Models\User;
use App\Notifications\Auth\OrganizationInvitationNotification;
use App\Support\Admin\SubscriptionLimitGuard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CreateTenantAction
{
    public function __construct(
        private readonly SubscriptionLimitGuard $subscriptionLimitGuard,
        private readonly AssignTenantToPropertyAction $assignTenantToPropertyAction,
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
                'password' => Hash::make(Str::random(32)),
            ]);

            $invitation = OrganizationInvitation::query()->create([
                'organization_id' => $actor->organization_id,
                'inviter_user_id' => $actor->id,
                'email' => $tenant->email,
                'role' => UserRole::TENANT,
                'full_name' => $tenant->name,
                'token' => (string) Str::uuid(),
                'expires_at' => now()->addDays(7),
                'accepted_at' => null,
            ]);

            Notification::route('mail', $invitation->email)
                ->notify(new OrganizationInvitationNotification($invitation));

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
        /** @var array{
         *     name: string,
         *     email: string,
         *     locale: string,
         *     status: UserStatus,
         *     property_id: int|null,
         *     unit_area_sqm: float|int|null
         * } $validated
         */
        $validated = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')],
            'locale' => ['required', Rule::in(array_keys(config('tenanto.locales', [])))],
            'status' => ['required', Rule::enum(UserStatus::class)],
            'property_id' => [
                'nullable',
                'integer',
                Rule::exists('properties', 'id')->where(
                    fn ($query) => $query->where('organization_id', $organizationId),
                ),
            ],
            'unit_area_sqm' => ['nullable', 'numeric', 'min:0'],
        ])->validate();

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
