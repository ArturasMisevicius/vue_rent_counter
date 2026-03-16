{{--
    Status Badge Component
    
    Displays a styled badge for various status enums with consistent color coding.
    Supports both BackedEnum instances and string values for maximum flexibility.
    
    Features:
    - Automatic label resolution from enum label() methods or translations
    - Cached translation lookups for performance (24-hour TTL)
    - Consistent color schemes across all status types
    - Accessible markup with ARIA attributes
    - Customizable labels via slot content
    
    Usage:
    <x-status-badge :status="$invoice->status" />
    <x-status-badge status="active" />
    <x-status-badge :status="$status">Custom Label</x-status-badge>
    
    Security Note:
    - Uses secure string concatenation (. $badgeClasses) to prevent CSS injection
    - All CSS class variables are pre-sanitized in the component class
    - Follows Laravel's Blade security best practices for dynamic class handling
    
    @see \App\View\Components\StatusBadge
    @see docs/components/STATUS_BADGE_COMPONENT.md
--}}
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold border ' . $badgeClasses]) }}>
    <span class="h-2.5 w-2.5 rounded-full {{ $dotClasses }}" aria-hidden="true"></span>
    <span>{{ $slot->isEmpty() ? $label : $slot }}</span>
</span>
