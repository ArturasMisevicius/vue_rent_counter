<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'label',
    'value',
    'hint' => null,
    'icon' => null,
    'tone' => 'slate',
]));

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

foreach (array_filter(([
    'label',
    'value',
    'hint' => null,
    'icon' => null,
    'tone' => 'slate',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
$toneStyles = [
    'indigo' => ['bg' => 'bg-indigo-50', 'text' => 'text-indigo-700', 'border' => 'border-indigo-100'],
    'emerald' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'border' => 'border-emerald-100'],
    'amber' => ['bg' => 'bg-amber-50', 'text' => 'text-amber-700', 'border' => 'border-amber-100'],
    'slate' => ['bg' => 'bg-slate-50', 'text' => 'text-slate-700', 'border' => 'border-slate-100'],
];

$tone = $toneStyles[$tone] ?? $toneStyles['slate'];
?>

<div class="relative overflow-hidden rounded-2xl border <?php echo e($tone['border']); ?> bg-white p-4 shadow-sm shadow-slate-200/60 transition hover:-translate-y-0.5 hover:shadow-md">
    <div class="absolute inset-x-0 bottom-0 h-1 bg-gradient-to-r from-indigo-500/20 via-sky-500/20 to-emerald-400/20"></div>
    <div class="flex items-start justify-between gap-3">
        <div class="flex items-start gap-3">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($icon): ?>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl <?php echo e($tone['bg']); ?> <?php echo e($tone['text']); ?> ring-1 ring-inset <?php echo e($tone['border']); ?>">
                    <?php echo $icon; ?>

                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500"><?php echo e($label); ?></p>
                <p class="mt-1 text-2xl font-semibold text-slate-900"><?php echo e($value); ?></p>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hint): ?>
                    <p class="mt-1 text-xs text-slate-500"><?php echo e($hint); ?></p>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>
        <div class="rounded-full bg-slate-50 px-2 py-1 text-[11px] font-semibold text-slate-500">Manager</div>
    </div>
</div>
<?php /**PATH C:\www\rent_counter\resources\views/components/manager/stat-card.blade.php ENDPATH**/ ?>