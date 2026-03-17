<?php

namespace App\Filament\Pages;

use App\Actions\Admin\Settings\UpdatePasswordAction;
use App\Actions\Admin\Settings\UpdateProfileAction;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class Profile extends Page
{
    protected static bool $isDiscovered = false;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'profile';

    protected string $view = 'filament.pages.profile';

    /**
     * @var array{name: string, email: string, locale: string}
     */
    public array $profileForm = [];

    /**
     * @var array{current_password: string, password: string, password_confirmation: string}
     */
    public array $passwordForm = [];

    public function mount(): void
    {
        $user = $this->user();
        app()->setLocale($user->locale);

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

    public function getTitle(): string
    {
        return __('shell.profile.title');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdminLike() ?? false;
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
