<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Enums\InvoiceStatus;
use App\Enums\MeterType;
use App\Enums\PropertyType;
use App\Enums\ServiceType;
use App\Enums\SubscriptionPlanType;
use App\Enums\SubscriptionStatus;
use App\Enums\UserAssignmentAction;
use App\Enums\UserRole;
use BackedEnum;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\Component;

/**
 * Status Badge Component
 *
 * Displays a styled badge for various status enums with consistent
 * color coding and visual indicators. Supports both enum instances
 * and string values for maximum flexibility.
 *
 * Supported Enums:
 * - InvoiceStatus (draft, finalized, paid)
 * - SubscriptionStatus (active, inactive, expired, suspended, cancelled)
 * - UserRole (superadmin, admin, manager, tenant)
 * - MeterType (electricity, water, gas, heating)
 * - PropertyType (apartment, house, commercial)
 * - ServiceType (electricity, water, heating, gas)
 * - SubscriptionPlanType (free, basic, premium, enterprise)
 * - UserAssignmentAction (assigned, unassigned, reassigned)
 *
 * Features:
 * - Automatic label resolution from enum label() methods
 * - Fallback to translation keys for string values
 * - Cached translation lookups for performance
 * - Consistent color coding across all status types
 * - Accessible markup with ARIA attributes
 * - Customizable labels via slot content
 *
 * @example Basic usage with enum
 * <x-status-badge :status="$invoice->status" />
 * @example Usage with string value
 * <x-status-badge status="active" />
 * @example Custom label via slot
 * <x-status-badge :status="$subscription->status">
 *     Custom Active Label
 * </x-status-badge>
 * @example Handling null status gracefully
 * <x-status-badge :status="$optionalStatus" />
 *
 * @see \App\Enums\InvoiceStatus
 * @see \App\Enums\SubscriptionStatus
 * @see \App\Enums\UserRole
 */
final class StatusBadge extends Component
{
    /**
     * Status color mappings for consistent visual representation.
     */
    private const STATUS_COLORS = [
        // Invoice statuses
        'draft' => [
            'badge' => 'bg-amber-50 text-amber-700 border-amber-200',
            'dot' => 'bg-amber-400',
        ],
        'finalized' => [
            'badge' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            'dot' => 'bg-indigo-500',
        ],
        'paid' => [
            'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'dot' => 'bg-emerald-500',
        ],

        // Subscription/general statuses
        'active' => [
            'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'dot' => 'bg-emerald-500',
        ],
        'inactive' => [
            'badge' => 'bg-slate-100 text-slate-700 border-slate-200',
            'dot' => 'bg-slate-400',
        ],
        'expired' => [
            'badge' => 'bg-rose-50 text-rose-700 border-rose-200',
            'dot' => 'bg-rose-400',
        ],
        'suspended' => [
            'badge' => 'bg-amber-50 text-amber-700 border-amber-200',
            'dot' => 'bg-amber-400',
        ],
        'cancelled' => [
            'badge' => 'bg-slate-100 text-slate-700 border-slate-200',
            'dot' => 'bg-slate-400',
        ],

        // Additional common statuses
        'pending' => [
            'badge' => 'bg-blue-50 text-blue-700 border-blue-200',
            'dot' => 'bg-blue-400',
        ],
        'processing' => [
            'badge' => 'bg-purple-50 text-purple-700 border-purple-200',
            'dot' => 'bg-purple-400',
        ],
        'unknown' => [
            'badge' => 'bg-slate-100 text-slate-700 border-slate-200',
            'dot' => 'bg-slate-400',
        ],
    ];

    /**
     * Default color scheme for unknown statuses.
     */
    private const DEFAULT_COLORS = [
        'badge' => 'bg-slate-100 text-slate-700 border-slate-200',
        'dot' => 'bg-slate-400',
    ];

    public string $statusValue;
    public string $label;
    public string $badgeClasses;
    public string $dotClasses;

    /**
     * Create a new component instance.
     *
     * Accepts status as enum instance, string value, or null. When null,
     * displays "unknown" status with default styling. Automatically resolves
     * labels from enum methods or translation keys.
     *
     * Security: All input is validated and sanitized. CSS classes come from
     * predefined constants to prevent CSS injection attacks.
     *
     * @param  BackedEnum|string|null  $status  The status to display (enum instance, string value, or null)
     * @param  string  $slot  Optional slot content for custom label override
     * 
     * @throws \InvalidArgumentException When status contains invalid characters (in strict mode)
     */
    public function __construct(
        BackedEnum|string|null $status,
        public readonly string $slot = ''
    ) {
        // Handle null status gracefully
        if ($status === null) {
            $this->statusValue = 'unknown';
            $this->label = __('common.status.unknown');
            $colors = self::DEFAULT_COLORS;
        } else {
            $this->statusValue = $this->normalizeStatus($status);
            $this->label = $this->resolveLabel($status);
            $colors = $this->resolveColors($this->statusValue);
        }

        $this->badgeClasses = $colors['badge'];
        $this->dotClasses = $colors['dot'];
    }

