<?php

namespace App\Filament\Pages\Concerns;

use App\Filament\Actions\Admin\Settings\UpdatePasswordAction;
use App\Filament\Actions\Admin\Settings\UpdateProfileAction;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

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
        $attributes = Validator::make($this->profileForm, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->user()->id),
            ],
            'locale' => ['required', Rule::in(array_keys(config('tenanto.locales', [])))],
        ])->validate();

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
        $attributes = Validator::make($this->passwordForm, [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ])->validate();

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
