<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserKycProfiles\Pages;

use App\Enums\KycVerificationStatus;
use App\Filament\Actions\Admin\Kyc\RejectKycProfileAction;
use App\Filament\Actions\Admin\Kyc\VerifyKycProfileAction;
use App\Filament\Resources\UserKycProfiles\UserKycProfileResource;
use App\Models\UserKycProfile;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewUserKycProfile extends ViewRecord
{
    protected static string $resource = UserKycProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('verify')
                ->label(__('superadmin.user_kyc_profiles.actions.verify'))
                ->color('success')
                ->visible(fn (UserKycProfile $record): bool => $record->verification_status !== KycVerificationStatus::VERIFIED)
                ->action(function (UserKycProfile $record, VerifyKycProfileAction $verifyKycProfileAction): void {
                    $verifyKycProfileAction->handle($record);

                    Notification::make()
                        ->title(__('superadmin.user_kyc_profiles.messages.verified'))
                        ->success()
                        ->send();
                }),
            Action::make('reject')
                ->label(__('superadmin.user_kyc_profiles.actions.reject'))
                ->color('danger')
                ->schema([
                    Textarea::make('rejection_reason')
                        ->label(__('superadmin.user_kyc_profiles.fields.rejection_reason'))
                        ->rows(4)
                        ->required(),
                ])
                ->action(function (UserKycProfile $record, array $data, RejectKycProfileAction $rejectKycProfileAction): void {
                    $rejectKycProfileAction->handle($record, $data);

                    Notification::make()
                        ->title(__('superadmin.user_kyc_profiles.messages.rejected'))
                        ->success()
                        ->send();
                }),
        ];
    }
}
