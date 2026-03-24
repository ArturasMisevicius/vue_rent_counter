<?php

namespace App\Filament\Actions\Superadmin\Organizations;

use App\Enums\SubscriptionPlan;
use App\Enums\UserRole;
use App\Http\Requests\Superadmin\Organizations\UpdateOrganizationRequest;
use App\Models\Organization;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
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
            } else {
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
            }

            if ($subscription !== null) {
                if ($plan instanceof SubscriptionPlan) {
                    $subscription->applyPlanSnapshots($plan);
                }

                if (filled($validated['expires_at'] ?? null)) {
                    $subscription->expires_at = CarbonImmutable::parse((string) $validated['expires_at'])->startOfDay();
                }

                if ($subscription->isDirty()) {
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
