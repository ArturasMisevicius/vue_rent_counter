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
            ?? 'Unknown');
    }

    public static function urlPath(SecurityViolation $record): string
    {
        $url = trim((string) ($record->metadata['url'] ?? ''));

        if ($url === '') {
            return '—';
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
            return 'Unknown';
        }

        if (str_starts_with(Str::lower($userAgent), 'curl/')) {
            return 'curl';
        }

        $browser = self::browserLabel($userAgent);
        $platform = self::platformLabel($userAgent);

        if ($browser === 'Unknown browser' && $platform === 'Unknown device') {
            return Str::limit($userAgent, 48);
        }

        if ($platform === 'Unknown device') {
            return $browser;
        }

        if ($browser === 'Unknown browser') {
            return $platform;
        }

        return "{$browser} on {$platform}";
    }

    public static function resolutionStatusLabel(SecurityViolation $record): string
    {
        return $record->resolved_at === null
            ? 'Unresolved'
            : 'Resolved';
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
            return 'This IP address is already blocked indefinitely.';
        }

        return 'This IP address is already blocked until '.$blockedUntil->format('Y-m-d H:i').'.';
    }

    private static function browserLabel(string $userAgent): string
    {
        $normalizedUserAgent = Str::lower($userAgent);

        return match (true) {
            str_contains($normalizedUserAgent, 'edg/') => 'Edge',
            str_contains($normalizedUserAgent, 'chrome/') && ! str_contains($normalizedUserAgent, 'edg/') => 'Chrome',
            str_contains($normalizedUserAgent, 'firefox/') => 'Firefox',
            str_contains($normalizedUserAgent, 'safari/') && ! str_contains($normalizedUserAgent, 'chrome/') => 'Safari',
            default => 'Unknown browser',
        };
    }

    private static function platformLabel(string $userAgent): string
    {
        $normalizedUserAgent = Str::lower($userAgent);

        return match (true) {
            str_contains($normalizedUserAgent, 'windows') => 'Windows',
            str_contains($normalizedUserAgent, 'mac os x') || str_contains($normalizedUserAgent, 'macintosh') => 'Mac',
            str_contains($normalizedUserAgent, 'linux') => 'Linux',
            str_contains($normalizedUserAgent, 'android') => 'Android',
            str_contains($normalizedUserAgent, 'iphone') || str_contains($normalizedUserAgent, 'ipad') || str_contains($normalizedUserAgent, 'ios') => 'iOS',
            default => 'Unknown device',
        };
    }
}
