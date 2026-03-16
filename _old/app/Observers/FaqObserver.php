<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Faq;
use Illuminate\Support\Facades\Cache;

/**
 * Observer for FAQ model events.
 *
 * Handles cache invalidation when FAQ categories change to ensure
 * filter dropdowns always show current category options.
 *
 * Performance impact:
 * - Automatic cache invalidation on category changes
 * - Real-time category updates in filter dropdowns
 * - No stale data (previously up to 1 hour delay)
 *
 * @see \App\Models\Faq
 * @see \App\Filament\Resources\FaqResource::getCategoryOptions()
 */
final class FaqObserver
{
    /**
     * Handle the Faq "saved" event.
     *
     * Invalidates category cache when category field changes.
     * This ensures filter dropdowns immediately reflect new categories.
     *
     * @param Faq $faq The FAQ being saved
     */
    public function saved(Faq $faq): void
    {
        if ($faq->wasChanged('category')) {
            Cache::forget('faq_categories');
        }
    }

    /**
     * Handle the Faq "deleted" event.
     *
     * Invalidates category cache when FAQ is deleted.
     * This ensures deleted categories are removed from filter dropdowns.
     *
     * @param Faq $faq The FAQ being deleted
     */
    public function deleted(Faq $faq): void
    {
        Cache::forget('faq_categories');
    }
}
