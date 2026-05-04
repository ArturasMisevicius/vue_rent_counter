<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Enums\ProjectStatus;
use App\Filament\Resources\Pages\Concerns\HasContainedSuperadminSurface;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class EditProject extends EditRecord
{
    use HasContainedSuperadminSurface;

    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()
            ->requiresConfirmation(fn (): bool => $this->shouldWarnAboutStatusOverride())
            ->modalHeading(__('admin.projects.modals.confirm_status_override'))
            ->modalDescription(fn (): ?string => $this->statusOverrideWarningMessage());
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $actor = request()->user();

        $record->update(Arr::except($data, ['status']));

        $nextStatus = array_key_exists('status', $data)
            ? ProjectStatus::from((string) $data['status'])
            : $record->status;

        if ($record instanceof Project && $record->status !== $nextStatus && $actor instanceof User) {
            app(ProjectService::class)->transitionStatus(
                $record->fresh(),
                $nextStatus,
                $actor,
                $data['cancellation_reason'] ?? null,
                $actor->isSuperadmin(),
            );
        }

        return $record->fresh();
    }

    private function shouldWarnAboutStatusOverride(): bool
    {
        return $this->statusOverrideWarningMessage() !== null;
    }

    private function statusOverrideWarningMessage(): ?string
    {
        $nextStatusValue = data_get($this->data, 'status');

        if (! is_string($nextStatusValue)) {
            return null;
        }

        $nextStatus = ProjectStatus::tryFrom($nextStatusValue);

        if (! $nextStatus instanceof ProjectStatus) {
            return null;
        }

        if ($nextStatus->isTerminal() && $nextStatus !== $this->record->status) {
            return __('admin.projects.warnings.terminal_status_override');
        }

        if (
            $nextStatus === ProjectStatus::IN_PROGRESS
            && (bool) data_get($this->data, 'requires_approval', $this->record->requires_approval)
            && $this->record->approved_at === null
        ) {
            return __('admin.projects.warnings.approval_bypass');
        }

        return null;
    }
}
