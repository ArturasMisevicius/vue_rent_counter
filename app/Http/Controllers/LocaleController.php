<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLocaleRequest;
use App\Models\Language;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\RedirectResponse;

class LocaleController extends Controller
{
    public function store(StoreLocaleRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if (! Schema::hasTable('languages')) {
            return back();
        }

        $locale = $validated['locale'];
        $isActive = Language::query()
            ->where('code', $locale)
            ->where('is_active', true)
            ->exists();

        if ($isActive) {
            $request->session()->put('app_locale', $locale);
        }

        return back();
    }
}
