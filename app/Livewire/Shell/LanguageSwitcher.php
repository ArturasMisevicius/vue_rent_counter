<?php

declare(strict_types=1);

namespace App\Livewire\Shell;

use App\Filament\Actions\Preferences\ResolveGuestLocaleAction;
use App\Filament\Actions\Preferences\StoreGuestLocaleAction;
use App\Filament\Actions\Preferences\UpdateUserLocaleAction;
use App\Filament\Support\Preferences\SupportedLocaleOptions;
use App\Http\Requests\Preferences\SetLocaleRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

class LanguageSwitcher extends Component
{
    #[Locked]
    public string $currentLocale = 'en';

    public function mount(ResolveGuestLocaleAction $resolveGuestLocaleAction): void
    {
        $supportedLocaleOptions = app(SupportedLocaleOptions::class);
        $userLocale = auth()->guard()->user()?->locale;

        $guestLocale = $resolveGuestLocaleAction->sessionLocale(request());

        $this->currentLocale = is_string($userLocale)
            && in_array($userLocale, $supportedLocaleOptions->codes(), true)
            ? $userLocale
            : ($guestLocale ?? $supportedLocaleOptions->fallbackLocale());
    }

    public function changeLocale(
        string $locale,
        UpdateUserLocaleAction $updateUserLocaleAction,
        StoreGuestLocaleAction $storeGuestLocaleAction,
    ): void {
        $validated = (new SetLocaleRequest)->validatePayload([
            'locale' => $locale,
        ]);

        $selectedLocale = (string) $validated['locale'];
        $user = auth()->guard()->user();

        if ($user instanceof User) {
            $updateUserLocaleAction->handle($user, $selectedLocale);
        } else {
            $storeGuestLocaleAction->handle(request(), $selectedLocale);
        }

        $this->currentLocale = $selectedLocale;
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
