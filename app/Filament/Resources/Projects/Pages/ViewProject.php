<?php

declare(strict_types=1);

namespace App\Filament\Resources\Projects\Pages;

use App\Enums\ProjectStatus;
use App\Filament\Resources\Organizations\OrganizationResource;
use App\Filament\Resources\Pages\Concerns\HasContainedSuperadminSurface;
use App\Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\Projects\ProjectResource;
use App\Filament\Support\Superadmin\Projects\ProjectOverviewData;
use App\Models\User;
use App\Services\ProjectService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;

class ViewProject extends ViewRecord
{
    use HasContainedSuperadminSurface;

    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('changeStatus')
                ->label('Change status')
                ->visible(fn (): bool => $this->availableTransitionOptions() !== [])
                ->requiresConfirmation()
                ->form([
                    Select::make('status')
                        ->options(collect($this->availableTransitionOptions())->mapWithKeys(
                            fn (ProjectStatus $status): array => [$status->value => $status->getLabel()],
                        )->all())
                        ->live()
                        ->required(),
                    Textarea::make('reason')
                        ->rows(4)
                        ->visible(fn (callable $get): bool => in_array($get('status'), [ProjectStatus::ON_HOLD->value, ProjectStatus::CANCELLED->value], true))
                        ->required(fn (callable $get): bool => in_array($get('status'), [ProjectStatus::ON_HOLD->value, ProjectStatus::CANCELLED->value], true)),
                    Toggle::make('acknowledge_incomplete_work')
                        ->label('Acknowledge incomplete critical tasks')
                        ->helperText('Required before completing a project that still has critical open tasks.')
                        ->visible(fn (callable $get): bool => $get('status') === ProjectStatus::COMPLETED->value),
                ])
                ->action(function (array $data): void {
                    $actor = request()->user();

                    if (! $actor instanceof User) {
                        return;
                    }

                    app(ProjectService::class)->transitionStatus(
                        $this->record,
                        ProjectStatus::from((string) $data['status']),
                        $actor,
                        $data['reason'] ?? null,
                        $actor->isSuperadmin(),
                        (bool) ($data['acknowledge_incomplete_work'] ?? false),
                    );

                    $this->refreshRecord();

                    Notification::make()
                        ->title('Project status updated')
                        ->success()
                        ->send();
                }),
            Action::make('updateHoldReason')
                ->label('Update hold reason')
                ->visible(fn (): bool => $this->record->status === ProjectStatus::ON_HOLD)
                ->form([
                    Textarea::make('reason')
                        ->default((string) data_get($this->record->metadata, 'on_hold_reason', ''))
                        ->rows(4)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $metadata = $this->record->metadata ?? [];
                    $metadata['on_hold_reason'] = $data['reason'];
                    $metadata['on_hold_reason_updated_at'] = now()->toDateTimeString();

                    $this->record->forceFill([
                        'metadata' => $metadata,
                    ])->save();

                    $this->refreshRecord();

                    Notification::make()
                        ->title('Hold reason updated')
                        ->success()
                        ->send();
                }),
            Action::make('assignManager')
                ->label('Assign manager')
                ->visible(fn (): bool => ! $this->record->isReadOnly())
                ->form([
                    Select::make('manager_id')
                        ->options(fn (): array => User::query()
                            ->select(['id', 'name', 'organization_id'])
                            ->where('organization_id', $this->record->organization_id)
                            ->orderedByName()
                            ->pluck('name', 'id')
                            ->all())
                        ->searchable()
                        ->placeholder('Unassigned'),
                ])
                ->action(function (array $data): void {
                    $managerId = blank($data['manager_id'] ?? null)
                        ? null
                        : (int) $data['manager_id'];

                    $this->record->update([
                        'manager_id' => $managerId,
                    ]);

                    $this->refreshRecord();

                    Notification::make()
                        ->title($managerId === null ? 'Project manager removed' : 'Project manager updated')
                        ->success()
                        ->send();
                }),
            Action::make('approveProject')
                ->label('Approve project')
                ->visible(fn (): bool => $this->record->status === ProjectStatus::PLANNED && $this->record->requires_approval && $this->record->approved_at === null)
                ->action(function (): void {
                    $actor = request()->user();

                    if (! $actor instanceof User) {
                        return;
                    }

                    app(ProjectService::class)->approve($this->record, $actor);

                    $this->refreshRecord();

                    Notification::make()
                        ->title('Project approved')
                        ->success()
                        ->send();
                }),
            Action::make('generateCostPassthrough')
                ->label('Generate cost passthrough')
                ->visible(fn (): bool => $this->record->cost_passed_to_tenant && $this->record->status === ProjectStatus::COMPLETED)
                ->requiresConfirmation()
                ->modalDescription('Generate draft invoice items for each affected tenant using the current project cost allocation.')
                ->action(function (): void {
                    $actor = request()->user();

                    if (! $actor instanceof User) {
                        return;
                    }

                    $items = app(ProjectService::class)->generateCostPassthrough($this->record, $actor);

                    $this->refreshRecord();

                    Notification::make()
                        ->title("Generated {$items->count()} passthrough invoice item(s)")
                        ->success()
                        ->send();
                }),
            Action::make('viewOrganization')
                ->label('View organization')
                ->url(fn (): string => OrganizationResource::getUrl('view', ['record' => $this->record->organization_id])),
            Action::make('viewAuditLog')
                ->label('View audit log')
                ->modalHeading('Project audit log')
                ->modalWidth(Width::Screen)
                ->modalSubmitAction(false)
                ->modalContent(fn () => view('filament.resources.projects.audit-log-modal', [
                    'entries' => app(ProjectOverviewData::class)->auditEntries($this->record),
                ])),
        ];
    }

    private function availableTransitionOptions(): array
    {
        $actor = request()->user();

        return $this->record->availableTransitionTargets($actor instanceof User && $actor->isSuperadmin());
    }
}
