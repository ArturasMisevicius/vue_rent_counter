<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Language;
use Illuminate\Support\Facades\Log;

/**
 * Language Observer
 *
 * Provides audit logging for all language operations.
 *
 * SECURITY FEATURES:
 * - Comprehensive audit trail for compliance
 * - Logs user ID, IP address, and changes
 * - Separate audit channel for security analysis
 * - PII-safe logging (no sensitive data in languages)
 *
 * @see \App\Models\Language
 */
class LanguageObserver
{
    /**
     * Handle the Language "created" event.
     *
     * AUDIT: Log language creation with user context.
     *
     * @param  \App\Models\Language  $language
     * @return void
     */
    public function created(Language $language): void
    {
        Log::channel('audit')->info('Language created', [
            'event' => 'language.created',
            'language_id' => $language->id,
            'code' => $language->code,
            'name' => $language->name,
            'is_default' => $language->is_default,
            'is_active' => $language->is_active,
            'user_id' => auth()->id(),
            'user_email' => auth()->user()?->email,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Handle the Language "updated" event.
     *
     * AUDIT: Log language updates with change tracking.
     *
     * @param  \App\Models\Language  $language
     * @return void
     */
    public function updated(Language $language): void
    {
        $changes = $language->getChanges();
        $original = $language->getOriginal();

        // Build change summary
        $changeSummary = [];
        foreach ($changes as $key => $newValue) {
            $changeSummary[$key] = [
                'from' => $original[$key] ?? null,
                'to' => $newValue,
            ];
        }

        Log::channel('audit')->info('Language updated', [
            'event' => 'language.updated',
            'language_id' => $language->id,
            'code' => $language->code,
            'changes' => $changeSummary,
            'user_id' => auth()->id(),
            'user_email' => auth()->user()?->email,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toIso8601String(),
        ]);

        // Special alert for default language changes
        if (isset($changes['is_default']) && $changes['is_default'] === true) {
            Log::channel('security')->warning('Default language changed', [
                'event' => 'language.default_changed',
                'language_id' => $language->id,
                'code' => $language->code,
                'previous_default' => $original['is_default'] ?? false,
                'user_id' => auth()->id(),
                'ip_address' => request()->ip(),
                'timestamp' => now()->toIso8601String(),
            ]);
        }
    }

    /**
     * Handle the Language "deleting" event.
     *
     * BUSINESS LOGIC: Prevent deletion of default or last active language.
     *
     * @param  \App\Models\Language  $language
     * @return bool|null Return false to prevent deletion
     */
    public function deleting(Language $language): ?bool
    {
        // Prevent deleting default language
        if ($language->is_default) {
            throw new \Exception(__('locales.errors.cannot_delete_default'));
        }

        // Prevent deleting last active language
        if ($language->is_active && Language::where('is_active', true)->count() === 1) {
            throw new \Exception(__('locales.errors.cannot_delete_last_active'));
        }

        return true;
    }

    /**
     * Handle the Language "deleted" event.
     *
     * AUDIT: Log language deletion with security alert.
     *
     * @param  \App\Models\Language  $language
     * @return void
     */
    public function deleted(Language $language): void
    {
        Log::channel('audit')->warning('Language deleted', [
            'event' => 'language.deleted',
            'language_id' => $language->id,
            'code' => $language->code,
            'name' => $language->name,
            'was_default' => $language->is_default,
            'was_active' => $language->is_active,
            'user_id' => auth()->id(),
            'user_email' => auth()->user()?->email,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toIso8601String(),
        ]);

        // Security alert for language deletion
        Log::channel('security')->warning('Language deleted - security event', [
            'event' => 'language.deleted.security_alert',
            'language_id' => $language->id,
            'code' => $language->code,
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Handle the Language "restored" event.
     *
     * AUDIT: Log language restoration.
     *
     * @param  \App\Models\Language  $language
     * @return void
     */
    public function restored(Language $language): void
    {
        Log::channel('audit')->info('Language restored', [
            'event' => 'language.restored',
            'language_id' => $language->id,
            'code' => $language->code,
            'user_id' => auth()->id(),
            'user_email' => auth()->user()?->email,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Handle the Language "force deleted" event.
     *
     * AUDIT: Log permanent language deletion with critical alert.
     *
     * @param  \App\Models\Language  $language
     * @return void
     */
    public function forceDeleted(Language $language): void
    {
        Log::channel('audit')->critical('Language permanently deleted', [
            'event' => 'language.force_deleted',
            'language_id' => $language->id,
            'code' => $language->code,
            'user_id' => auth()->id(),
            'user_email' => auth()->user()?->email,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toIso8601String(),
        ]);

        // Critical security alert
        Log::channel('security')->critical('Language permanently deleted - critical security event', [
            'event' => 'language.force_deleted.critical_alert',
            'language_id' => $language->id,
            'code' => $language->code,
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
