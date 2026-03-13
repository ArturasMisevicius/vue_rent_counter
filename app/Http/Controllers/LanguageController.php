<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\Localization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class LanguageController extends Controller
{
    /**
     * Switch the application locale.
     */
    public function switch(Request $request, string $locale): RedirectResponse
    {
        if (!Localization::isAvailable($locale)) {
            abort(404, "Locale '{$locale}' is not available.");
        }

        // Store locale in session
        $request->session()->put('locale', $locale);
        
        // Set locale for current request
        app()->setLocale($locale);

        // Update user preference if authenticated
        if ($request->user() && method_exists($request->user(), 'update')) {
            $request->user()->update(['locale' => $locale]);
        }

        // Redirect back to previous page or dashboard
        return redirect()->back()->with('success', __('common.language_switched'));
    }
}