<?php

namespace App\Filament\Resources\PlatformUserResource\Pages;

use App\Filament\Resources\PlatformUserResource;
use App\Models\User;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Str;

class ViewPlatformUser extends ViewRecord
{
    protected static string $resource = PlatformUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('resetPassword')
                ->label('Reset Password')
                ->icon('heroicon-o-key')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function (User $record): void {
                    $temporaryPassword = Str::random(12);

                    $record->update([
                        'password' => bcrypt($temporaryPassword),
                    ]);

                    Notification::make()
                        ->title('Password Reset')
                        ->body('A temporary password was generated.')
                        ->success()
                        ->send();
                }),
            Actions\EditAction::make(),
        ];
    }
}
