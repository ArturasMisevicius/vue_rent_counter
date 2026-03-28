<?php

declare(strict_types=1);

namespace App\Filament\Resources\Projects\Tables;

use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectService;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

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
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('organization.name')
                    ->label(__('superadmin.organizations.singular'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('building.name')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('property.name')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (ProjectStatus|string|null $state): string => $state instanceof ProjectStatus ? $state->badgeColor() : 'gray'),
                TextColumn::make('priority')
                    ->badge()
                    ->color(fn (ProjectPriority|string|null $state): string => $state instanceof ProjectPriority ? $state->badgeColor() : 'gray'),
                TextColumn::make('type')
                    ->badge()
                    ->toggleable(),
                TextColumn::make('manager.name')
                    ->label('Manager')
                    ->state(fn (Project $record): string => $record->manager?->name ?? 'Unassigned')
                    ->color(fn (Project $record): string => $record->manager === null ? 'warning' : 'primary')
                    ->searchable(),
                TextColumn::make('budget_amount')
                    ->money('EUR')
                    ->toggleable(),
                TextColumn::make('actual_cost')
                    ->money('EUR')
                    ->toggleable(),
                TextColumn::make('budget_variance_amount')
                    ->label('Budget variance')
                    ->state(fn (Project $record): string => number_format((float) ($record->budget_variance_amount ?? 0), 2, '.', ''))
                    ->color(fn (Project $record): string => ($record->budget_variance_amount ?? 0) > 0 ? 'danger' : 'success')
                    ->toggleable(),
                TextColumn::make('completion_percentage')
                    ->label('Completion')
                    ->suffix('%')
                    ->sortable(),
                TextColumn::make('estimated_end_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('schedule_variance_days')
                    ->label('Schedule variance')
                    ->state(fn (Project $record): string => (string) ($record->schedule_variance_days ?? 0))
                    ->color(fn (Project $record): string => ($record->schedule_variance_days ?? 0) > 0 ? 'danger' : 'success')
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
                    ->multiple()
                    ->options(collect(ProjectStatus::cases())->mapWithKeys(
                        fn (ProjectStatus $status): array => [$status->value => $status->getLabel()],
                    )->all()),
                SelectFilter::make('priority')
                    ->multiple()
                    ->options(collect(ProjectPriority::cases())->mapWithKeys(
                        fn (ProjectPriority $priority): array => [$priority->value => $priority->getLabel()],
                    )->all()),
                SelectFilter::make('type')
                    ->multiple()
                    ->options(collect(ProjectType::cases())->mapWithKeys(
                        fn (ProjectType $type): array => [$type->value => $type->getLabel()],
                    )->all()),
                SelectFilter::make('manager')
                    ->options(fn (): array => User::query()
                        ->select(['id', 'name'])
                        ->orderedByName()
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->query(fn (Builder $query, array $data): Builder => $query->forManagerValue($data['value'] ?? null)),
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
                        ->form([
                            Select::make('status')
                                ->options(collect(ProjectStatus::cases())->mapWithKeys(
                                    fn (ProjectStatus $status): array => [$status->value => $status->getLabel()],
                                )->all())
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            /** @var User|null $actor */
                            $actor = auth()->user();

                            if (! $actor instanceof User) {
                                return;
                            }

                            foreach ($records as $record) {
                                app(ProjectService::class)->transitionStatus(
                                    $record,
                                    ProjectStatus::from((string) $data['status']),
                                    $actor,
                                    'Bulk status change',
                                    $actor->isSuperadmin(),
                                );
                            }
                        }),
                    BulkAction::make('assignManager')
                        ->form([
                            Select::make('manager_id')
                                ->options(fn (): array => User::query()
                                    ->select(['id', 'name'])
                                    ->orderedByName()
                                    ->pluck('name', 'id')
                                    ->all())
                                ->searchable()
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            foreach ($records as $record) {
                                $record->update([
                                    'manager_id' => (int) $data['manager_id'],
                                ]);
                            }
                        }),
                    BulkAction::make('addTag')
                        ->action(fn (): Notification => Notification::make()->title('Add tag bulk action ready')->success()),
                    BulkAction::make('exportCsv')
                        ->action(fn (): Notification => Notification::make()->title('CSV export bulk action ready')->success()),
                ]),
            ])
            ->defaultSort('estimated_end_date');
    }
}
