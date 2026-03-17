<?php

namespace App\Filament\Resources\AuditLogs\Tables;

use App\Enums\AuditLogAction;
use App\Models\AuditLog;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AuditLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('occurred_at', 'desc')
            ->columns([
                TextColumn::make('occurred_at')
                    ->label('Occurred')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('action')
                    ->label('Action')
                    ->badge()
                    ->formatStateUsing(fn (AuditLogAction $state): string => $state->label())
                    ->color(fn (AuditLogAction $state): string => match ($state) {
                        AuditLogAction::CREATED, AuditLogAction::REINSTATED, AuditLogAction::UNBLOCKED => 'success',
                        AuditLogAction::UPDATED, AuditLogAction::EXTENDED, AuditLogAction::UPGRADED, AuditLogAction::CHECKED => 'info',
                        AuditLogAction::SUSPENDED, AuditLogAction::CANCELLED, AuditLogAction::BLOCKED => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('actor.name')
                    ->label('Actor')
                    ->searchable(),
                TextColumn::make('description')
                    ->label('Description')
                    ->wrap(),
                TextColumn::make('before_state')
                    ->label('Before')
                    ->state(fn (AuditLog $record): string => self::formatMetadataState(data_get($record->metadata, 'before')))
                    ->toggleable(),
                TextColumn::make('after_state')
                    ->label('After')
                    ->state(fn (AuditLog $record): string => self::formatMetadataState(data_get($record->metadata, 'after')))
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->options(collect(AuditLogAction::cases())
                        ->mapWithKeys(fn (AuditLogAction $action): array => [$action->value => $action->label()])
                        ->all()),
                SelectFilter::make('actor')
                    ->relationship('actor', 'name', fn (Builder $query): Builder => $query
                        ->select([
                            'id',
                            'name',
                        ])),
            ]);
    }

    /**
     * @param  array<string, mixed>|null  $metadataState
     */
    private static function formatMetadataState(?array $metadataState): string
    {
        if ($metadataState === null || $metadataState === []) {
            return 'N/A';
        }

        return collect($metadataState)
            ->map(fn (mixed $value, string $key): string => "{$key}: {$value}")
            ->implode(', ');
    }
}
