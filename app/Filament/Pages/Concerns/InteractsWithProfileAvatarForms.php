<?php

declare(strict_types=1);

namespace App\Filament\Pages\Concerns;

use App\Filament\Actions\Profile\UpdateProfileAvatarAction;
use App\Http\Requests\Profile\UpdateProfileAvatarRequest;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

trait InteractsWithProfileAvatarForms
{
    /**
     * @var array{avatar: string}
     */
    public array $avatarForm = [
        'avatar' => '',
    ];

    public ?string $currentAvatarUrl = null;

    abstract protected function user(): User;

    protected function fillProfileAvatarForm(): void
    {
        $this->avatarForm = [
            'avatar' => '',
        ];

        $this->currentAvatarUrl = $this->profileAvatarUrl();
    }

    public function canManageProfileAvatar(): bool
    {
        return $this->user()->isTenant();
    }

    public function saveProfileAvatar(UpdateProfileAvatarAction $updateProfileAvatarAction): void
    {
        abort_unless($this->canManageProfileAvatar(), 403);

        $this->resetValidation('avatarForm.avatar');

        $attributes = $this->validateProfileAvatarForm();

        if ($attributes === null) {
            return;
        }

        $updateProfileAvatarAction->handle($this->user(), $attributes);
        $this->fillProfileAvatarForm();
        $this->dispatch('profile-avatar-updated');

        Notification::make()
            ->success()
            ->title(__('shell.profile.avatar.messages.saved'))
            ->send();
    }

    private function profileAvatarUrl(): ?string
    {
        $user = $this->user();

        if (blank($user->avatar_path) || ! Route::has('profile.avatar.show')) {
            return null;
        }

        return route('profile.avatar.show', [
            'v' => $user->avatar_updated_at?->getTimestamp() ?? $user->updated_at?->getTimestamp(),
        ]);
    }

    /**
     * @return array{avatar: string}|null
     */
    private function validateProfileAvatarForm(): ?array
    {
        try {
            $request = new UpdateProfileAvatarRequest;

            /** @var array{avatar: string} $attributes */
            $attributes = $request->validatePayload($this->avatarForm, $this->user());

            return $attributes;
        } catch (ValidationException $exception) {
            $this->addPrefixedValidationErrors('avatarForm', $exception);

            return null;
        }
    }
}
