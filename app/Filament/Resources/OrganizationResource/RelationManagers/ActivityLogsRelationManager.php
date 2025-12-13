<?php

namespace App\Filament\Resources\OrganizationResource\RelationManagers;

use BackedEnum;
use UnitEnum;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;

class ActivityLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'activityLogs';

    protected static ?string $title = null;

    protected static BackedEnum|string|null $icon = 'heroicon-o-clock';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('organizations.relations.activity_logs.time'))
                    ->dateTime('M d, H:i')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('organizations.relations.activity_logs.user'))
                    ->searchable()
                    ->default(__('superadmin.dashboard.recent_activity_widget.default_system')),
                
                Tables\Columns\TextColumn::make('action')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'info',
                        'deleted' => 'danger',
                        'suspended' => 'warning',
                        'reactivated' => 'success',
                        'impersonation_started' => 'warning',
                        'impersonation_ended' => 'info',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('resource_type')
                    ->label(__('organizations.relations.activity_logs.resource'))
                    ->formatStateUsing(fn ($state) => class_basename($state)),
                
                Tables\Columns\TextColumn::make('resource_id')
                    ->label(__('organizations.relations.activity_logs.id'))
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('ip_address')
                    ->label(__('organizations.relations.activity_logs.ip'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('action')
                    ->options([
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                        'suspended' => 'Suspended',
                        'reactivated' => 'Reactivated',
                        'impersonation_started' => 'Impersonation Started',
                        'impersonation_ended' => 'Impersonation Ended',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('view_details')
                    ->label(__('organizations.relations.activity_logs.details'))
                    ->icon('heroicon-o-eye')
                    ->modalHeading(__('organizations.relations.activity_logs.modal_heading'))
                    ->modalContent(fn ($record): \Illuminate\Contracts\View\View => view(
                        'filament.widgets.activity-details',
                        ['record' => $record]
                    ))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('subscriptions.actions.close')),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('organizations.relations.activity_logs.empty_heading'))
            ->emptyStateDescription(__('organizations.relations.activity_logs.empty_description'))
            ->emptyStateIcon('heroicon-o-clock');
    }
}
