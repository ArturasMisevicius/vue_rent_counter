<?php

namespace App\Http\Middleware;

use App\Models\Language;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);
        app()->setLocale($locale);

        return $next($request);
    }

    protected function resolveLocale(Request $request): string
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('languages')) {
            return config('app.locale', 'en');
        }

        $sessionLocale = $request->session()->get('app_locale');
        if ($sessionLocale && $this->isActive($sessionLocale)) {
            return $sessionLocale;
        }

        $default = Language::query()->where('is_default', true)->where('is_active', true)->value('code');
        if ($default && $this->isActive($default)) {
            return $default;
        }

        return config('app.locale', 'en');
    }

    protected function isActive(string $code): bool
    {
        return Language::query()->where('code', $code)->where('is_active', true)->exists();
    }
}
