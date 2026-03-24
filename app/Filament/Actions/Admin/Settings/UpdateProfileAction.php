<?php

namespace App\Filament\Actions\Admin\Settings;

use App\Filament\Actions\Preferences\UpdateUserLocaleAction;
use App\Models\User;

class UpdateProfileAction
{
    public function __construct(
        protected UpdateUserLocaleAction $updateUserLocaleAction,
    ) {}

    /**
     * @param  array{name: string, email: string, phone: string|null, locale: string}  $attributes
     */
    public function handle(User $user, array $attributes): User
    {
        $user->fill([
            'name' => $attributes['name'],
            'email' => $attributes['email'],
            'phone' => $attributes['phone'],
        ]);

        $user->save();

        $this->updateUserLocaleAction->handle($user, $attributes['locale']);

        return $user->refresh();
    }
}
