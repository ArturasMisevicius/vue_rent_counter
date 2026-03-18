<?php

namespace App\Filament\Resources\Tenants\Pages;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Actions\Admin\Properties\AssignTenantToPropertyAction;
use App\Filament\Actions\Auth\ResendOrganizationInvitationAction;
use App\Filament\Resources\Tenants\TenantResource;
use App\Http\Requests\Admin\Tenants\ReassignTenantRequest;
use App\Models\OrganizationInvitation;
use App\Models\Property;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewTenant extends ViewRecord
{
    protected static string $resource = TenantResource::class;

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    public function getBreadcrumbs(): array
    {
        return [
            TenantResource::getUrl('index') => TenantResource::getPluralModelLabel(),
            $this->record->name,
        ];
    }

    public function getTitle(): string
    {
        return __('admin.tenants.view_title');
    }

    public function getContentTabLabel(): ?string
    {
        return __('admin.tenants.tabs.profile');
    }

    protected function getHeaderActions(): array
    {
        return [
            ...(
                TenantResource::shouldInterceptGraceEditAction()
                    ? [
                        TenantResource::makeSubscriptionInfoAction(
                            name: 'edit',
                            resource: 'tenants',
                            label: __('filament-actions::edit.single.label', [
                                'label' => TenantResource::getModelLabel(),
                            ]),
                        ),
                    ]
                    : (
                        TenantResource::canEdit($this->record)
                            ? [EditAction::make()]
                            : []
                    )
            ),
            ...(
                TenantResource::canEdit($this->record)
                    ? [
                        Action::make('reassignProperty')
                            ->label(__('admin.tenants.actions.reassign_property'))
                            ->slideOver()
                            ->modalDescription(__('admin.tenants.messages.reassign_property_warning'))
                            ->schema([
                                Select::make('property_id')
                                    ->label(__('admin.tenants.fields.property'))
                                    ->options(fn (): array => $this->availablePropertyOptions())
                                    ->searchable()
                                    ->required(),
                                TextInput::make('unit_area_sqm')
                                    ->label(__('admin.tenants.fields.unit_area_sqm'))
                                    ->numeric()
                                    ->minValue(0),
                            ])
                            ->action(function (array $data, AssignTenantToPropertyAction $assignTenantToPropertyAction): void {
                                /** @var ReassignTenantRequest $request */
                                $request = app(ReassignTenantRequest::class);
                                $validated = $request
                                    ->forOrganization($this->record->organization_id)
                                    ->validatePayload($data);

                                $property = Property::query()
                                    ->select(['id', 'organization_id', 'building_id', 'name', 'unit_number', 'type', 'floor_area_sqm'])
                                    ->where('organization_id', $this->record->organization_id)
                                    ->findOrFail($validated['property_id']);

                                $assignTenantToPropertyAction->handle(
                                    $property,
                                    $this->record,
                                    $validated['unit_area_sqm'] !== null ? (float) $validated['unit_area_sqm'] : null,
                                );

                                $this->refreshRecord();

                                Notification::make()
                                    ->title(__('admin.tenants.messages.property_reassigned'))
                                    ->success()
                                    ->send();
                            }),
                    ]
                    : []
            ),
            ...(
                TenantResource::canMutateSubscriptionScopedRecords()
                    ? [
                        Action::make('resendInvitation')
                            ->label(__('admin.tenants.actions.resend_invitation'))
                            ->visible(fn (): bool => $this->record->status === UserStatus::INACTIVE && $this->latestInvitation() !== null)
                            ->requiresConfirmation()
                            ->action(function (ResendOrganizationInvitationAction $resendOrganizationInvitationAction): void {
                                $invitation = $this->latestInvitation();

                                abort_if($invitation === null, 404);

                                $resendOrganizationInvitationAction->handle(auth()->user(), $invitation);

                                Notification::make()
                                    ->title(__('admin.tenants.messages.invitation_resent'))
                                    ->success()
                                    ->send();
                            }),
                    ]
                    : []
            ),
        ];
    }

    private function latestInvitation(): ?OrganizationInvitation
    {
        return OrganizationInvitation::query()
            ->forAcceptancePortal()
            ->forOrganization($this->record->organization_id)
            ->where('email', $this->record->email)
            ->where('role', UserRole::TENANT)
            ->latestExpiryFirst()
            ->first();
    }

    /**
     * @return array<int, string>
     */
    private function availablePropertyOptions(): array
    {
        return Property::query()
            ->select(['id', 'organization_id', 'building_id', 'name', 'unit_number'])
            ->where('organization_id', $this->record->organization_id)
            ->with(['building:id,name'])
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Property $property): array => [
                $property->id => trim($property->name.' '.$property->unit_number.' '.$property->building?->name),
            ])
            ->all();
    }

    private function refreshRecord(): void
    {
        $record = User::query()
            ->withTenantWorkspaceSummary($this->record->organization_id)
            ->findOrFail($this->record->getKey());

        $this->record = $record;
    }
}
