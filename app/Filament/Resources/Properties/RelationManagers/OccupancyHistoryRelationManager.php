<?php

declare(strict_types=1);

namespace App\Filament\Resources\Properties\RelationManagers;

use App\Filament\Resources\Properties\PropertyResource;
use App\Filament\Resources\Tenants\TenantResource;
use App\Filament\Support\Formatting\LocalizedDateFormatter;
use App\Models\PropertyAssignment;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class OccupancyHistoryRelationManager extends RelationManager
{
    protected static string $relationship = 'assignments';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return PropertyResource::canView($ownerRecord);
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.properties.tabs.occupancy_history');
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->getAttribute('assignments_count');

        return (string) ($count ?? $ownerRecord->assignments()->count());
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->select([
                    'id',
                    'organization_id',
                    'property_id',
                    'tenant_user_id',
                    'status',
                    'is_primary',
                    'occupants_count',
                    'assigned_at',
                    'unassigned_at',
                    'move_out_date',
                    'billing_start_date',
                    'billing_end_date',
                    'move_out_completed_at',
                ])
                ->with(['tenant:id,organization_id,name,email'])
                ->latestAssignedFirst())
            ->columns([
                TextColumn::make('tenant.name')
                    ->label(__('admin.tenants.singular'))
                    ->url(fn (PropertyAssignment $record): ?string => $record->tenant !== null
                        ? TenantResource::getUrl('view', ['record' => $record->tenant])
                        : null)
                    ->searchable(),
                TextColumn::make('status')
                    ->label(__('admin.tenants.fields.assignment_status'))
                    ->badge(),
                IconColumn::make('is_primary')
                    ->label(__('admin.tenants.fields.primary_tenant'))
                    ->boolean(),
                TextColumn::make('occupants_count')
                    ->label(__('admin.tenants.fields.occupants_count'))
                    ->default('—'),
                TextColumn::make('assigned_at')
                    ->label(__('admin.tenants.fields.move_in_date'))
                    ->state(fn (PropertyAssignment $record): string => $record->assigned_at?->locale(app()->getLocale())->translatedFormat(LocalizedDateFormatter::dateFormat()) ?? '—'),
                TextColumn::make('move_out_date')
                    ->label(__('admin.tenants.fields.move_out_date'))
                    ->state(fn (PropertyAssignment $record): string => $record->move_out_date?->locale(app()->getLocale())->translatedFormat(LocalizedDateFormatter::dateFormat()) ?? '—'),
                TextColumn::make('unassigned_at')
                    ->label(__('admin.properties.history.unassigned_at'))
                    ->state(fn (PropertyAssignment $record): string => $record->unassigned_at?->locale(app()->getLocale())->translatedFormat(LocalizedDateFormatter::dateFormat()) ?? '—'),
            ])
            ->defaultSort('assigned_at', 'desc');
    }
}
