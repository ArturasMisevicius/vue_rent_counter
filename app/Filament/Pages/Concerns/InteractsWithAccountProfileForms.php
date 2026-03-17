<?php

namespace App\Filament\Pages\Concerns;

use App\Filament\Actions\Admin\Settings\UpdatePasswordAction;
use App\Filament\Actions\Admin\Settings\UpdateProfileAction;
use App\Http\Requests\Profile\UpdatePasswordRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Models\User;
use Filament\Notifications\Notification;

trait InteractsWithAccountProfileForms
{
    /**
     * @var array{name: string, email: string, locale: string}
     */
    public array $profileForm = [];

    /**
     * @var array{current_password: string, password: string, password_confirmation: string}
     */
    public array $passwordForm = [];

    protected function fillAccountProfileForms(): void
    {
        $user = $this->user();

        $this->profileForm = [
            'name' => $user->name,
            'email' => $user->email,
            'locale' => $user->locale,
        ];

        $this->passwordForm = [
            'current_password' => '',
            'password' => '',
            'password_confirmation' => '',
        ];
    }

    public function saveProfile(UpdateProfileAction $updateProfileAction): void
    {
        /** @var UpdateProfileRequest $request */
        $request = new UpdateProfileRequest;
        $attributes = $request->validatePayload($this->profileForm, $this->user());

        $user = $updateProfileAction->handle($this->user(), $attributes);

        $this->profileForm = [
            'name' => $user->name,
            'email' => $user->email,
            'locale' => $user->locale,
        ];

        Notification::make()
            ->success()
            ->title(__('shell.profile.messages.saved'))
            ->send();

        $this->dispatch('shell-locale-updated');
    }

    public function updatePassword(UpdatePasswordAction $updatePasswordAction): void
    {
        /** @var UpdatePasswordRequest $request */
        $request = new UpdatePasswordRequest;
        $attributes = $request->validatePayload($this->passwordForm, $this->user());

        $updatePasswordAction->handle($this->user(), $attributes['password']);

        $this->passwordForm = [
            'current_password' => '',
            'password' => '',
            'password_confirmation' => '',
        ];

        Notification::make()
            ->success()
            ->title(__('shell.profile.messages.password_updated'))
            ->send();
    }

    protected function user(): User
    {
        /** @var User $user */
        $user = auth()->user();

        return $user;
    }
}
