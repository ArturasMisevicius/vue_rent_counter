<?php

namespace App\Filament\Resources\TimeEntries\Tables;

use App\Filament\Support\Localization\DatabaseContentLocalizer;
use App\Filament\Support\Localization\LocalizedCodeLabel;
use App\Models\TimeEntry;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TimeEntriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label(__('superadmin.relation_resources.time_entries.fields.user'))
                    ->searchable(),
                TextColumn::make('task.title')
                    ->label(__('superadmin.relation_resources.time_entries.fields.task'))
                    ->state(fn (TimeEntry $record): string => app(DatabaseContentLocalizer::class)->taskTitle($record->task?->title))
                    ->searchable(),
                TextColumn::make('assignment.role')
                    ->label(__('superadmin.relation_resources.time_entries.fields.assignment'))
                    ->state(fn (TimeEntry $record): string => LocalizedCodeLabel::translate(
                        'superadmin.relation_resources.task_assignments.roles',
                        $record->assignment?->role,
                    ))
                    ->badge()
                    ->searchable(),
                TextColumn::make('hours')
                    ->label(__('superadmin.relation_resources.time_entries.fields.hours'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('logged_at')
                    ->label(__('superadmin.relation_resources.time_entries.fields.logged_at'))
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
