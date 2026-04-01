<?php

declare(strict_types=1);

namespace App\Filament\Resources\Projects\Tables;

use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Filament\Actions\Superadmin\Projects\ExportProjectsCsvAction;
use App\Filament\Resources\Projects\ProjectResource;
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
                    ->label('Project')
                    ->url(fn (Project $record): string => ProjectResource::getUrl('view', ['record' => $record]))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('reference_number')
                    ->label('Reference #')
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
                    ->label('Manager')
                    ->state(fn (Project $record): string => $record->manager?->name ?? 'Unassigned')
                    ->color(fn (Project $record): string => $record->manager === null ? 'warning' : 'primary')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('budget_amount')
                    ->money('EUR')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('actual_cost')
                    ->money('EUR')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('budget_variance')
                    ->label('Budget variance')
                    ->state(fn (Project $record): string => self::budgetVarianceLabel($record))
                    ->color(fn (Project $record): string => ($record->budgetVarianceAmount() ?? 0) > 0 ? 'danger' : 'success')
                    ->toggleable(),
                ViewColumn::make('completion_percentage')
                    ->label('Completion')
                    ->view('filament.tables.columns.project-progress-bar')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('estimated_end_date')
                    ->label('Estimated end')
                    ->date()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('schedule_variance')
                    ->label('Schedule variance')
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
                    ->label('Status')
                    ->multiple()
                    ->options(self::projectStatusOptions()),
                SelectFilter::make('priority')
                    ->label('Priority')
                    ->multiple()
                    ->options(self::projectPriorityOptions()),
                SelectFilter::make('type')
                    ->label('Type')
                    ->multiple()
                    ->options(self::projectTypeOptions()),
                SelectFilter::make('manager')
                    ->label('Manager')
                    ->options(fn (): array => User::query()
                        ->select(['id', 'name'])
                        ->orderedByName()
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->query(fn (Builder $query, array $data): Builder => $query->forManagerValue($data['value'] ?? null)),
                SelectFilter::make('building')
                    ->label('Building')
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
                    ->label('Has overdue tasks')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereHas('tasks', fn (Builder $taskQuery): Builder => $taskQuery->overdue()),
                        false: fn (Builder $query): Builder => $query->whereDoesntHave('tasks', fn (Builder $taskQuery): Builder => $taskQuery->overdue()),
                        blank: fn (Builder $query): Builder => $query,
                    ),
                TernaryFilter::make('is_over_budget')
                    ->label('Is over budget')
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
                    ->label('Is behind schedule')
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
                    ->label('Is unassigned')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereNull('manager_id'),
                        false: fn (Builder $query): Builder => $query->whereNotNull('manager_id'),
                        blank: fn (Builder $query): Builder => $query,
                    ),
                Filter::make('created_between')
                    ->label('Created date range')
                    ->schema([
                        DatePicker::make('created_from')
                            ->label('Created from'),
                        DatePicker::make('created_to')
                            ->label('Created to'),
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
                    ->label('Estimated end date range')
                    ->schema([
                        DatePicker::make('estimated_end_from')
                            ->label('Estimated end from'),
                        DatePicker::make('estimated_end_to')
                            ->label('Estimated end to'),
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
                    ->label('Needs attention')
                    ->query(fn (Builder $query): Builder => $query->needsAttention()),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('changeStatus')
                        ->label('Change status')
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
                                ->label('Acknowledge incomplete critical tasks')
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
                                        fn (int $count): string => "Skipped {$count} project(s): {$exception->getMessage()}",
                                    );
                                }
                            }

                            Notification::make()
                                ->title("Updated {$updatedCount} project(s)")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('assignManager')
                        ->label('Assign manager')
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
                                ->title("Assigned a manager to {$updatedCount} project(s)");

                            if ($skippedCount > 0) {
                                $notification
                                    ->warning()
                                    ->body("{$skippedCount} project(s) were skipped because they belong to a different organization than the first selected project.");
                            } else {
                                $notification->success();
                            }

                            $notification->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('addTag')
                        ->label('Add tag')
                        ->form([
                            TextInput::make('tag_name')
                                ->label('Tag')
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
                                ->title('Tag added to selected projects')
                                ->success()
                                ->send();
                        }),
                    BulkAction::make('exportCsv')
                        ->label('Export CSV')
                        ->action(function (Collection $records, ExportProjectsCsvAction $exportProjectsCsvAction) {
                            $path = $exportProjectsCsvAction->handle($records);

                            return response()->download($path, 'projects-export.csv')->deleteFileAfterSend(true);
                        }),
                ]),
            ])
            ->defaultSort(fn (Builder $query): Builder => $query
                ->reorder()
                ->orderByRaw("case priority when 'critical' then 1 when 'high' then 2 when 'medium' then 3 else 4 end")
                ->orderByRaw('case when estimated_end_date is null then 1 else 0 end')
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

        $formatted = '€'.number_format(abs($variance), 2);

        return match (true) {
            $variance > 0 => "{$formatted} over",
            $variance < 0 => "{$formatted} under",
            default => 'On budget',
        };
    }

    private static function scheduleVarianceLabel(Project $record): string
    {
        $variance = $record->scheduleVarianceDays();

        if ($variance === null) {
            return '—';
        }

        if ($variance > 0) {
            return "{$variance} day(s) behind";
        }

        if ($variance < 0) {
            return abs($variance).' day(s) ahead';
        }

        return 'On schedule';
    }
}
