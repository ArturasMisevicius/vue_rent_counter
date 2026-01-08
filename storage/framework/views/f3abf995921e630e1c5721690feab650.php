<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['gap' => '6']));

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

foreach (array_filter((['gap' => '6']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $gapClass = match((string) $gap) {
        '1' => 'space-y-1',
        '2' => 'space-y-2',
        '3' => 'space-y-3',
        '4' => 'space-y-4',
        '5' => 'space-y-5',
        '6' => 'space-y-6',
        '8' => 'space-y-8',
        default => 'space-y-6',
    };
?>

<div <?php echo e($attributes->merge(['class' => $gapClass])); ?>>
    <?php echo e($slot); ?>

</div>
<?php /**PATH C:\www\rent_counter\resources\views/components/tenant/stack.blade.php ENDPATH**/ ?>