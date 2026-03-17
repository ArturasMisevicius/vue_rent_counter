<?php

namespace App\Filament\Resources\Tenants\Pages;

use App\Filament\Resources\Tenants\TenantResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;

class ListTenants extends ListRecords
{
    protected static string $resource = TenantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function table(Table $table): Table
    {
        return parent::table($table)
            ->emptyState(view('components.ui.empty-state', [
                'heading' => 'You have not added any tenants yet',
                'description' => 'Invite your first tenant so they can access the portal, invoices, and meter submissions.',
                'actionLabel' => 'Invite Your First Tenant',
                'actionUrl' => TenantResource::getUrl('create'),
            ]));
    }
}
