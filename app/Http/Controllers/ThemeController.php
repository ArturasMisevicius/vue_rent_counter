<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Theme Controller
 *
 * Handles theme switching for daisyUI design system
 */
class ThemeController extends Controller
{
    /**
     * Switch the user's theme preference.
     */
    public function switch(Request $request): RedirectResponse
    {
        $theme = $request->input('theme', 'light');
        
        // Validate theme
        if (! in_array($theme, ['light', 'dark'])) {
            $theme = 'light';
        }
        
        // Store in session
        session(['theme' => $theme]);
        
        // Store in user preferences if authenticated
        if ($request->user()) {
            $request->user()->update([
                'preferences->theme' => $theme,
            ]);
        }
        
        return back();
    }
}
