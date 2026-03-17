<?php

namespace App\Actions\Superadmin\Organizations;

use App\Enums\OrganizationStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Organization;
use Illuminate\Support\Facades\DB;

class SuspendOrganizationAction
{
    public function __invoke(Organization $organization): Organization
    {
        return DB::transaction(function () use ($organization): Organization {
            $organization->forceFill([
                'status' => OrganizationStatus::SUSPENDED,
            ])->save();

            $organization->subscriptions()
                ->current()
                ->where('status', SubscriptionStatus::ACTIVE)
                ->get()
                ->each(fn ($subscription) => $subscription->forceFill([
                    'status' => SubscriptionStatus::SUSPENDED,
                ])->save());

            return $organization->refresh();
        });
    }
}
