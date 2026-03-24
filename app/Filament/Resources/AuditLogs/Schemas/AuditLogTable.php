<?php

namespace App\Filament\Resources\AuditLogs\Schemas;

use App\Filament\Support\Superadmin\AuditLogs\AuditLogTablePresenter;
use App\Models\AuditLog;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AuditLogTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('actor_summary')
                    ->label('User')
                    ->state(fn (AuditLog $record): string => $record->actor?->name ?? 'System')
                    ->description(fn (AuditLog $record): ?string => $record->actor?->email)
                    ->wrap()
                    ->extraCellAttributes(self::expandableCellAttributes()),
                TextColumn::make('display_action')
                    ->label('Action')
                    ->state(fn (AuditLog $record): string => AuditLogTablePresenter::actionLabel($record))
                    ->badge()
                    ->color(fn (AuditLog $record): string => AuditLogTablePresenter::actionColor($record))
                    ->extraCellAttributes(self::expandableCellAttributes()),
                TextColumn::make('record_type_label')
                    ->label('Record Type')
                    ->state(fn (AuditLog $record): string => AuditLogTablePresenter::recordTypeLabel($record->subject_type))
                    ->extraCellAttributes(self::expandableCellAttributes()),
                TextColumn::make('subject_id')
                    ->label('Record ID')
                    ->placeholder('—')
                    ->extraCellAttributes(self::expandableCellAttributes()),
                TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->placeholder('—')
                    ->extraCellAttributes(self::expandableCellAttributes()),
                TextColumn::make('occurred_at')
                    ->label('Timestamp')
                    ->state(fn (AuditLog $record): string => $record->occurred_at?->format('F j, Y g:i A') ?? '—')
                    ->sortable()
                    ->extraCellAttributes(self::expandableCellAttributes()),
                Panel::make([
                    ViewColumn::make('change_panels')
                        ->view('filament.resources.audit-logs.tables.audit-log-diff-panels')
                        ->viewData(fn (AuditLog $record): array => [
                            'rows' => AuditLogTablePresenter::diffRows($record),
                        ]),
                ])->collapsed(),
            ])
            ->filters([
                Filter::make('user')
                    ->label('User')
                    ->schema([
                        TextInput::make('query')
                            ->label('User')
                            ->placeholder('Search user name or email'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->whereActorMatches($data['query'] ?? null)),
                SelectFilter::make('action_type')
                    ->label('Action Type')
                    ->placeholder('All Action Types')
                    ->options(AuditLogTablePresenter::actionTypeOptions())
                    ->query(fn (Builder $query, array $data): Builder => $query->forPresentedActionType($data['value'] ?? null)),
                SelectFilter::make('subject_type')
                    ->label('Affected Record Type')
                    ->placeholder('All Record Types')
                    ->options(fn (): array => AuditLog::subjectTypeOptions())
                    ->query(fn (Builder $query, array $data): Builder => $query->forSubjectTypeValue($data['value'] ?? null)),
                Filter::make('occurred_between')
                    ->label('Date Range')
                    ->schema([
                        Placeholder::make('date_range_heading')
                            ->label('Date Range')
                            ->content('Set an optional start and end date.'),
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
            ->recordActions([])
            ->deferFilters(false)
            ->filtersLayout(FiltersLayout::AboveContent)
            ->defaultSort('occurred_at', 'desc');
    }

    /**
     * @return array<string, string>
     */
    private static function expandableCellAttributes(): array
    {
        return [
            'class' => 'audit-log-expand-cell cursor-pointer',
            'x-on:click' => 'isCollapsed = ! isCollapsed',
        ];
    }
}
