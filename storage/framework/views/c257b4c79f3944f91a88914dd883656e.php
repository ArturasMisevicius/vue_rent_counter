<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['label', 'value', 'valueColor' => 'text-slate-900']));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['label', 'value', 'valueColor' => 'text-slate-900']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div <?php echo e($attributes->merge(['class' => 'relative overflow-hidden rounded-2xl border border-slate-200/80 bg-gradient-to-br from-white via-white to-indigo-50 shadow-md shadow-slate-200/60'])); ?>>
    <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-indigo-500 to-sky-400"></div>
    <div class="px-5 py-6">
        <p class="truncate text-xs font-semibold uppercase tracking-[0.16em] text-slate-500"><?php echo e($label); ?></p>
        <p class="mt-2 text-3xl font-semibold tracking-tight <?php echo e($valueColor); ?>"><?php echo e($value); ?></p>
    </div>
</div>
<?php /**PATH C:\www\rent_counter\resources\views/components/tenant/stat-card.blade.php ENDPATH**/ ?>