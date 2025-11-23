<?php

namespace App\Http\Controllers;

use App\Models\Language;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', 'max:5'],
        ]);

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
