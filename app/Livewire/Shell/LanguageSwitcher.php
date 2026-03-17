<?php

namespace App\Livewire\Shell;

use App\Actions\Preferences\UpdateUserLocaleAction;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class LanguageSwitcher extends Component
{
    public string $currentLocale;

    public function mount(): void
    {
        $this->currentLocale = auth()->user()?->locale ?? app()->getLocale();
    }

    public function switchLocale(string $locale, UpdateUserLocaleAction $updateUserLocaleAction): void
    {
        /** @var User|null $user */
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        $updateUserLocaleAction->handle($user, $locale);

        $this->currentLocale = $locale;

        $this->dispatch('shell-locale-updated', locale: $locale);
    }

    /**
     * @return array<string, array{abbreviation: string, native_name: string, flag: string}>
     */
    public function getLocalesProperty(): array
    {
        /** @var array<string, array{abbreviation: string, native_name: string, flag: string}> $locales */
        $locales = config('tenanto.locales', []);

        return $locales;
    }

    public function render(): View
    {
        return view('livewire.shell.language-switcher');
    }
}
