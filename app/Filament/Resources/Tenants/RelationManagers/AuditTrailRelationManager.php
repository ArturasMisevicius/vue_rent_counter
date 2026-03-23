<?php

namespace App\Filament\Resources\Tenants\RelationManagers;

use App\Filament\Resources\Tenants\TenantResource;
use App\Models\OrganizationActivityLog;
use App\Models\User;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
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

    public function getRelationship(): Relation|Builder
    {
        $tenant = $this->getOwnerRecord();

        if (! $tenant instanceof User) {
            return OrganizationActivityLog::query()->whereKey(-1);
        }

        return OrganizationActivityLog::query()
            ->forOrganization($tenant->organization_id)
            ->forResource($tenant)
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
