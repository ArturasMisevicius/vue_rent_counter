<?php

namespace App\Filament\Resources\Organizations\Pages;

use App\Actions\Superadmin\Organizations\ExportOrganizationDataAction;
use App\Actions\Superadmin\Organizations\ReinstateOrganizationAction;
use App\Actions\Superadmin\Organizations\SendOrganizationNotificationAction;
use App\Actions\Superadmin\Organizations\StartOrganizationImpersonationAction;
use App\Actions\Superadmin\Organizations\SuspendOrganizationAction;
use App\Enums\OrganizationStatus;
use App\Enums\PlatformNotificationSeverity;
use App\Filament\Resources\Organizations\OrganizationResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ViewRecord;

class ViewOrganization extends ViewRecord
{
    protected static string $resource = OrganizationResource::class;

    protected string $view = 'filament.resources.organizations.pages.view-organization';

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('suspend')
                ->label('Suspend')
                ->requiresConfirmation()
                ->modalHeading('Suspend organization')
                ->visible(fn (): bool => $this->getRecord()->status === OrganizationStatus::ACTIVE)
                ->action(fn () => app(SuspendOrganizationAction::class)($this->getRecord())),
            Action::make('reinstate')
                ->label('Reinstate')
                ->requiresConfirmation()
                ->modalHeading('Reinstate organization')
                ->visible(fn (): bool => $this->getRecord()->status === OrganizationStatus::SUSPENDED)
                ->action(fn () => app(ReinstateOrganizationAction::class)($this->getRecord())),
            Action::make('impersonate')
                ->label('Impersonate')
                ->action(fn () => app(StartOrganizationImpersonationAction::class)(auth()->user(), $this->getRecord())),
            Action::make('sendNotification')
                ->label('Send notification')
                ->modalHeading('Send organization notification')
                ->form([
                    TextInput::make('title')
                        ->required()
                        ->maxLength(255),
                    Textarea::make('body')
                        ->required()
                        ->rows(4),
                    Select::make('severity')
                        ->required()
                        ->options(collect(PlatformNotificationSeverity::cases())
                            ->mapWithKeys(fn (PlatformNotificationSeverity $severity): array => [$severity->value => $severity->label()])
                            ->all())
                        ->default(PlatformNotificationSeverity::INFO->value),
                ])
                ->action(fn (array $data) => app(SendOrganizationNotificationAction::class)($this->getRecord(), $data)),
            Action::make('exportData')
                ->label('Export data')
                ->action(fn () => app(ExportOrganizationDataAction::class)($this->getRecord())),
        ];
    }
}
