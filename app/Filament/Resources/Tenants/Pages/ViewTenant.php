<?php

namespace App\Filament\Resources\Tenants\Pages;

use App\Enums\UserStatus;
use App\Filament\Actions\Admin\Properties\AssignTenantToPropertyAction;
use App\Filament\Actions\Admin\Tenants\DeleteTenantAction;
use App\Filament\Actions\Admin\Tenants\ToggleTenantStatusAction;
use App\Filament\Actions\Auth\ResendOrganizationInvitationAction;
use App\Filament\Resources\Tenants\TenantResource;
use App\Models\OrganizationInvitation;
use App\Models\Property;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewTenant extends ViewRecord
{
    protected static string $resource = TenantResource::class;

    private ?OrganizationInvitation $latestInvitation = null;

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
        return $this->record->name;
    }

    public function getSubheading(): ?string
    {
        return $this->record->email;
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
                            label: __('admin.actions.edit'),
                        ),
                    ]
                    : (
                        TenantResource::canEdit($this->record)
                            ? [
                                EditAction::make()
                                    ->label(__('admin.actions.edit')),
                            ]
                            : []
                    )
            ),
            ...(
                TenantResource::canEdit($this->record)
                    ? [
                        ...(
                            $this->canResendInvitation()
                                ? [
                                    Action::make('resendInvitation')
                                        ->label(__('admin.tenants.actions.resend_invitation'))
                                        ->action(function (ResendOrganizationInvitationAction $resendOrganizationInvitationAction): void {
                                            $actor = auth()->user();

                                            abort_if(! $actor instanceof User, 403);

                                            $invitation = $this->latestInvitation();

                                            abort_if($invitation === null, 404);

                                            $this->latestInvitation = $resendOrganizationInvitationAction->handle($actor, $invitation);

                                            Notification::make()
                                                ->success()
                                                ->title(__('admin.tenants.messages.invitation_resent'))
                                                ->send();
                                        }),
                                ]
                                : []
                        ),
                        Action::make('assignProperty')
                            ->label($this->record->currentPropertyAssignment === null
                                ? __('admin.tenants.actions.assign_to_property')
                                : __('admin.tenants.actions.reassign_property'))
                            ->slideOver()
                            ->modalDescription($this->record->currentPropertyAssignment === null
                                ? null
                                : __('admin.tenants.messages.reassign_property_warning'))
                            ->schema([
                                Select::make('property_id')
                                    ->label(__('admin.tenants.fields.property'))
                                    ->options(fn (): array => $this->availablePropertyOptions())
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->required()
                                    ->afterStateUpdated(function (mixed $state, callable $set): void {
                                        $property = $this->findProperty($state);

                                        $set('unit_area_sqm', $property?->floor_area_sqm !== null
                                            ? (float) $property->floor_area_sqm
                                            : null);
                                    }),
                                TextInput::make('unit_area_sqm')
                                    ->label(__('admin.tenants.fields.unit_area_sqm'))
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(fn (): ?float => $this->record->currentPropertyAssignment?->unit_area_sqm !== null
                                        ? (float) $this->record->currentPropertyAssignment->unit_area_sqm
                                        : ($this->record->currentProperty?->floor_area_sqm !== null
                                            ? (float) $this->record->currentProperty->floor_area_sqm
                                            : null)),
                            ])
                            ->action(function (array $data, AssignTenantToPropertyAction $assignTenantToPropertyAction): void {
                                $property = $this->findProperty($data['property_id'] ?? null);

                                abort_if($property === null, 404);

                                $assignTenantToPropertyAction->handle(
                                    $property,
                                    $this->record,
                                    isset($data['unit_area_sqm']) ? (float) $data['unit_area_sqm'] : null,
                                );

                                $this->refreshRecord();

                                Notification::make()
                                    ->success()
                                    ->title(__('admin.tenants.messages.property_reassigned'))
                                    ->send();
                            }),
                        Action::make('toggleStatus')
                            ->label($this->record->status === UserStatus::ACTIVE
                                ? __('admin.tenants.actions.deactivate')
                                : __('admin.tenants.actions.reactivate'))
                            ->color($this->record->status === UserStatus::ACTIVE ? 'warning' : 'success')
                            ->requiresConfirmation()
                            ->modalDescription($this->record->status === UserStatus::ACTIVE
                                ? __('admin.tenants.messages.deactivate_confirmation')
                                : __('admin.tenants.messages.reactivate_confirmation'))
                            ->action(function (ToggleTenantStatusAction $toggleTenantStatusAction): void {
                                $updatedTenant = $toggleTenantStatusAction->handle($this->record);
                                $this->refreshRecord();

                                Notification::make()
                                    ->success()
                                    ->title($updatedTenant->status === UserStatus::ACTIVE
                                        ? __('admin.tenants.messages.tenant_reactivated')
                                        : __('admin.tenants.messages.tenant_deactivated'))
                                    ->send();
                            }),
                        DeleteAction::make()
                            ->label(__('admin.actions.delete'))
                            ->using(fn (User $record) => app(DeleteTenantAction::class)->handle($record))
                            ->authorize(fn (User $record): bool => TenantResource::canDelete($record))
                            ->disabled(fn (User $record): bool => ! $record->canBeDeletedFromAdminWorkspace())
                            ->tooltip(fn (User $record): ?string => $record->adminDeletionBlockedReason()),
                    ]
                    : []
            ),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function availablePropertyOptions(): array
    {
        return Property::query()
            ->availableForTenantAssignment($this->record->organization_id, $this->record->id)
            ->get()
            ->mapWithKeys(fn (Property $property): array => [
                $property->id => $property->tenantAssignmentLabel(),
            ])
            ->all();
    }

    private function findProperty(mixed $propertyId): ?Property
    {
        if (blank($propertyId)) {
            return null;
        }

        return Property::query()
            ->availableForTenantAssignment($this->record->organization_id, $this->record->id)
            ->find($propertyId);
    }

    private function refreshRecord(): void
    {
        $this->record = TenantResource::getEloquentQuery()
            ->findOrFail($this->record->getKey());
    }

    private function canResendInvitation(): bool
    {
        return $this->record->status === UserStatus::INACTIVE
            && $this->latestInvitation() !== null;
    }

    private function latestInvitation(): ?OrganizationInvitation
    {
        if ($this->latestInvitation instanceof OrganizationInvitation) {
            return $this->latestInvitation;
        }

        $organizationId = $this->record->organization_id;

        if ($organizationId === null || blank($this->record->email)) {
            return null;
        }

        $this->latestInvitation = OrganizationInvitation::query()
            ->select([
                'id',
                'organization_id',
                'inviter_user_id',
                'email',
                'role',
                'full_name',
                'token',
                'expires_at',
                'accepted_at',
                'created_at',
                'updated_at',
            ])
            ->forOrganization($organizationId)
            ->where('email', $this->record->email)
            ->where('role', $this->record->role)
            ->whereNull('accepted_at')
            ->latest('id')
            ->first();

        return $this->latestInvitation;
    }
}
