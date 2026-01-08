<?php if (isset($component)) { $__componentOriginal1479eaaad1c219d39a5c50a0a8cbec4f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1479eaaad1c219d39a5c50a0a8cbec4f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tenant.section-card','data' => ['title' => __('dashboard.tenant.quick_actions.title'),'description' => __('dashboard.tenant.quick_actions.description')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tenant.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('dashboard.tenant.quick_actions.title')),'description' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('dashboard.tenant.quick_actions.description'))]); ?>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <a href="<?php echo e(route('tenant.invoices.index')); ?>" class="relative overflow-hidden rounded-xl border border-slate-200/80 bg-white/95 p-5 shadow-sm transition">
            <div class="pointer-events-none absolute inset-0 bg-gradient-to-br from-indigo-500/5 via-transparent to-sky-400/5"></div>
            <div class="relative flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-slate-900"><?php echo e(__('dashboard.tenant.quick_actions.invoices_title')); ?></h3>
                    <p class="text-sm text-slate-600"><?php echo e(__('dashboard.tenant.quick_actions.invoices_desc')); ?></p>
                </div>
            </div>
        </a>

        <a href="<?php echo e(route('tenant.meters.index')); ?>" class="relative overflow-hidden rounded-xl border border-slate-200/80 bg-white/95 p-5 shadow-sm transition">
            <div class="pointer-events-none absolute inset-0 bg-gradient-to-br from-indigo-500/5 via-transparent to-sky-400/5"></div>
            <div class="relative flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-slate-900"><?php echo e(__('dashboard.tenant.quick_actions.meters_title')); ?></h3>
                    <p class="text-sm text-slate-600"><?php echo e(__('dashboard.tenant.quick_actions.meters_desc')); ?></p>
                </div>
            </div>
        </a>

        <a href="<?php echo e(route('tenant.property.show')); ?>" class="relative overflow-hidden rounded-xl border border-slate-200/80 bg-white/95 p-5 shadow-sm transition">
            <div class="pointer-events-none absolute inset-0 bg-gradient-to-br from-indigo-500/5 via-transparent to-sky-400/5"></div>
            <div class="relative flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-slate-900"><?php echo e(__('dashboard.tenant.quick_actions.property_title')); ?></h3>
                    <p class="text-sm text-slate-600"><?php echo e(__('dashboard.tenant.quick_actions.property_desc')); ?></p>
                </div>
            </div>
        </a>
    </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1479eaaad1c219d39a5c50a0a8cbec4f)): ?>
<?php $attributes = $__attributesOriginal1479eaaad1c219d39a5c50a0a8cbec4f; ?>
<?php unset($__attributesOriginal1479eaaad1c219d39a5c50a0a8cbec4f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1479eaaad1c219d39a5c50a0a8cbec4f)): ?>
<?php $component = $__componentOriginal1479eaaad1c219d39a5c50a0a8cbec4f; ?>
<?php unset($__componentOriginal1479eaaad1c219d39a5c50a0a8cbec4f); ?>
<?php endif; ?>
<?php /**PATH C:\www\rent_counter\resources\views/components/tenant/quick-actions.blade.php ENDPATH**/ ?>