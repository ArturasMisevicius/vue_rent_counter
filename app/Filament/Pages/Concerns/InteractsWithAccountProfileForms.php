<?php

namespace App\Filament\Pages\Concerns;

use App\Filament\Actions\Admin\Settings\UpdatePasswordAction;
use App\Filament\Actions\Admin\Settings\UpdateProfileAction;
use App\Filament\Actions\Preferences\UpdateUserLocaleAction;
use App\Filament\Actions\Profile\UpsertKycProfileAction;
use App\Filament\Support\Preferences\SupportedLocaleOptions;
use App\Http\Requests\Profile\UpdatePasswordRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

trait InteractsWithAccountProfileForms
{
    /**
     * @var array{name: string, email: string, phone: string|null, locale: string}
     */
    public array $profileForm = [];

    /**
     * @var array<string, string>
     */
    public array $profileLocaleOptions = [];

    /**
     * @var array{current_password: string, password: string, password_confirmation: string}
     */
    public array $passwordForm = [];

    protected function fillAccountProfileForms(): void
    {
        $user = $this->user();
        $supportedLocaleOptions = app(SupportedLocaleOptions::class);
        $this->profileLocaleOptions = $supportedLocaleOptions->labels();

        $locale = in_array($user->locale, $supportedLocaleOptions->codes(), true)
            ? $user->locale
            : $supportedLocaleOptions->fallbackLocale();

        $this->profileForm = [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'locale' => $locale,
        ];

        $this->passwordForm = [
            'current_password' => '',
            'password' => '',
            'password_confirmation' => '',
        ];
    }

    public function saveChanges(
        UpdateProfileAction $updateProfileAction,
        UpdatePasswordAction $updatePasswordAction,
        UpsertKycProfileAction $upsertKycProfileAction,
    ): void {
        $this->resetValidation();

        $attributes = $this->validateProfileForm();

        if ($attributes === null) {
            return;
        }

        $shouldUpdatePassword = collect($this->passwordForm)
            ->contains(fn (?string $value): bool => filled($value));

        $passwordAttributes = null;

        if ($shouldUpdatePassword) {
            $passwordAttributes = $this->validatePasswordForm();

            if ($passwordAttributes === null) {
                return;
            }
        }

        $user = $updateProfileAction->handle($this->user(), $attributes);

        $this->profileForm = [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'locale' => $user->locale,
        ];

        if ($shouldUpdatePassword) {
            $updatePasswordAction->handle($this->user(), $passwordAttributes['password']);

            $this->passwordForm = [
                'current_password' => '',
                'password' => '',
                'password_confirmation' => '',
            ];
        }

        if (! $this->persistKycProfile($upsertKycProfileAction)) {
            return;
        }

        Notification::make()
            ->success()
            ->title(__('shell.profile.messages.saved'))
            ->send();

        $this->dispatch('shell-locale-updated');
    }

    public function saveProfile(
        UpdateProfileAction $updateProfileAction,
        UpsertKycProfileAction $upsertKycProfileAction,
    ): void {
        $this->saveChanges($updateProfileAction, app(UpdatePasswordAction::class), $upsertKycProfileAction);
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

    public function updatedProfileFormLocale(mixed $value): void
    {
        $validated = Validator::make(
            ['locale' => $value],
            ['locale' => (new UpdateProfileRequest)->rules()['locale']],
            (new UpdateProfileRequest)->messages(),
            (new UpdateProfileRequest)->attributes(),
        )->validate();

        app(UpdateUserLocaleAction::class)->handle($this->user(), (string) $validated['locale']);
        $this->dispatch('shell-locale-updated');
    }

    public function updatedPasswordFormPassword(): void
    {
        $this->syncPasswordConfirmationError();
    }

    public function updatedPasswordFormPasswordConfirmation(): void
    {
        $this->syncPasswordConfirmationError();
    }

    protected function user(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return $user;
    }

    private function syncPasswordConfirmationError(): void
    {
        $password = (string) ($this->passwordForm['password'] ?? '');
        $passwordConfirmation = (string) ($this->passwordForm['password_confirmation'] ?? '');

        if ($password === '' || $passwordConfirmation === '') {
            $this->resetErrorBag('passwordForm.password_confirmation');

            return;
        }

        if ($password !== $passwordConfirmation) {
            $this->addError(
                'passwordForm.password_confirmation',
                __('validation.confirmed', ['attribute' => __('shell.profile.fields.password')]),
            );

            return;
        }

        $this->resetErrorBag('passwordForm.password_confirmation');
    }

    /**
     * @return array{name: string, email: string, phone: string|null, locale: string}|null
     */
    private function validateProfileForm(): ?array
    {
        try {
            /** @var UpdateProfileRequest $request */
            $request = new UpdateProfileRequest;

            /** @var array{name: string, email: string, phone: string|null, locale: string} $attributes */
            $attributes = $request->validatePayload($this->profileForm, $this->user());

            return $attributes;
        } catch (ValidationException $exception) {
            $this->addPrefixedValidationErrors('profileForm', $exception);

            return null;
        }
    }

    /**
     * @return array{current_password: string, password: string, password_confirmation: string}|null
     */
    private function validatePasswordForm(): ?array
    {
        try {
            /** @var UpdatePasswordRequest $request */
            $request = new UpdatePasswordRequest;

            /** @var array{current_password: string, password: string, password_confirmation: string} $attributes */
            $attributes = $request->validatePayload($this->passwordForm, $this->user());

            return $attributes;
        } catch (ValidationException $exception) {
            $this->addPrefixedValidationErrors('passwordForm', $exception);

            return null;
        }
    }

    private function addPrefixedValidationErrors(string $prefix, ValidationException $exception): void
    {
        foreach ($exception->errors() as $key => $messages) {
            foreach ($messages as $message) {
                $this->addError($prefix.'.'.$key, $message);
            }
        }
    }
}
