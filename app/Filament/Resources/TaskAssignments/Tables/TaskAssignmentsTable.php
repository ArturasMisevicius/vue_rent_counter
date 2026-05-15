<?php

namespace App\Filament\Resources\TaskAssignments\Tables;

use App\Filament\Support\Localization\DatabaseContentLocalizer;
use App\Models\TaskAssignment;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TaskAssignmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('task.title')
                    ->label(__('superadmin.relation_resources.task_assignments.fields.task'))
                    ->state(fn (TaskAssignment $record): string => app(DatabaseContentLocalizer::class)->taskTitle($record->task?->title))
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label(__('superadmin.relation_resources.task_assignments.fields.user'))
                    ->searchable(),
                TextColumn::make('role')
                    ->label(__('superadmin.relation_resources.task_assignments.fields.role'))
                    ->state(fn (TaskAssignment $record): string => $record->roleLabel())
                    ->badge()
                    ->color(fn (TaskAssignment $record): string => $record->roleBadgeColor())
                    ->searchable(),
                TextColumn::make('assigned_at')
                    ->label(__('superadmin.relation_resources.task_assignments.fields.assigned_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('completed_at')
                    ->label(__('superadmin.relation_resources.task_assignments.fields.completed_at'))
                    ->dateTime()
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
                //
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
