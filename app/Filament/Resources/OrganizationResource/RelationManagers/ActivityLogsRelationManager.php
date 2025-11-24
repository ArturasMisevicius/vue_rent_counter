<?php

namespace App\Filament\Resources\OrganizationResource\RelationManagers;

use BackedEnum;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ActivityLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'activityLogs';

    protected static ?string $title = 'Activity Logs';

    protected static BackedEnum|string|null $icon = 'heroicon-o-clock';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Time')
                    ->dateTime('M d, H:i')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->default('System'),
                
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
                    ->label('Resource')
                    ->formatStateUsing(fn ($state) => class_basename($state)),
                
                Tables\Columns\TextColumn::make('resource_id')
                    ->label('ID')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP')
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
                    ->label('Details')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Activity Details')
                    ->modalContent(fn ($record): \Illuminate\Contracts\View\View => view(
                        'filament.widgets.activity-details',
                        ['record' => $record]
                    ))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No activity logs')
            ->emptyStateDescription('Activity logs will appear here')
            ->emptyStateIcon('heroicon-o-clock');
    }
}
