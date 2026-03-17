<?php

namespace App\Actions\Superadmin\Organizations;

use App\Enums\OrganizationStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Organization;
use Illuminate\Support\Facades\DB;

class ReinstateOrganizationAction
{
    public function __invoke(Organization $organization): Organization
    {
        return DB::transaction(function () use ($organization): Organization {
            $organization->forceFill([
                'status' => OrganizationStatus::ACTIVE,
            ])->save();

            $organization->subscriptions()
                ->current()
                ->where('status', SubscriptionStatus::SUSPENDED)
                ->get()
                ->each(fn ($subscription) => $subscription->forceFill([
                    'status' => SubscriptionStatus::ACTIVE,
                ])->save());

            return $organization->refresh();
        });
    }
}
