<?php

declare(strict_types=1);

namespace App\Filament\Resources\TenantKycProfiles\Pages;

use App\Filament\Actions\TenantKyc\ApproveTenantKycProfile;
use App\Filament\Actions\TenantKyc\AuditKycView;
use App\Filament\Actions\TenantKyc\RejectTenantKycProfile;
use App\Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\TenantKycProfiles\TenantKycProfileResource;
use App\Models\TenantKycProfile;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;

class ViewTenantKycProfile extends ViewRecord
{
    protected static string $resource = TenantKycProfileResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        if ($this->record instanceof TenantKycProfile) {
            app(AuditKycView::class)->forProfile($this->record, TenantKycProfileResource::currentUserOrFail(), 'admin_review_center');
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approveProfile')
                ->label(__('admin.tenant_kyc.actions.approve_profile'))
                ->icon('heroicon-m-check-circle')
                ->color('success')
                ->authorize(fn (): bool => TenantKycProfileResource::allows('approve', $this->record))
                ->action(function (ApproveTenantKycProfile $approveTenantKycProfile): void {
                    $approveTenantKycProfile->handle($this->record, TenantKycProfileResource::currentUserOrFail());

                    $this->refreshRecord();

                    Notification::make()
                        ->success()
                        ->title(__('admin.tenant_kyc.messages.profile_approved'))
                        ->send();
                }),
            Action::make('rejectProfile')
                ->label(__('admin.tenant_kyc.actions.reject_profile'))
                ->icon('heroicon-m-x-circle')
                ->color('danger')
                ->authorize(fn (): bool => TenantKycProfileResource::allows('reject', $this->record))
                ->schema([
                    Textarea::make('rejection_reason')
                        ->label(__('admin.tenant_kyc.fields.rejection_reason'))
                        ->required()
                        ->rows(4),
                ])
                ->action(function (array $data, RejectTenantKycProfile $rejectTenantKycProfile): void {
                    $rejectTenantKycProfile->handle($this->record, TenantKycProfileResource::currentUserOrFail(), $data);

                    $this->refreshRecord();

                    Notification::make()
                        ->success()
                        ->title(__('admin.tenant_kyc.messages.profile_rejected'))
                        ->send();
                }),
        ];
    }
}
