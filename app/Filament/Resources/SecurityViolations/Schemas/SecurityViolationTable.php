<?php

namespace App\Filament\Resources\SecurityViolations\Schemas;

use App\Enums\SecurityViolationSeverity;
use App\Enums\SecurityViolationType;
use App\Filament\Actions\Superadmin\Security\BlockIpAddressAction;
use App\Filament\Support\Superadmin\SecurityViolations\SecurityViolationTablePresenter;
use App\Models\SecurityViolation;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SecurityViolationTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->label('Violation Type')
                    ->state(fn (SecurityViolation $record): string => $record->type->label()),
                TextColumn::make('severity')
                    ->label('Severity')
                    ->badge()
                    ->color(fn (SecurityViolation $record): string => SecurityViolationTablePresenter::severityColor($record))
                    ->state(fn (SecurityViolation $record): string => $record->severity->label()),
                TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->placeholder('—'),
                TextColumn::make('user_summary')
                    ->label('User')
                    ->state(fn (SecurityViolation $record): string => $record->user?->name ?? 'Anonymous')
                    ->description(fn (SecurityViolation $record): ?string => $record->user?->email)
                    ->wrap(),
                TextColumn::make('url')
                    ->label('URL')
                    ->state(fn (SecurityViolation $record): string => SecurityViolationTablePresenter::urlPath($record))
                    ->wrap(),
                TextColumn::make('user_agent_summary')
                    ->label('User Agent Summary')
                    ->state(fn (SecurityViolation $record): string => SecurityViolationTablePresenter::userAgentSummary($record))
                    ->wrap(),
                TextColumn::make('occurred_at')
                    ->label('Timestamp')
                    ->state(fn (SecurityViolation $record): string => $record->occurred_at?->format('F j, Y g:i A') ?? '—')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('severity')
                    ->label('Severity')
                    ->placeholder('All')
                    ->options(SecurityViolationSeverity::options())
                    ->query(fn (Builder $query, array $data): Builder => $query->forSeverityValue($data['value'] ?? null)),
                SelectFilter::make('type')
                    ->label('Violation Type')
                    ->placeholder('All')
                    ->options(SecurityViolationType::options())
                    ->query(fn (Builder $query, array $data): Builder => $query->forTypeValue($data['value'] ?? null)),
                Filter::make('occurred_between')
                    ->label('Date Range')
                    ->schema([
                        DatePicker::make('occurred_from')
                            ->label('From'),
                        DatePicker::make('occurred_to')
                            ->label('To'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->occurredBetween(
                        $data['occurred_from'] ?? null,
                        $data['occurred_to'] ?? null,
                    )),
            ])
            ->recordActions([
                Action::make('blockIp')
                    ->label('Block IP Address')
                    ->color('danger')
                    ->authorize(fn (): bool => auth()->user()?->isSuperadmin() ?? false)
                    ->hidden(fn (SecurityViolation $record): bool => blank($record->ip_address))
                    ->requiresConfirmation()
                    ->modalHeading('Block IP Address')
                    ->modalDescription(fn (SecurityViolation $record): string => "Are you sure you want to block all access from {$record->ip_address}? This will prevent any connection from this address.")
                    ->modalSubmitActionLabel('Block IP')
                    ->modalCancelActionLabel('Cancel')
                    ->action(function (SecurityViolation $record, BlockIpAddressAction $blockIpAddressAction): void {
                        $blockIpAddressAction->handle([
                            'ip_address' => $record->ip_address,
                            'reason' => "Blocked from security violation #{$record->id}",
                            'blocked_by_user_id' => auth()->id(),
                        ]);

                        Notification::make()
                            ->title('IP address blocked')
                            ->success()
                            ->send();
                    }),
            ])
            ->deferFilters(false)
            ->filtersLayout(FiltersLayout::AboveContent)
            ->defaultSort('occurred_at', 'desc');
    }
}
