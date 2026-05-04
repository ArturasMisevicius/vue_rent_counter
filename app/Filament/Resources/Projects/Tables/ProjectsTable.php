<?php

declare(strict_types=1);

namespace App\Filament\Resources\Projects\Tables;

use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Filament\Actions\Superadmin\Projects\ExportProjectsCsvAction;
use App\Filament\Resources\Projects\ProjectResource;
use App\Filament\Support\Formatting\EuMoneyFormatter;
use App\Models\Building;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Tag;
use App\Models\User;
use App\Services\ProjectService;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Throwable;

class ProjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.projects.columns.project'))
                    ->url(fn (Project $record): string => ProjectResource::getUrl('view', ['record' => $record]))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('reference_number')
                    ->label(__('admin.projects.columns.reference_number'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('organization.name')
                    ->label(__('superadmin.organizations.singular'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('building.name')
                    ->placeholder('—')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('property.name')
                    ->placeholder('—')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable()
                    ->toggleable()
                    ->color(fn (ProjectStatus|string|null $state): string => $state instanceof ProjectStatus ? $state->badgeColor() : 'gray'),
                TextColumn::make('priority')
                    ->badge()
                    ->sortable()
                    ->toggleable()
                    ->color(fn (ProjectPriority|string|null $state): string => $state instanceof ProjectPriority ? $state->badgeColor() : 'gray'),
                TextColumn::make('type')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('manager.name')
                    ->label(__('admin.projects.columns.manager'))
                    ->state(fn (Project $record): string => $record->manager?->name ?? __('admin.projects.overview.unassigned'))
                    ->color(fn (Project $record): string => $record->manager === null ? 'warning' : 'primary')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('budget_amount')
                    ->formatStateUsing(fn (mixed $state): string => EuMoneyFormatter::format($state))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('actual_cost')
                    ->formatStateUsing(fn (mixed $state): string => EuMoneyFormatter::format($state))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('budget_variance')
                    ->label(__('admin.projects.columns.budget_variance'))
                    ->state(fn (Project $record): string => self::budgetVarianceLabel($record))
                    ->color(fn (Project $record): string => ($record->budgetVarianceAmount() ?? 0) > 0 ? 'danger' : 'success')
                    ->toggleable(),
                ViewColumn::make('completion_percentage')
                    ->label(__('admin.projects.columns.completion'))
                    ->view('filament.tables.columns.project-progress-bar')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('estimated_end_date')
                    ->label(__('admin.projects.columns.estimated_end'))
                    ->date()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('schedule_variance')
                    ->label(__('admin.projects.columns.schedule_variance'))
                    ->state(fn (Project $record): string => self::scheduleVarianceLabel($record))
                    ->color(fn (Project $record): string => ($record->scheduleVarianceDays() ?? 0) > 0 ? 'danger' : 'success')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('organization')
                    ->label(__('superadmin.organizations.singular'))
                    ->options(fn (): array => Organization::query()
                        ->select(['id', 'name'])
                        ->ordered()
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->query(fn (Builder $query, array $data): Builder => $query->forOrganizationValue($data['value'] ?? null)),
                SelectFilter::make('status')
                    ->label(__('admin.projects.filters.status'))
                    ->multiple()
                    ->options(self::projectStatusOptions()),
                SelectFilter::make('priority')
                    ->label(__('admin.projects.filters.priority'))
                    ->multiple()
                    ->options(self::projectPriorityOptions()),
                SelectFilter::make('type')
                    ->label(__('admin.projects.filters.type'))
                    ->multiple()
                    ->options(self::projectTypeOptions()),
                SelectFilter::make('manager')
                    ->label(__('admin.projects.filters.manager'))
                    ->options(fn (): array => User::query()
                        ->select(['id', 'name'])
                        ->orderedByName()
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->query(fn (Builder $query, array $data): Builder => $query->forManagerValue($data['value'] ?? null)),
                SelectFilter::make('building')
                    ->label(__('admin.projects.filters.building'))
                    ->options(fn ($livewire): array => Building::query()
                        ->select(['id', 'name', 'organization_id'])
                        ->when(
                            filled(self::selectedOrganizationFilterValue($livewire)),
                            fn (Builder $query): Builder => $query->where('organization_id', self::selectedOrganizationFilterValue($livewire)),
                        )
                        ->ordered()
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->query(fn (Builder $query, array $data): Builder => blank($data['value'] ?? null)
                        ? $query
                        : $query->where('building_id', (int) $data['value'])),
                TernaryFilter::make('has_overdue_tasks')
                    ->label(__('admin.projects.filters.has_overdue_tasks'))
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereHas('tasks', fn (Builder $taskQuery): Builder => $taskQuery->overdue()),
                        false: fn (Builder $query): Builder => $query->whereDoesntHave('tasks', fn (Builder $taskQuery): Builder => $taskQuery->overdue()),
                        blank: fn (Builder $query): Builder => $query,
                    ),
                TernaryFilter::make('is_over_budget')
                    ->label(__('admin.projects.filters.is_over_budget'))
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereColumn('actual_cost', '>', 'budget_amount'),
                        false: fn (Builder $query): Builder => $query->where(function (Builder $budgetQuery): void {
                            $budgetQuery
                                ->whereNull('budget_amount')
                                ->orWhereColumn('actual_cost', '<=', 'budget_amount');
                        }),
                        blank: fn (Builder $query): Builder => $query,
                    ),
                TernaryFilter::make('is_behind_schedule')
                    ->label(__('admin.projects.filters.is_behind_schedule'))
                    ->queries(
                        true: fn (Builder $query): Builder => $query
                            ->whereNotNull('estimated_end_date')
                            ->whereDate('estimated_end_date', '<', today())
                            ->where(function (Builder $scheduleQuery): void {
                                $scheduleQuery
                                    ->whereNull('actual_end_date')
                                    ->orWhereColumn('actual_end_date', '>', 'estimated_end_date');
                            }),
                        false: fn (Builder $query): Builder => $query->where(function (Builder $scheduleQuery): void {
                            $scheduleQuery
                                ->whereNull('estimated_end_date')
                                ->orWhere(function (Builder $onTimeQuery): void {
                                    $onTimeQuery
                                        ->whereNotNull('estimated_end_date')
                                        ->where(function (Builder $nestedQuery): void {
                                            $nestedQuery
                                                ->whereNotNull('actual_end_date')
                                                ->whereColumn('actual_end_date', '<=', 'estimated_end_date');
                                        });
                                });
                        }),
                        blank: fn (Builder $query): Builder => $query,
                    ),
                TernaryFilter::make('is_unassigned')
                    ->label(__('admin.projects.filters.is_unassigned'))
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereNull('manager_id'),
                        false: fn (Builder $query): Builder => $query->whereNotNull('manager_id'),
                        blank: fn (Builder $query): Builder => $query,
                    ),
                Filter::make('created_between')
                    ->label(__('admin.projects.filters.created_date_range'))
                    ->schema([
                        DatePicker::make('created_from')
                            ->label(__('admin.projects.filters.created_from')),
                        DatePicker::make('created_to')
                            ->label(__('admin.projects.filters.created_to')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            filled($data['created_from'] ?? null),
                            fn (Builder $query): Builder => $query->whereDate('created_at', '>=', (string) $data['created_from']),
                        )
                        ->when(
                            filled($data['created_to'] ?? null),
                            fn (Builder $query): Builder => $query->whereDate('created_at', '<=', (string) $data['created_to']),
                        )),
                Filter::make('estimated_end_between')
                    ->label(__('admin.projects.filters.estimated_end_date_range'))
                    ->schema([
                        DatePicker::make('estimated_end_from')
                            ->label(__('admin.projects.filters.estimated_end_from')),
                        DatePicker::make('estimated_end_to')
                            ->label(__('admin.projects.filters.estimated_end_to')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            filled($data['estimated_end_from'] ?? null),
                            fn (Builder $query): Builder => $query->whereDate('estimated_end_date', '>=', (string) $data['estimated_end_from']),
                        )
                        ->when(
                            filled($data['estimated_end_to'] ?? null),
                            fn (Builder $query): Builder => $query->whereDate('estimated_end_date', '<=', (string) $data['estimated_end_to']),
                        )),
                Filter::make('needs_attention')
                    ->label(__('admin.projects.filters.needs_attention'))
                    ->query(fn (Builder $query): Builder => $query->needsAttention()),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('changeStatus')
                        ->label(__('admin.projects.actions.change_status'))
                        ->form([
                            Select::make('status')
                                ->options(self::projectStatusOptions())
                                ->live()
                                ->required(),
                            TextInput::make('reason')
                                ->maxLength(255)
                                ->visible(fn (callable $get): bool => in_array($get('status'), [ProjectStatus::ON_HOLD->value, ProjectStatus::CANCELLED->value], true))
                                ->required(fn (callable $get): bool => in_array($get('status'), [ProjectStatus::ON_HOLD->value, ProjectStatus::CANCELLED->value], true)),
                            Toggle::make('acknowledge_incomplete_work')
                                ->label(__('admin.projects.fields.acknowledge_incomplete_work'))
                                ->visible(fn (callable $get): bool => $get('status') === ProjectStatus::COMPLETED->value),
                        ])
                        ->action(function (BulkAction $action, Collection $records, array $data): void {
                            $actor = request()->user();

                            if (! $actor instanceof User) {
                                return;
                            }

                            $updatedCount = 0;

                            foreach ($records as $record) {
                                try {
                                    app(ProjectService::class)->transitionStatus(
                                        $record,
                                        ProjectStatus::from((string) $data['status']),
                                        $actor,
                                        $data['reason'] ?? null,
                                        $actor->isSuperadmin(),
                                        (bool) ($data['acknowledge_incomplete_work'] ?? false),
                                    );

                                    $updatedCount++;
                                } catch (Throwable $exception) {
                                    $action->reportBulkProcessingFailure(
                                        md5($exception->getMessage()),
                                        fn (int $count): string => __('admin.projects.notifications.skipped_projects', [
                                            'count' => $count,
                                            'message' => $exception->getMessage(),
                                        ]),
                                    );
                                }
                            }

                            Notification::make()
                                ->title(__('admin.projects.notifications.updated_projects', ['count' => $updatedCount]))
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('assignManager')
                        ->label(__('admin.projects.actions.assign_manager'))
                        ->form([
                            Select::make('manager_id')
                                ->options(fn ($livewire): array => User::query()
                                    ->select(['id', 'name', 'organization_id'])
                                    ->when(
                                        filled(self::firstSelectedOrganizationId($livewire)),
                                        fn (Builder $query): Builder => $query->where('organization_id', self::firstSelectedOrganizationId($livewire)),
                                    )
                                    ->orderedByName()
                                    ->pluck('name', 'id')
                                    ->all())
                                ->searchable()
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $targetOrganizationId = $records->first()?->organization_id;

                            if ($targetOrganizationId === null) {
                                return;
                            }

                            $updatedCount = 0;
                            $skippedCount = 0;

                            foreach ($records as $record) {
                                if ($record->isReadOnly()) {
                                    $skippedCount++;

                                    continue;
                                }

                                if ($record->organization_id !== $targetOrganizationId) {
                                    $skippedCount++;

                                    continue;
                                }

                                $record->update([
                                    'manager_id' => (int) $data['manager_id'],
                                ]);

                                $updatedCount++;
                            }

                            $notification = Notification::make()
                                ->title(__('admin.projects.notifications.manager_assigned', ['count' => $updatedCount]));

                            if ($skippedCount > 0) {
                                $notification
                                    ->warning()
                                    ->body(__('admin.projects.notifications.manager_assign_skipped', ['count' => $skippedCount]));
                            } else {
                                $notification->success();
                            }

                            $notification->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('addTag')
                        ->label(__('admin.projects.actions.add_tag'))
                        ->form([
                            TextInput::make('tag_name')
                                ->label(__('admin.projects.fields.tag'))
                                ->required()
                                ->maxLength(255),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $actor = request()->user();

                            if (! $actor instanceof User) {
                                return;
                            }

                            foreach ($records as $record) {
                                $tag = Tag::query()->firstOrCreate(
                                    [
                                        'organization_id' => $record->organization_id,
                                        'name' => $data['tag_name'],
                                    ],
                                    [
                                        'description' => null,
                                        'color' => null,
                                        'type' => 'project',
                                        'is_system' => false,
                                    ],
                                );

                                $record->tags()->syncWithoutDetaching([
                                    $tag->id => [
                                        'tagged_by_user_id' => $actor->id,
                                    ],
                                ]);
                            }

                            Notification::make()
                                ->title(__('admin.projects.notifications.tag_added'))
                                ->success()
                                ->send();
                        }),
                    BulkAction::make('exportCsv')
                        ->label(__('admin.projects.actions.export_csv'))
                        ->action(function (Collection $records, ExportProjectsCsvAction $exportProjectsCsvAction) {
                            $path = $exportProjectsCsvAction->handle($records);

                            return response()->download($path, 'projects-export.csv')->deleteFileAfterSend(true);
                        }),
                ]),
            ])
            ->defaultSort(fn (Builder $query): Builder => $query
                ->reorder()
                ->orderBy('priority')
                ->orderBy('estimated_end_date')
                ->orderBy('id'));
    }

    private static function projectStatusOptions(): array
    {
        return collect(ProjectStatus::cases())->mapWithKeys(
            fn (ProjectStatus $status): array => [$status->value => $status->getLabel()],
        )->all();
    }

    private static function projectPriorityOptions(): array
    {
        return collect(ProjectPriority::cases())->mapWithKeys(
            fn (ProjectPriority $priority): array => [$priority->value => $priority->getLabel()],
        )->all();
    }

    private static function projectTypeOptions(): array
    {
        return collect(ProjectType::cases())->mapWithKeys(
            fn (ProjectType $type): array => [$type->value => $type->getLabel()],
        )->all();
    }

    private static function selectedOrganizationFilterValue(mixed $livewire): ?int
    {
        $value = data_get($livewire, 'tableFilters.organization.value');

        return filled($value) ? (int) $value : null;
    }

    private static function firstSelectedOrganizationId(mixed $livewire): ?int
    {
        $selectedRecords = method_exists($livewire, 'getSelectedTableRecords')
            ? $livewire->getSelectedTableRecords()
            : collect();

        return $selectedRecords->first()?->organization_id;
    }

    private static function budgetVarianceLabel(Project $record): string
    {
        $variance = $record->budgetVarianceAmount();

        if ($variance === null) {
            return '—';
        }

        $formatted = EuMoneyFormatter::format(abs($variance));

        return match (true) {
            $variance > 0 => __('admin.projects.overview.amount_over_budget_short', ['amount' => $formatted]),
            $variance < 0 => __('admin.projects.overview.amount_under_budget_short', ['amount' => $formatted]),
            default => __('admin.projects.overview.on_budget'),
        };
    }

    private static function scheduleVarianceLabel(Project $record): string
    {
        $variance = $record->scheduleVarianceDays();

        if ($variance === null) {
            return '—';
        }

        if ($variance > 0) {
            return __('admin.projects.overview.days_behind_short', ['count' => $variance]);
        }

        if ($variance < 0) {
            return __('admin.projects.overview.days_ahead_short', ['count' => abs($variance)]);
        }

        return __('admin.projects.overview.on_schedule');
    }
}
