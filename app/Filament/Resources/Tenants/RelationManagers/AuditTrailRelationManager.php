<?php

namespace App\Filament\Resources\Tenants\RelationManagers;

use App\Filament\Resources\Tenants\TenantResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

class AuditTrailRelationManager extends RelationManager
{
    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return TenantResource::canView($ownerRecord);
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.tenants.tabs.audit_trail');
    }

    public function getRelationship(): Relation
    {
        $tenant = $this->getOwnerRecord();

        return $tenant->resourceActivityLogs()
            ->forOrganization($tenant->organization_id)
            ->withActorSummary()
            ->recent();
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('occurred_at')
                    ->label(__('admin.tenants.audit.columns.occurred_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label(__('admin.tenants.audit.columns.actor'))
                    ->default(__('admin.tenants.audit.empty.system'))
                    ->searchable(),
                TextColumn::make('action')
                    ->label(__('admin.tenants.audit.columns.action'))
                    ->badge(),
                TextColumn::make('description')
                    ->label(__('admin.tenants.audit.columns.description'))
                    ->wrap(),
            ])
            ->defaultSort('occurred_at', 'desc');
    }
}
