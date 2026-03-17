<?php

namespace App\Actions\Admin\Tenants;

use App\Actions\Admin\Properties\AssignTenantToPropertyAction;
use App\Enums\UserStatus;
use App\Models\OrganizationInvitation;
use App\Models\Property;
use App\Models\User;
use App\Support\Admin\SubscriptionLimitGuard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UpdateTenantAction
{
    public function __construct(
        private readonly AssignTenantToPropertyAction $assignTenantToPropertyAction,
        private readonly SubscriptionLimitGuard $subscriptionLimitGuard,
    ) {}

    public function handle(User $tenant, array $data): User
    {
        $this->subscriptionLimitGuard->ensureCanWrite($tenant->organization_id);

        $validated = $this->validate($tenant, $data);

        return DB::transaction(function () use ($tenant, $validated): User {
            $originalEmail = $tenant->email;

            $tenant->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'locale' => $validated['locale'],
                'status' => $validated['status'],
            ]);

            if ($validated['property'] !== null) {
                $this->assignTenantToPropertyAction->handle(
                    $validated['property'],
                    $tenant->fresh(),
                    $validated['unit_area_sqm'],
                );
            } elseif ($tenant->fresh()->currentProperty !== null) {
                $tenant->fresh()->currentPropertyAssignment?->update([
                    'unassigned_at' => now(),
                ]);
            }

            OrganizationInvitation::query()
                ->where('organization_id', $tenant->organization_id)
                ->whereNull('accepted_at')
                ->where('email', $originalEmail)
                ->update([
                    'email' => $validated['email'],
                    'full_name' => $validated['name'],
                ]);

            return $tenant->fresh([
                'currentPropertyAssignment.property',
                'propertyAssignments',
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
    private function validate(User $tenant, array $data): array
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
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($tenant->id)],
            'locale' => ['required', Rule::in(array_keys(config('tenanto.locales', [])))],
            'status' => ['required', Rule::enum(UserStatus::class)],
            'property_id' => [
                'nullable',
                'integer',
                Rule::exists('properties', 'id')->where(
                    fn ($query) => $query->where('organization_id', $tenant->organization_id),
                ),
            ],
            'unit_area_sqm' => ['nullable', 'numeric', 'min:0'],
        ])->validate();

        /** @var Property|null $property */
        $property = null;

        if ($validated['property_id'] !== null) {
            $property = Property::query()
                ->select(['id', 'organization_id', 'building_id', 'name', 'unit_number', 'type', 'floor_area_sqm'])
                ->where('organization_id', $tenant->organization_id)
                ->find($validated['property_id']);
        }

        return [
            ...$validated,
            'property' => $property,
        ];
    }
}
