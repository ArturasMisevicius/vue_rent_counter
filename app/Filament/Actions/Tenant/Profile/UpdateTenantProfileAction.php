<?php

namespace App\Filament\Actions\Tenant\Profile;

use App\Filament\Actions\Preferences\UpdateUserLocaleAction;
use App\Models\User;

class UpdateTenantProfileAction
{
    public function __construct(
        protected UpdateUserLocaleAction $updateUserLocaleAction,
    ) {}

    /**
     * @param  array{name: string, email: string, locale: string}  $attributes
     */
    public function handle(User $tenant, array $attributes): User
    {
        $tenant->fill([
            'name' => $attributes['name'],
            'email' => $attributes['email'],
        ]);

        $tenant->save();

        $this->updateUserLocaleAction->handle($tenant, $attributes['locale']);

        return $tenant->refresh();
    }
}
