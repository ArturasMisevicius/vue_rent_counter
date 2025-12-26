<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['title', 'description' => null]));

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

foreach (array_filter((['title', 'description' => null]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<section <?php echo e($attributes->merge(['class' => 'relative overflow-hidden rounded-3xl border border-slate-200/80 bg-white/90 shadow-xl shadow-slate-200/70 backdrop-blur-sm'])); ?>>
    <div class="pointer-events-none absolute inset-0 opacity-60">
        <div class="absolute -left-10 -top-16 h-52 w-52 rounded-full bg-indigo-500/10 blur-3xl"></div>
        <div class="absolute -right-12 top-12 h-40 w-40 rounded-full bg-sky-400/10 blur-3xl"></div>
    </div>
    <div class="relative p-6 sm:p-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-indigo-500">Tenant Space</p>
                <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl"><?php echo e($title); ?></h1>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($description): ?>
                    <p class="mt-2 max-w-2xl text-sm text-slate-600"><?php echo e($description); ?></p>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($actions)): ?>
                <div class="flex flex-col items-start gap-3 sm:items-end">
                    <?php echo e($actions); ?>

                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>

        <?php if (isset($component)) { $__componentOriginalb98937165472911853f6eaf30e23bc6a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb98937165472911853f6eaf30e23bc6a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tenant.stack','data' => ['class' => 'mt-6']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tenant.stack'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'mt-6']); ?>
            <?php echo e($slot); ?>

         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb98937165472911853f6eaf30e23bc6a)): ?>
<?php $attributes = $__attributesOriginalb98937165472911853f6eaf30e23bc6a; ?>
<?php unset($__attributesOriginalb98937165472911853f6eaf30e23bc6a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb98937165472911853f6eaf30e23bc6a)): ?>
<?php $component = $__componentOriginalb98937165472911853f6eaf30e23bc6a; ?>
<?php unset($__componentOriginalb98937165472911853f6eaf30e23bc6a); ?>
<?php endif; ?>
    </div>
</section>
<?php /**PATH C:\www\rent_counter\resources\views/components/tenant/page.blade.php ENDPATH**/ ?>