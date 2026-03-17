<?php

namespace App\Filament\Resources\Subscriptions\Pages;

use App\Enums\SubscriptionPlan;
use App\Filament\Resources\Subscriptions\SubscriptionResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditSubscription extends EditRecord
{
    protected static string $resource = SubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $plan = SubscriptionPlan::from($data['plan']);

        $record->update([
            ...$data,
            ...$plan->snapshotAttributes(),
        ]);

        return $record->refresh();
    }
}
