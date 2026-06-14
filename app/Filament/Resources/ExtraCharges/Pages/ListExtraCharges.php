<?php

declare(strict_types=1);

namespace App\Filament\Resources\ExtraCharges\Pages;

use App\Filament\Actions\Help\ContextualHelpAction;
use App\Filament\Resources\ExtraCharges\ExtraChargeResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListExtraCharges extends ListRecords
{
    protected static string $resource = ExtraChargeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ContextualHelpAction::make('extra_charges.index'),
            Action::make('pendingApprovals')
                ->label(__('admin.extra_charges.actions.pending_approvals'))
                ->url(ExtraChargeResource::getUrl('pending-approvals')),
            CreateAction::make(),
        ];
    }
}
