<?php

namespace App\Filament\Resources\PlatformUserResource\Pages;

use App\Filament\Resources\PlatformUserResource;
use App\Models\User;
use Filament\Actions;
use Illuminate\Support\Str;
use Filament\Resources\Pages\ViewRecord;

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

                    \Filament\Notifications\Notification::make()
                        ->title('Password Reset')
                        ->body('A temporary password was generated.')
                        ->success()
                        ->send();
                }),
            Actions\EditAction::make(),
        ];
    }
}
