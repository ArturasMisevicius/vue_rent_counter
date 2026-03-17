<?php

namespace App\Livewire\Shell;

use App\Actions\Preferences\UpdateUserLocaleAction;
use App\Support\Preferences\SupportedLocaleOptions;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;

class LanguageSwitcher extends Component
{
    public string $currentLocale = 'en';

    public function mount(): void
    {
        $this->currentLocale = auth()->user()?->locale ?? app()->getLocale();
    }

    public function changeLocale(string $locale, UpdateUserLocaleAction $updateUserLocaleAction): void
    {
        validator(
            ['locale' => $locale],
            [
                'locale' => [
                    'required',
                    'string',
                    Rule::in(array_keys($this->availableLocales())),
                ],
            ],
        )->validate();

        $user = auth()->user();

        if ($user === null) {
            return;
        }

        $updateUserLocaleAction->handle($user, $locale);

        $this->currentLocale = $locale;

        $this->dispatch('shell-locale-updated');
    }

    public function render(): View
    {
        return view('livewire.shell.language-switcher', [
            'currentLocaleLabel' => mb_strtoupper($this->currentLocale),
            'locales' => $this->availableLocales(),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function availableLocales(): array
    {
        return app(SupportedLocaleOptions::class)->labels();
    }
}
