<?php

namespace App\Filament\Resources\Tenants\RelationManagers;

use App\Filament\Resources\Tenants\TenantResource;
use App\Filament\Support\Admin\Tenants\OrganizationActivityLogPresenter;
use App\Models\OrganizationActivityLog;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
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
                TextColumn::make('action')
                    ->label(__('admin.tenants.audit.columns.action'))
                    ->state(fn (OrganizationActivityLog $record): string => OrganizationActivityLogPresenter::actionLabel($record))
                    ->badge()
                    ->extraCellAttributes(self::expandableCellAttributes()),
                TextColumn::make('user.name')
                    ->label(__('admin.tenants.audit.columns.performed_by'))
                    ->state(fn (OrganizationActivityLog $record): string => $record->user?->name ?? __('admin.tenants.audit.empty.system'))
                    ->description(fn (OrganizationActivityLog $record): ?string => $record->user?->email)
                    ->extraCellAttributes(self::expandableCellAttributes()),
                TextColumn::make('created_at')
                    ->label(__('admin.tenants.audit.columns.date_and_time'))
                    ->state(fn (OrganizationActivityLog $record): string => $record->created_at?->format('F j, Y g:i A') ?? '—')
                    ->sortable()
                    ->extraCellAttributes(self::expandableCellAttributes()),
                Panel::make([
                    ViewColumn::make('change_panels')
                        ->view('filament.resources.audit-logs.tables.audit-log-diff-panels')
                        ->viewData(fn (OrganizationActivityLog $record): array => [
                            'rows' => OrganizationActivityLogPresenter::diffRows($record),
                        ]),
                ])->collapsed(),
            ])
            ->recordActions([])
            ->defaultSort('created_at', 'desc');
    }

    /**
     * @return array<string, string>
     */
    private static function expandableCellAttributes(): array
    {
        return [
            'class' => 'audit-log-expand-cell cursor-pointer',
            'x-on:click' => 'isCollapsed = ! isCollapsed',
        ];
    }
}
