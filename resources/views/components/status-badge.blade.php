{{--
    Status Badge Component
    
    Displays a styled badge for various status enums with consistent color coding.
    All logic is handled in the StatusBadge component class.
    
    @see \App\View\Components\StatusBadge
--}}
<span {{ $attributes->merge(['class' => "inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold border {$badgeClasses}"]) }}>
    <span class="h-2.5 w-2.5 rounded-full {{ $dotClasses }}"></span>
    <span>{{ $slot->isEmpty() ? $label : $slot }}</span>
</span>
