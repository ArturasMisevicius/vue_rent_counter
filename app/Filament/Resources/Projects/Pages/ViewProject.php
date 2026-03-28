<?php

declare(strict_types=1);

namespace App\Filament\Resources\Projects\Pages;

use App\Enums\ProjectStatus;
use App\Filament\Resources\Organizations\OrganizationResource;
use App\Filament\Resources\Pages\Concerns\HasContainedSuperadminSurface;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\User;
use App\Services\ProjectService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\ViewRecord;

class ViewProject extends ViewRecord
{
    use HasContainedSuperadminSurface;

    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('changeStatus')
                ->form([
                    Select::make('status')
                        ->options(collect($this->record->validNextStatuses())->mapWithKeys(
                            fn (ProjectStatus $status): array => [$status->value => $status->getLabel()],
                        )->all())
                        ->required(),
                    Textarea::make('reason'),
                ])
                ->action(function (array $data): void {
                    /** @var User|null $actor */
                    $actor = auth()->user();

                    if (! $actor instanceof User) {
                        return;
                    }

                    app(ProjectService::class)->transitionStatus(
                        $this->record,
                        ProjectStatus::from((string) $data['status']),
                        $actor,
                        $data['reason'] ?? null,
                        $actor->isSuperadmin(),
                    );
                }),
            Action::make('assignManager')
                ->form([
                    Select::make('manager_id')
                        ->options(fn (): array => User::query()
                            ->select(['id', 'name', 'organization_id'])
                            ->where('organization_id', $this->record->organization_id)
                            ->orderedByName()
                            ->pluck('name', 'id')
                            ->all())
                        ->searchable()
                        ->required(),
                ])
                ->action(fn (array $data) => $this->record->update(['manager_id' => (int) $data['manager_id']])),
            Action::make('approveProject')
                ->visible(fn (): bool => $this->record->status === ProjectStatus::PLANNED && $this->record->requires_approval && $this->record->approved_at === null)
                ->action(function (): void {
                    /** @var User|null $actor */
                    $actor = auth()->user();

                    if (! $actor instanceof User) {
                        return;
                    }

                    app(ProjectService::class)->approve($this->record, $actor);
                }),
            Action::make('viewOrganization')
                ->url(fn (): string => OrganizationResource::getUrl('view', ['record' => $this->record->organization_id])),
            Action::make('viewAuditLog')
                ->modalHeading('Project audit log')
                ->modalDescription('Review recent project audit activity in the dedicated audit log resource.'),
        ];
    }
}
