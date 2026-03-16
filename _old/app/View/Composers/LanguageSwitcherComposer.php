<?php

declare(strict_types=1);

namespace App\View\Composers;

use App\Models\Language;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

final readonly class LanguageSwitcherComposer
{
    public function compose(View $view): void
    {
        $languages = Language::query()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get();

        $currentLocale = app()->getLocale();
        $canSwitchLocale = Route::has('language.switch');

        $view->with([
            'languages' => $languages,
            'currentLocale' => $currentLocale,
            'canSwitchLocale' => $canSwitchLocale,
        ]);
    }
}