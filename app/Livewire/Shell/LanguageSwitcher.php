<?php

namespace App\Livewire\Shell;

use App\Filament\Actions\Preferences\UpdateUserLocaleAction;
use App\Filament\Support\Preferences\SupportedLocaleOptions;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class LanguageSwitcher extends Component
{
    #[Locked]
    public string $currentLocale = 'en';

    public function mount(): void
    {
        $this->currentLocale = auth()->user()?->locale ?? app()->getLocale();
    }

    public function changeLocale(string $locale, UpdateUserLocaleAction $updateUserLocaleAction): void
    {
        $availableLocales = $this->locales;

        validator(
            ['locale' => $locale],
            [
                'locale' => [
                    'required',
                    'string',
                    Rule::in(array_keys($availableLocales)),
                ],
            ],
        )->validate();

        $user = auth()->user();

        if ($user === null) {
            return;
        }

        $updateUserLocaleAction->handle($user, $locale);

        $this->currentLocale = $locale;
        unset($this->currentLocaleLabel);

        $this->dispatch('shell-locale-updated');
    }

    public function render(): View
    {
        return view('livewire.shell.language-switcher', [
            'currentLocaleLabel' => $this->currentLocaleLabel,
            'locales' => $this->locales,
        ]);
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function locales(): array
    {
        return app(SupportedLocaleOptions::class)->labels();
    }

    #[Computed]
    public function currentLocaleLabel(): string
    {
        return mb_strtoupper($this->currentLocale);
    }
}
