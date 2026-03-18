<?php

declare(strict_types=1);

namespace App\Filament\Resources\Organizations\RelationManagers;

use App\Filament\Resources\Organizations\OrganizationResource;
use App\Models\AuditLog;
use App\Models\Organization;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
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
        return 'Activity Logs';
    }

    public function getRelationship(): Relation|Builder
    {
        /** @var Organization $organization */
        $organization = $this->getOwnerRecord();

        return AuditLog::query()
            ->forOrganization($organization->id)
            ->withActorSummary()
            ->recent();
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('occurred_at')
                    ->label('Occurred At')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('actor.name')
                    ->label('Actor')
                    ->default('System')
                    ->searchable(),
                TextColumn::make('action')
                    ->label('Action')
                    ->badge(),
                TextColumn::make('description')
                    ->label('Description')
                    ->wrap(),
            ])
            ->defaultSort('occurred_at', 'desc');
    }
}
