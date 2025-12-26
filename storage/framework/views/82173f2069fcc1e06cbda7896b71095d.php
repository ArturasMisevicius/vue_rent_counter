<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['variant' => 'primary', 'type' => 'button']));

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

foreach (array_filter((['variant' => 'primary', 'type' => 'button']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
$classes = match($variant) {
    'primary' => 'bg-gradient-to-r from-indigo-500 to-sky-500 text-white border-transparent shadow-glow focus:ring-indigo-400 focus:ring-offset-0',
    'secondary' => 'bg-white/90 text-slate-800 border-slate-200 shadow-sm focus:ring-slate-300',
    'danger' => 'bg-rose-600 text-white border-rose-600 shadow-lg shadow-rose-200/70 focus:ring-rose-400 focus:ring-offset-0',
    default => 'bg-gradient-to-r from-indigo-500 to-sky-500 text-white border-transparent shadow-glow focus:ring-indigo-400 focus:ring-offset-0',
};
?>

<button type="<?php echo e($type); ?>" <?php echo e($attributes->merge(['class' => "inline-flex items-center justify-center gap-2 px-4 py-2.5 border rounded-xl font-semibold text-sm tracking-tight transition duration-150 focus:outline-none focus:ring-2 focus:ring-offset-2 $classes"])); ?>>
    <?php echo e($slot); ?>

</button>
<?php /**PATH C:\www\rent_counter\resources\views/components/button.blade.php ENDPATH**/ ?>