<?php

namespace App\Filament\Resources\Organizations\Pages;

use App\Enums\OrganizationStatus;
use App\Enums\UserRole;
use App\Filament\Actions\Superadmin\Organizations\ExportOrganizationDataAction;
use App\Filament\Actions\Superadmin\Organizations\ReinstateOrganizationAction;
use App\Filament\Actions\Superadmin\Organizations\SendOrganizationNotificationAction;
use App\Filament\Actions\Superadmin\Organizations\StartOrganizationImpersonationAction;
use App\Filament\Actions\Superadmin\Organizations\SuspendOrganizationAction;
use App\Filament\Resources\Organizations\OrganizationResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewOrganization extends ViewRecord
{
    protected static string $resource = OrganizationResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $relation = request()->query('relation');

        if (! is_numeric($relation)) {
            return;
        }

        $relationIndex = (int) $relation;

        if ($this->hasCombinedRelationManagerTabsWithContent()) {
            $relationIndex--;
        }

        if ($relationIndex < 0) {
            return;
        }

        $relationManagerKeys = array_values(array_keys(OrganizationResource::getRelations()));
        $relationManagerKey = $relationManagerKeys[$relationIndex] ?? null;

        if (! is_string($relationManagerKey)) {
            return;
        }

        $this->activeRelationManager = $relationManagerKey;
    }

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    public function getBreadcrumbs(): array
    {
        return [
            OrganizationResource::getUrl('index') => OrganizationResource::getPluralModelLabel(),
            $this->record->name,
        ];
    }

    public function getTitle(): string
    {
        return $this->record->name;
    }

    public function getSubheading(): ?string
    {
        return $this->record->slug;
    }

    public function getContentTabLabel(): ?string
    {
        return 'Overview';
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Edit'),
            Action::make('suspendOrganization')
                ->label('Suspend Organization')
                ->color('danger')
                ->visible(fn (): bool => $this->record->status->permitsAccess())
                ->authorize(fn (): bool => $this->authenticatedUser()?->can('suspend', $this->record) ?? false)
                ->requiresConfirmation()
                ->modalDescription(fn (): string => "Are you sure you want to suspend {$this->record->name}? This will immediately prevent all users in this organization from making any changes. Their data will be preserved.")
                ->action(function (SuspendOrganizationAction $suspendOrganizationAction): void {
                    $suspendOrganizationAction->handle($this->record);
                    $this->refreshRecord();

                    Notification::make()
                        ->title('Organization suspended')
                        ->success()
                        ->send();
                }),
            Action::make('reinstateOrganization')
                ->label('Reinstate Organization')
                ->color('success')
                ->visible(fn (): bool => $this->record->status === OrganizationStatus::SUSPENDED)
                ->authorize(fn (): bool => $this->authenticatedUser()?->can('reinstate', $this->record) ?? false)
                ->requiresConfirmation()
                ->modalDescription(fn (): string => "Reinstate {$this->record->name} and restore write access for the organization.")
                ->action(function (ReinstateOrganizationAction $reinstateOrganizationAction): void {
                    $reinstateOrganizationAction->handle($this->record);
                    $this->refreshRecord();

                    Notification::make()
                        ->title('Organization reinstated')
                        ->success()
                        ->send();
                }),
            Action::make('sendNotification')
                ->label('Send Notification')
                ->slideOver()
                ->authorize(fn (): bool => $this->authenticatedUser()?->can('update', $this->record) ?? false)
                ->schema([
                    TextInput::make('title')
                        ->label('Notification Title')
                        ->required()
                        ->maxLength(255),
                    Textarea::make('body')
                        ->label('Message Body')
                        ->required()
                        ->rows(5),
                    Select::make('severity')
                        ->label('Severity')
                        ->options([
                            'information' => 'Information',
                            'warning' => 'Warning',
                            'critical' => 'Critical',
                        ])
                        ->default('information')
                        ->required(),
                ])
                ->action(function (array $data, SendOrganizationNotificationAction $sendOrganizationNotificationAction): void {
                    $sendOrganizationNotificationAction->handle(
                        $this->record,
                        $data['title'],
                        $data['body'],
                        $data['severity'],
                    );

                    Notification::make()
                        ->title('Notification sent')
                        ->success()
                        ->send();
                }),
            Action::make('impersonateAdmin')
                ->label('Impersonate Admin')
                ->icon('heroicon-o-user-circle')
                ->visible(fn (): bool => $this->record->status->permitsAccess())
                ->authorize(fn (): bool => $this->authenticatedUser()?->can('impersonate', $this->record) ?? false)
                ->requiresConfirmation()
                ->modalDescription('You will switch into the organization primary admin account until you stop impersonating.')
                ->action(function (StartOrganizationImpersonationAction $startOrganizationImpersonationAction): void {
                    $admin = $this->resolvePrimaryAdmin();
                    $impersonator = $this->authenticatedUser();

                    abort_if($admin === null, 404, 'No primary admin is available for this organization.');
                    abort_if($impersonator === null, 403);

                    $startOrganizationImpersonationAction->handle($impersonator, $admin);

                    $this->redirect('/app');
                }),
            Action::make('exportData')
                ->label('Export Data')
                ->authorize(fn (): bool => $this->authenticatedUser()?->can('view', $this->record) ?? false)
                ->requiresConfirmation()
                ->modalDescription('This export includes all invoices as one spreadsheet, all tenants as another spreadsheet, and all meter readings as another spreadsheet, packaged into a downloadable ZIP file.')
                ->action(function (ExportOrganizationDataAction $exportOrganizationDataAction) {
                    $path = $exportOrganizationDataAction->handle($this->record);

                    return response()
                        ->download($path, "{$this->record->slug}-organization-export.zip")
                        ->deleteFileAfterSend(true);
                }),
        ];
    }

    private function refreshRecord(): void
    {
        $this->record = OrganizationResource::getEloquentQuery()->findOrFail($this->record->getKey());
    }

    private function resolvePrimaryAdmin(): ?User
    {
        $owner = $this->record->owner;

        if ($owner instanceof User && $owner->isAdmin()) {
            return $owner;
        }

        return $this->record->users()
            ->select(['id', 'organization_id', 'name', 'email', 'role', 'status', 'locale', 'password', 'remember_token'])
            ->where('role', UserRole::ADMIN)
            ->orderedByName()
            ->first();
    }

    private function authenticatedUser(): ?User
    {
        $user = auth()->guard()->user();

        return $user instanceof User ? $user : null;
    }
}
