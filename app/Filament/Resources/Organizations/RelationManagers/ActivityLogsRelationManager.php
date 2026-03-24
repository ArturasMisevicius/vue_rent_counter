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
        return 'Activity Log';
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
                    ->label('Who Did It')
                    ->default('System')
                    ->searchable(),
                TextColumn::make('action')
                    ->label('What They Did')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => str($state)->replace('_', ' ')->title()->toString()),
                TextColumn::make('resource_label')
                    ->label('Which Record')
                    ->state(fn (OrganizationActivityLog $record): string => self::resourceLabel($record))
                    ->wrap(),
                TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->default('Unknown'),
                TextColumn::make('created_at')
                    ->label('When')
                    ->since()
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('viewChanges')
                    ->label('View Changes')
                    ->modalHeading('Change Details')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
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
        $resource = $record->resource_type !== null ? class_basename($record->resource_type) : 'Organization';

        if ($record->resource_id === null) {
            return $resource;
        }

        return "{$resource} #{$record->resource_id}";
    }
}
