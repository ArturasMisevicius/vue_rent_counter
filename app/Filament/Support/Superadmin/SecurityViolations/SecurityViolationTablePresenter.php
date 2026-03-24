<?php

namespace App\Filament\Support\Superadmin\SecurityViolations;

use App\Models\SecurityViolation;
use Illuminate\Support\Str;

class SecurityViolationTablePresenter
{
    public static function severityColor(SecurityViolation $record): string
    {
        return match ($record->severity?->value) {
            'critical', 'high' => 'danger',
            'medium' => 'warning',
            default => 'gray',
        };
    }

    public static function sourceLabel(SecurityViolation $record): string
    {
        return (string) ($record->metadata['source']
            ?? $record->metadata['url']
            ?? __('superadmin.security_violations.presenter.unknown'));
    }

    public static function urlPath(SecurityViolation $record): string
    {
        $url = trim((string) ($record->metadata['url'] ?? ''));

        if ($url === '') {
            return __('superadmin.security_violations.placeholders.empty');
        }

        $path = parse_url($url, PHP_URL_PATH);

        if (is_string($path) && $path !== '') {
            return $path;
        }

        return Str::start($url, '/');
    }

    public static function userAgentSummary(SecurityViolation $record): string
    {
        $userAgent = trim((string) ($record->metadata['user_agent'] ?? ''));

        if ($userAgent === '') {
            return __('superadmin.security_violations.presenter.unknown');
        }

        if (str_starts_with(Str::lower($userAgent), 'curl/')) {
            return __('superadmin.security_violations.presenter.curl');
        }

        $browser = self::browserLabel($userAgent);
        $platform = self::platformLabel($userAgent);

        if ($browser === __('superadmin.security_violations.presenter.unknown_browser') && $platform === __('superadmin.security_violations.presenter.unknown_device')) {
            return Str::limit($userAgent, 48);
        }

        if ($platform === __('superadmin.security_violations.presenter.unknown_device')) {
            return $browser;
        }

        if ($browser === __('superadmin.security_violations.presenter.unknown_browser')) {
            return $platform;
        }

        return __('superadmin.security_violations.presenter.browser_on_platform', [
            'browser' => $browser,
            'platform' => $platform,
        ]);
    }

    public static function resolutionStatusLabel(SecurityViolation $record): string
    {
        return $record->resolved_at === null
            ? __('superadmin.security_violations.presenter.unresolved')
            : __('superadmin.security_violations.presenter.resolved');
    }

    public static function resolutionStatusColor(SecurityViolation $record): string
    {
        return $record->resolved_at === null
            ? 'gray'
            : 'success';
    }

    public static function blockIpTooltip(SecurityViolation $record): ?string
    {
        if (! $record->hasActiveIpBlock()) {
            return null;
        }

        $blockedUntil = $record->activeBlockedUntil();

        if ($blockedUntil === null) {
            return __('superadmin.security_violations.presenter.blocked_indefinitely');
        }

        return __('superadmin.security_violations.presenter.blocked_until', [
            'date' => $blockedUntil->format('Y-m-d H:i'),
        ]);
    }

    private static function browserLabel(string $userAgent): string
    {
        $normalizedUserAgent = Str::lower($userAgent);

        return match (true) {
            str_contains($normalizedUserAgent, 'edg/') => __('superadmin.security_violations.presenter.browsers.edge'),
            str_contains($normalizedUserAgent, 'chrome/') && ! str_contains($normalizedUserAgent, 'edg/') => __('superadmin.security_violations.presenter.browsers.chrome'),
            str_contains($normalizedUserAgent, 'firefox/') => __('superadmin.security_violations.presenter.browsers.firefox'),
            str_contains($normalizedUserAgent, 'safari/') && ! str_contains($normalizedUserAgent, 'chrome/') => __('superadmin.security_violations.presenter.browsers.safari'),
            default => __('superadmin.security_violations.presenter.unknown_browser'),
        };
    }

    private static function platformLabel(string $userAgent): string
    {
        $normalizedUserAgent = Str::lower($userAgent);

        return match (true) {
            str_contains($normalizedUserAgent, 'windows') => __('superadmin.security_violations.presenter.platforms.windows'),
            str_contains($normalizedUserAgent, 'mac os x') || str_contains($normalizedUserAgent, 'macintosh') => __('superadmin.security_violations.presenter.platforms.mac'),
            str_contains($normalizedUserAgent, 'linux') => __('superadmin.security_violations.presenter.platforms.linux'),
            str_contains($normalizedUserAgent, 'android') => __('superadmin.security_violations.presenter.platforms.android'),
            str_contains($normalizedUserAgent, 'iphone') || str_contains($normalizedUserAgent, 'ipad') || str_contains($normalizedUserAgent, 'ios') => __('superadmin.security_violations.presenter.platforms.ios'),
            default => __('superadmin.security_violations.presenter.unknown_device'),
        };
    }
}
