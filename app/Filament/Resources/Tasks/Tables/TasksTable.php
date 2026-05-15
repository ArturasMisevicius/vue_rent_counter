<?php

namespace App\Filament\Resources\Tasks\Tables;

use App\Filament\Support\Localization\DatabaseContentLocalizer;
use App\Models\Organization;
use App\Models\Task;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TasksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('organization.name')->label(__('superadmin.organizations.singular'))
                    ->searchable(),
                TextColumn::make('project.name')
                    ->label(__('superadmin.relation_resources.tasks.fields.project'))
                    ->state(fn (Task $record): string => app(DatabaseContentLocalizer::class)->projectName($record->project?->name))
                    ->searchable(),
                TextColumn::make('title')
                    ->label(__('superadmin.relation_resources.tasks.fields.title'))
                    ->state(fn (Task $record): string => app(DatabaseContentLocalizer::class)->taskTitle($record->title))
                    ->searchable(),
                TextColumn::make('status')
                    ->label(__('superadmin.relation_resources.tasks.fields.status'))
                    ->state(fn (Task $record): string => $record->statusLabel())
                    ->badge()
                    ->color(fn (Task $record): string => $record->statusBadgeColor())
                    ->searchable(),
                TextColumn::make('priority')
                    ->label(__('superadmin.relation_resources.tasks.fields.priority'))
                    ->state(fn (Task $record): string => $record->priorityLabel())
                    ->badge()
                    ->color(fn (Task $record): string => $record->priorityBadgeColor())
                    ->searchable(),
                TextColumn::make('creator.name')
                    ->label(__('superadmin.relation_resources.tasks.fields.creator'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('due_date')
                    ->label(__('superadmin.relation_resources.tasks.fields.due_date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('completed_at')
                    ->label(__('superadmin.relation_resources.tasks.fields.completed_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('estimated_hours')
                    ->label(__('superadmin.relation_resources.tasks.fields.estimated_hours'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('actual_hours')
                    ->label(__('superadmin.relation_resources.tasks.fields.actual_hours'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('superadmin.relation_resources.shared.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('superadmin.relation_resources.shared.fields.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('organization')->label(__('superadmin.organizations.singular'))
                    ->options(fn (): array => Organization::query()
                        ->select(['id', 'name'])
                        ->ordered()
                        ->pluck('name', 'id')
                        ->all())
                    ->query(fn (Builder $query, array $data): Builder => $query->forOrganizationValue($data['value'] ?? null)),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
