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
use Illuminate\Support\Str;
use Illuminate\View\Component;

/**
 * Status Badge Component
 *
 * Displays a styled badge for various status enums with consistent
 * color coding and visual indicators.
 *
 * Supports: InvoiceStatus, SubscriptionStatus, UserRole, MeterType,
 * PropertyType, ServiceType, SubscriptionPlanType, UserAssignmentAction
 *
 * @example
 * <x-status-badge :status="$invoice->status" />
 * <x-status-badge :status="'active'" />
 */
final class StatusBadge extends Component
{
    /**
     * Status color mappings for consistent visual representation.
     */
    private const STATUS_COLORS = [
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
    ];

    /**
     * Default color scheme for unknown statuses.
     */
    private const DEFAULT_COLORS = [
        'badge' => 'bg-slate-100 text-slate-700 border-slate-200',
        'dot' => 'bg-slate-400',
    ];

    public readonly string $statusValue;
    public readonly string $label;
    public readonly string $badgeClasses;
    public readonly string $dotClasses;

    /**
     * Create a new component instance.
     *
     * @param BackedEnum|string $status The status to display (enum or string)
     * @param string $slot Optional slot content for custom label
     */
    public function __construct(
        BackedEnum|string $status,
        public readonly string $slot = ''
    ) {
        $this->statusValue = $this->normalizeStatus($status);
        $this->label = $this->resolveLabel($status);
        
        $colors = $this->resolveColors($this->statusValue);
        $this->badgeClasses = $colors['badge'];
        $this->dotClasses = $colors['dot'];
    }

    /**
     * Normalize status to string value.
     */
    private function normalizeStatus(BackedEnum|string $status): string
    {
        return $status instanceof BackedEnum ? $status->value : (string) $status;
    }

    /**
     * Resolve the display label for the status.
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
     * @return array<string, string>
     */
    private function getMergedTranslations(): array
    {
        static $cachedTranslations = null;

        if ($cachedTranslations === null) {
            $cachedTranslations = array_merge(
                InvoiceStatus::labels(),
                ServiceType::labels(),
                UserRole::labels(),
                MeterType::labels(),
                PropertyType::labels(),
                SubscriptionStatus::labels(),
                SubscriptionPlanType::labels(),
                UserAssignmentAction::labels(),
            );
        }

        return $cachedTranslations;
    }

    /**
     * Resolve color classes for the status.
     *
     * @return array{badge: string, dot: string}
     */
    private function resolveColors(string $statusValue): array
    {
        return self::STATUS_COLORS[$statusValue] ?? self::DEFAULT_COLORS;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('components.status-badge');
    }
}
