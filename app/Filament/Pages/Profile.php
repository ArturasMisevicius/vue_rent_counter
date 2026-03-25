<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Concerns\InteractsWithAccountProfileForms;
use App\Filament\Pages\Concerns\InteractsWithKycProfileForms;
use App\Filament\Pages\Concerns\RefreshesOnShellLocaleUpdate;
use App\Models\User;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class Profile extends Page
{
    use InteractsWithAccountProfileForms;
    use InteractsWithKycProfileForms;
    use RefreshesOnShellLocaleUpdate;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'profile';

    protected string $view = 'filament.pages.profile';

    public function mount(): void
    {
        $this->applyShellLocale();
        $this->fillAccountProfileForms();
        $this->fillKycProfileForm();
    }

    public function getTitle(): string
    {
        return __('shell.profile.title');
    }

    public static function canAccess(): bool
    {
        return Auth::user() instanceof User;
    }
}