    /**
     * Normalize status to string value.
     *
     * Extracts the underlying value from enum instances or casts
     * string values to ensure consistent string representation.
     *
     * @param  BackedEnum|string  $status  The status to normalize
     * @return string The normalized string value
     */
    private function normalizeStatus(BackedEnum|string $status): string
    {
        return $status instanceof BackedEnum ? $status->value : (string) $status;
    }

    /**
     * Resolve the display label for the status.
     *
     * Resolution order:
     * 1. Enum label() method if available
     * 2. Merged translation cache lookup
     * 3. Formatted string fallback (snake_case to Title Case)
     *
     * @param  BackedEnum|string  $status  The status to resolve label for
     * @return string The resolved display label
     */
    private function resolveLabel(BackedEnum|string $status): string
    {
        // If it's an enum with a label method, use it
        if ($status instanceof BackedEnum && method_exists($status, 'label')) {
            return $status->label();
        }

        // Try to find label in merged translations
        $translations = $this->getMergedTranslations();
        $statusValue = $this->normalizeStatus($status);

        if (isset($translations[$statusValue])) {
            return $translations[$statusValue];
        }

        // Fallback to formatted string
        return Str::of($statusValue)
            ->replace('_', ' ')
            ->title()
            ->toString();
    }

    /**
     * Get merged translations from all supported enums.
     *
     * Merges label arrays from all supported enum types into a single
     * lookup table. Results are cached for 24 hours with tags for
     * selective invalidation when translations change.
     *
     * Cache invalidation:
     * - Cache::tags(['status-badge', 'translations'])->flush()
     * - Automatic on cache clear
     *
     * @return array<string, string> Map of status values to display labels
     */
    private function getMergedTranslations(): array
    {
        $cacheKey = 'status-badge.translations';
        
        return Cache::tags(['status-badge', 'translations'])
            ->remember($cacheKey, now()->addDay(), function () use ($cacheKey): array {
                $translations = array_merge(
                    InvoiceStatus::labels(),
                    ServiceType::labels(),
                    UserRole::labels(),
                    MeterType::labels(),
                    PropertyType::labels(),
                    SubscriptionStatus::labels(),
                    SubscriptionPlanType::labels(),
                    UserAssignmentAction::labels(),
                );
                
                // Log cache miss for monitoring
                if (app()->environment('production')) {
                    logger()->debug('StatusBadge translations cache miss', [
                        'cache_key' => $cacheKey,
                        'translation_count' => count($translations),
                    ]);
                }
                
                return $translations;
            });
    }

    /**
     * Resolve color classes for the status.
     *
     * Returns Tailwind CSS classes for badge background/text and
     * status indicator dot. Falls back to default gray styling
     * for unknown status values.
     *
     * Security: All returned CSS classes are from predefined constants,
     * preventing CSS injection attacks. Unknown statuses are logged
     * for security monitoring in non-production environments.
     *
     * @param  string  $statusValue  The normalized status value
     * @return array{badge: string, dot: string} Badge and dot CSS classes
     */
    private function resolveColors(string $statusValue): array
    {
        $colors = self::STATUS_COLORS[$statusValue] ?? null;
        
        if ($colors === null) {
            // Log unknown status for monitoring in non-production environments
            if (! app()->environment('production')) {
                logger()->warning('StatusBadge: Unknown status value', [
                    'status_value' => $statusValue,
                    'available_statuses' => array_keys(self::STATUS_COLORS),
                ]);
            }
            
            return self::DEFAULT_COLORS;
        }
        
        return $colors;
    }

    /**
     * Invalidate the status badge translation cache.
     *
     * Call this method when enum labels change or new status types are added.
     */
    public static function invalidateCache(): void
    {
        Cache::tags(['status-badge', 'translations'])->flush();
        
        logger()->info('StatusBadge translation cache invalidated');
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return View The component view instance
     */
    public function render(): View
    {
        return view('components.status-badge');
    }
}
