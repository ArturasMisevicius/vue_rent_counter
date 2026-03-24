<?php

declare(strict_types=1);

namespace App\Filament\Resources\Organizations\RelationManagers;

use App\Filament\Resources\Organizations\OrganizationResource;
use App\Models\Organization;
use App\Models\OrganizationActivityLog;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

class ActivityLogsRelationManager extends RelationManager
{
    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return OrganizationResource::canAccess();
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('superadmin.organizations.relations.activity_logs.title');
    }

    public function getRelationship(): Relation
    {
        /** @var Organization $organization */
        $organization = $this->getOwnerRecord();

        return $organization->activityLogs()
            ->withActorSummary()
            ->recent();
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label(__('superadmin.organizations.relations.activity_logs.columns.actor'))
                    ->default(__('superadmin.organizations.relations.activity_logs.placeholders.system'))
                    ->searchable(),
                TextColumn::make('action')
                    ->label(__('superadmin.organizations.relations.activity_logs.columns.action'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => str($state)->replace('_', ' ')->title()->toString()),
                TextColumn::make('resource_label')
                    ->label(__('superadmin.organizations.relations.activity_logs.columns.record'))
                    ->state(fn (OrganizationActivityLog $record): string => self::resourceLabel($record))
                    ->wrap(),
                TextColumn::make('ip_address')
                    ->label(__('superadmin.organizations.relations.activity_logs.columns.ip_address'))
                    ->default(__('superadmin.organizations.relations.activity_logs.placeholders.unknown')),
                TextColumn::make('created_at')
                    ->label(__('superadmin.organizations.relations.activity_logs.columns.when'))
                    ->since()
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('viewChanges')
                    ->label(__('superadmin.organizations.relations.activity_logs.actions.view_changes'))
                    ->modalHeading(__('superadmin.organizations.relations.activity_logs.modals.changes_heading'))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('superadmin.organizations.relations.activity_logs.actions.close'))
                    ->modalContent(fn (OrganizationActivityLog $record): View => view(
                        'filament.resources.organizations.activity-log-diff',
                        ['activityLog' => $record],
                    )),
            ])
            ->recordAction('viewChanges')
            ->defaultSort('created_at', 'desc');
    }

    private static function resourceLabel(OrganizationActivityLog $record): string
    {
        $resource = $record->resource_type !== null
            ? class_basename($record->resource_type)
            : __('superadmin.organizations.relations.activity_logs.placeholders.organization');

        if ($record->resource_id === null) {
            return $resource;
        }

        return "{$resource} #{$record->resource_id}";
    }
}
