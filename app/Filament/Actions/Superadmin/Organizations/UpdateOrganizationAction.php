<?php

namespace App\Filament\Actions\Superadmin\Organizations;

use App\Enums\SubscriptionPlan;
use App\Enums\UserRole;
use App\Http\Requests\Superadmin\Organizations\UpdateOrganizationRequest;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UpdateOrganizationAction
{
    public function handle(Organization $organization, array $attributes): Organization
    {
        /** @var UpdateOrganizationRequest $request */
        $request = new UpdateOrganizationRequest;
        $validated = $request->validatePayload($attributes);

        return DB::transaction(function () use ($organization, $validated): Organization {
            $organization->update([
                'name' => $validated['name'],
                'slug' => Str::slug($validated['name']),
            ]);

            if (filled($validated['owner_email'])) {
                $owner = User::query()
                    ->select(['id', 'organization_id', 'email', 'name', 'role', 'status', 'locale'])
                    ->where('email', $validated['owner_email'])
                    ->first();

                if ($owner !== null && $owner->organization_id !== null && $owner->organization_id !== $organization->id) {
                    throw ValidationException::withMessages([
                        'owner_email' => 'The selected owner already belongs to another organization.',
                    ]);
                }

                if ($owner !== null) {
                    $owner->forceFill([
                        'organization_id' => $organization->id,
                        'role' => UserRole::ADMIN,
                        'name' => $validated['owner_name'] ?? $owner->name,
                    ])->save();

                    $organization->forceFill([
                        'owner_user_id' => $owner->id,
                    ])->save();
                }
            }

            $plan = filled($validated['plan'] ?? null)
                ? SubscriptionPlan::from((string) $validated['plan'])
                : null;

            if ($plan instanceof SubscriptionPlan) {
                $subscription = $organization->subscriptions()
                    ->select([
                        'id',
                        'organization_id',
                        'plan',
                        'status',
                        'starts_at',
                        'expires_at',
                        'is_trial',
                        'property_limit_snapshot',
                        'tenant_limit_snapshot',
                        'meter_limit_snapshot',
                        'invoice_limit_snapshot',
                    ])
                    ->latest('expires_at')
                    ->first();

                if ($subscription !== null) {
                    $subscription->applyPlanSnapshots($plan);
                    $subscription->save();
                }
            }

            return $organization->fresh([
                'owner:id,name,email',
                'subscriptions',
            ]);
        });
    }
}
