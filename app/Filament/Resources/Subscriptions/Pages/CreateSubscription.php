<?php

namespace App\Filament\Resources\Subscriptions\Pages;

use App\Enums\SubscriptionPlan;
use App\Filament\Resources\Subscriptions\SubscriptionResource;
use App\Models\Subscription;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateSubscription extends CreateRecord
{
    protected static string $resource = SubscriptionResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $plan = SubscriptionPlan::from($data['plan']);

        return Subscription::query()->create([
            ...$data,
            ...$plan->snapshotAttributes(),
        ]);
    }
}
