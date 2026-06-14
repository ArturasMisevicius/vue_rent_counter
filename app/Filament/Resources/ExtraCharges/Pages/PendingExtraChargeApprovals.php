<?php

declare(strict_types=1);

namespace App\Filament\Resources\ExtraCharges\Pages;

use App\Enums\ExtraChargeStatus;
use App\Filament\Resources\ExtraCharges\ExtraChargeResource;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Builder;

class PendingExtraChargeApprovals extends ListExtraCharges
{
    protected static string $resource = ExtraChargeResource::class;

    public function getTitle(): string
    {
        return __('admin.extra_charges.titles.pending_approvals');
    }

    protected function getTableQuery(): ?Builder
    {
        return parent::getTableQuery()?->where('status', ExtraChargeStatus::PENDING_REVIEW);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('allCharges')
                ->label(__('admin.extra_charges.actions.all_charges'))
                ->url(ExtraChargeResource::getUrl('index')),
        ];
    }
}
