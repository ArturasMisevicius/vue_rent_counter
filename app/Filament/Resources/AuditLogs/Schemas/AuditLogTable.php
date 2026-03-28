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
                    ->label(__('superadmin.audit_logs.columns.user'))
                    ->state(fn (AuditLog $record): string => AuditLogTablePresenter::actorLabel($record))
                    ->description(fn (AuditLog $record): ?string => AuditLogTablePresenter::actorDescription($record))
                    ->wrap()
                    ->extraCellAttributes(self::expandableCellAttributes()),
                TextColumn::make('display_action')
                    ->label(__('superadmin.audit_logs.columns.action'))
                    ->state(fn (AuditLog $record): string => AuditLogTablePresenter::actionLabel($record))
                    ->badge()
                    ->color(fn (AuditLog $record): string => AuditLogTablePresenter::actionColor($record))
                    ->extraCellAttributes(self::expandableCellAttributes()),
                TextColumn::make('record_type_label')
                    ->label(__('superadmin.audit_logs.columns.record_type'))
                    ->state(fn (AuditLog $record): string => AuditLogTablePresenter::recordTypeLabel($record->subject_type))
                    ->extraCellAttributes(self::expandableCellAttributes()),
                TextColumn::make('subject_id')
                    ->label(__('superadmin.audit_logs.columns.record_id'))
                    ->placeholder(__('superadmin.audit_logs.placeholders.empty'))
                    ->extraCellAttributes(self::expandableCellAttributes()),
                TextColumn::make('ip_address')
                    ->label(__('superadmin.audit_logs.columns.ip_address'))
                    ->placeholder(__('superadmin.audit_logs.placeholders.empty'))
                    ->extraCellAttributes(self::expandableCellAttributes()),
                TextColumn::make('occurred_at')
                    ->label(__('superadmin.audit_logs.columns.timestamp'))
                    ->state(fn (AuditLog $record): string => $record->occurred_at?->locale(app()->getLocale())->isoFormat('LLL') ?? __('superadmin.audit_logs.placeholders.empty'))
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
                    ->label(__('superadmin.audit_logs.filters.user'))
                    ->schema([
                        TextInput::make('query')
                            ->label(__('superadmin.audit_logs.filters.user'))
                            ->placeholder(__('superadmin.audit_logs.filters.user_placeholder')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->whereActorMatches($data['query'] ?? null)),
                SelectFilter::make('action_type')
                    ->label(__('superadmin.audit_logs.filters.action_type'))
                    ->placeholder(__('superadmin.audit_logs.filters.all_action_types'))
                    ->options(AuditLogTablePresenter::actionTypeOptions())
                    ->query(fn (Builder $query, array $data): Builder => $query->forPresentedActionType($data['value'] ?? null)),
                SelectFilter::make('subject_type')
                    ->label(__('superadmin.audit_logs.filters.affected_record_type'))
                    ->placeholder(__('superadmin.audit_logs.filters.all_record_types'))
                    ->options(fn (): array => AuditLog::subjectTypeOptions())
                    ->query(fn (Builder $query, array $data): Builder => $query->forSubjectTypeValue($data['value'] ?? null)),
                SelectFilter::make('organization')
                    ->label(__('superadmin.audit_logs.filters.organization'))
                    ->placeholder(__('superadmin.audit_logs.filters.all_organizations'))
                    ->relationship(
                        'organization',
                        'name',
                        fn (Builder $query): Builder => $query
                            ->select(['id', 'name'])
                            ->orderBy('name')
                            ->orderBy('id'),
                    )
                    ->query(fn (Builder $query, array $data): Builder => $query->forOrganizationValue($data['value'] ?? null)),
                Filter::make('record_id')
                    ->label(__('superadmin.audit_logs.columns.record_id'))
                    ->schema([
                        TextInput::make('subject_id')
                            ->label(__('superadmin.audit_logs.columns.record_id'))
                            ->numeric(),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->forSubjectIdValue($data['subject_id'] ?? null)),
                Filter::make('occurred_between')
                    ->label(__('superadmin.audit_logs.filters.date_range'))
                    ->schema([
                        Placeholder::make('date_range_heading')
                            ->label(__('superadmin.audit_logs.filters.date_range'))
                            ->content(__('superadmin.audit_logs.filters.date_range_help')),
                        DatePicker::make('occurred_from')
                            ->label(__('superadmin.audit_logs.filters.from')),
                        DatePicker::make('occurred_to')
                            ->label(__('superadmin.audit_logs.filters.to')),
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
