<?php

declare(strict_types=1);

namespace App\View\Composers;

use Illuminate\View\View;

/**
 * Theme Composer for daisyUI theme management
 *
 * Provides theme context to all views for consistent theming
 */
class ThemeComposer
{
    /**
     * Bind data to the view.
     */
    public function compose(View $view): void
    {
        $theme = session('theme', config('app.default_theme', 'light'));
        
        $view->with([
            'currentTheme' => $theme,
            'availableThemes' => ['light', 'dark'],
        ]);
    }
}
