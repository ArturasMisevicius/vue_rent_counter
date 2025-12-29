<?php $__env->startSection('title', __('dashboard.manager.title')); ?>

<?php $__env->startSection('content'); ?>
<div class="px-4 sm:px-6 lg:px-8">
    <?php if (isset($component)) { $__componentOriginald3d6b3e971fccd37d20c615a504fd43d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald3d6b3e971fccd37d20c615a504fd43d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.manager.page','data' => ['title' => __('dashboard.manager.title'),'description' => __('dashboard.manager.description')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('manager.page'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('dashboard.manager.title')),'description' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('dashboard.manager.description'))]); ?>
         <?php $__env->slot('meta', null, []); ?> 
            <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 shadow-sm shadow-indigo-500/10">
                <span class="h-2 w-2 rounded-full bg-emerald-300"></span>
                <?php echo e(__('dashboard.manager.pending_section')); ?>: <?php echo e($stats['meters_pending_reading']); ?> · <?php echo e(__('dashboard.manager.stats.draft_invoices')); ?>: <?php echo e($stats['draft_invoices']); ?>

            </span>
         <?php $__env->endSlot(); ?>

         <?php $__env->slot('actions', null, []); ?> 
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('create', App\Models\MeterReading::class)): ?>
                <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['href' => ''.e(route('manager.meter-readings.create')).'','class' => 'bg-white/90 text-indigo-700 shadow-lg shadow-indigo-500/10 hover:bg-white']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => ''.e(route('manager.meter-readings.create')).'','class' => 'bg-white/90 text-indigo-700 shadow-lg shadow-indigo-500/10 hover:bg-white']); ?>
                    <?php echo e(__('meter_readings.actions.enter_new')); ?>

                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $attributes = $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $component = $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
            <?php endif; ?>
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('create', App\Models\Invoice::class)): ?>
                <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['href' => ''.e(route('manager.invoices.create')).'','class' => 'bg-slate-900 text-white shadow-lg shadow-slate-900/20 hover:bg-slate-800']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => ''.e(route('manager.invoices.create')).'','class' => 'bg-slate-900 text-white shadow-lg shadow-slate-900/20 hover:bg-slate-800']); ?>
                    <?php echo e(__('invoices.manager.index.generate')); ?>

                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $attributes = $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $component = $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
            <?php endif; ?>
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('create', App\Models\Property::class)): ?>
                <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['href' => ''.e(route('manager.properties.create')).'','variant' => 'secondary']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => ''.e(route('manager.properties.create')).'','variant' => 'secondary']); ?>
                    <?php echo e(__('properties.actions.add')); ?>

                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $attributes = $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561)): ?>
<?php $component = $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561; ?>
<?php unset($__componentOriginald0f1fd2689e4bb7060122a5b91fe8561); ?>
<?php endif; ?>
            <?php endif; ?>
         <?php $__env->endSlot(); ?>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <?php if (isset($component)) { $__componentOriginal3a0860922c84ce7a6def00e905f07a73 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3a0860922c84ce7a6def00e905f07a73 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.manager.stat-card','data' => ['label' => __('dashboard.manager.stats.total_properties'),'value' => $stats['total_properties'],'tone' => 'indigo','icon' => <<<'SVG'
<svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='1.5'>
    <path stroke-linecap='round' stroke-linejoin='round' d='M3 9.75 12 3l9 6.75M4.5 10.5V21h5.25v-4.5A1.5 1.5 0 0 1 11.25 15h1.5A1.5 1.5 0 0 1 14.25 16.5V21H19.5V10.5' />
</svg>
SVG]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('manager.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('dashboard.manager.stats.total_properties')),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($stats['total_properties']),'tone' => 'indigo','icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(<<<'SVG'
<svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='1.5'>
    <path stroke-linecap='round' stroke-linejoin='round' d='M3 9.75 12 3l9 6.75M4.5 10.5V21h5.25v-4.5A1.5 1.5 0 0 1 11.25 15h1.5A1.5 1.5 0 0 1 14.25 16.5V21H19.5V10.5' />
</svg>
SVG)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3a0860922c84ce7a6def00e905f07a73)): ?>
<?php $attributes = $__attributesOriginal3a0860922c84ce7a6def00e905f07a73; ?>
<?php unset($__attributesOriginal3a0860922c84ce7a6def00e905f07a73); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3a0860922c84ce7a6def00e905f07a73)): ?>
<?php $component = $__componentOriginal3a0860922c84ce7a6def00e905f07a73; ?>
<?php unset($__componentOriginal3a0860922c84ce7a6def00e905f07a73); ?>
<?php endif; ?>
            <?php if (isset($component)) { $__componentOriginal3a0860922c84ce7a6def00e905f07a73 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3a0860922c84ce7a6def00e905f07a73 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.manager.stat-card','data' => ['label' => __('dashboard.manager.stats.active_meters'),'value' => $stats['active_meters'],'tone' => 'slate','icon' => <<<'SVG'
<svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='1.5'>
    <path stroke-linecap='round' stroke-linejoin='round' d='M12 3v6m0 0 3-3m-3 3-3-3m6 6v6m0 0 3-3m-3 3-3-3M6 5.25h-.75A1.5 1.5 0 0 0 3.75 6.75v10.5a1.5 1.5 0 0 0 1.5 1.5H6M18 5.25h.75a1.5 1.5 0 0 1 1.5 1.5v10.5a1.5 1.5 0 0 1-1.5 1.5H18' />
</svg>
SVG]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('manager.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('dashboard.manager.stats.active_meters')),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($stats['active_meters']),'tone' => 'slate','icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(<<<'SVG'
<svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='1.5'>
    <path stroke-linecap='round' stroke-linejoin='round' d='M12 3v6m0 0 3-3m-3 3-3-3m6 6v6m0 0 3-3m-3 3-3-3M6 5.25h-.75A1.5 1.5 0 0 0 3.75 6.75v10.5a1.5 1.5 0 0 0 1.5 1.5H6M18 5.25h.75a1.5 1.5 0 0 1 1.5 1.5v10.5a1.5 1.5 0 0 1-1.5 1.5H18' />
</svg>
SVG)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3a0860922c84ce7a6def00e905f07a73)): ?>
<?php $attributes = $__attributesOriginal3a0860922c84ce7a6def00e905f07a73; ?>
<?php unset($__attributesOriginal3a0860922c84ce7a6def00e905f07a73); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3a0860922c84ce7a6def00e905f07a73)): ?>
<?php $component = $__componentOriginal3a0860922c84ce7a6def00e905f07a73; ?>
<?php unset($__componentOriginal3a0860922c84ce7a6def00e905f07a73); ?>
<?php endif; ?>
            <?php if (isset($component)) { $__componentOriginal3a0860922c84ce7a6def00e905f07a73 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3a0860922c84ce7a6def00e905f07a73 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.manager.stat-card','data' => ['label' => __('dashboard.manager.stats.meters_pending'),'value' => $stats['meters_pending_reading'],'tone' => 'amber','hint' => __('dashboard.manager.hints.operations'),'icon' => <<<'SVG'
<svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='1.5'>
    <path stroke-linecap='round' stroke-linejoin='round' d='M12 9v3.75m9 .75a9 9 0 1 1-18 0 9 9 0 0 1 18 0zm-9 3.75h.008v.008H12v-.008z' />
</svg>
SVG]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('manager.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('dashboard.manager.stats.meters_pending')),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($stats['meters_pending_reading']),'tone' => 'amber','hint' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('dashboard.manager.hints.operations')),'icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(<<<'SVG'
<svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='1.5'>
    <path stroke-linecap='round' stroke-linejoin='round' d='M12 9v3.75m9 .75a9 9 0 1 1-18 0 9 9 0 0 1 18 0zm-9 3.75h.008v.008H12v-.008z' />
</svg>
SVG)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3a0860922c84ce7a6def00e905f07a73)): ?>
<?php $attributes = $__attributesOriginal3a0860922c84ce7a6def00e905f07a73; ?>
<?php unset($__attributesOriginal3a0860922c84ce7a6def00e905f07a73); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3a0860922c84ce7a6def00e905f07a73)): ?>
<?php $component = $__componentOriginal3a0860922c84ce7a6def00e905f07a73; ?>
<?php unset($__componentOriginal3a0860922c84ce7a6def00e905f07a73); ?>
<?php endif; ?>
            <?php if (isset($component)) { $__componentOriginal3a0860922c84ce7a6def00e905f07a73 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3a0860922c84ce7a6def00e905f07a73 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.manager.stat-card','data' => ['label' => __('dashboard.manager.stats.draft_invoices'),'value' => $stats['draft_invoices'],'tone' => 'indigo','icon' => <<<'SVG'
<svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='1.5'>
    <path stroke-linecap='round' stroke-linejoin='round' d='M9 8.25h6m-6 3h3.75M7.5 21h9A2.25 2.25 0 0 0 18.75 18.75V5.25A2.25 2.25 0 0 0 16.5 3h-9A2.25 2.25 0 0 0 5.25 5.25v13.5A2.25 2.25 0 0 0 7.5 21z' />
</svg>
SVG]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('manager.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('dashboard.manager.stats.draft_invoices')),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($stats['draft_invoices']),'tone' => 'indigo','icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(<<<'SVG'
<svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='1.5'>
    <path stroke-linecap='round' stroke-linejoin='round' d='M9 8.25h6m-6 3h3.75M7.5 21h9A2.25 2.25 0 0 0 18.75 18.75V5.25A2.25 2.25 0 0 0 16.5 3h-9A2.25 2.25 0 0 0 5.25 5.25v13.5A2.25 2.25 0 0 0 7.5 21z' />
</svg>
SVG)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3a0860922c84ce7a6def00e905f07a73)): ?>
<?php $attributes = $__attributesOriginal3a0860922c84ce7a6def00e905f07a73; ?>
<?php unset($__attributesOriginal3a0860922c84ce7a6def00e905f07a73); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3a0860922c84ce7a6def00e905f07a73)): ?>
<?php $component = $__componentOriginal3a0860922c84ce7a6def00e905f07a73; ?>
<?php unset($__componentOriginal3a0860922c84ce7a6def00e905f07a73); ?>
<?php endif; ?>
            <?php if (isset($component)) { $__componentOriginal3a0860922c84ce7a6def00e905f07a73 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3a0860922c84ce7a6def00e905f07a73 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.manager.stat-card','data' => ['label' => __('dashboard.manager.stats.overdue_invoices'),'value' => $stats['overdue_invoices'],'tone' => 'amber','hint' => __('dashboard.manager.hints.drafts'),'icon' => <<<'SVG'
<svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='1.5'>
    <path stroke-linecap='round' stroke-linejoin='round' d='M12 6v6l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z' />
</svg>
SVG]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('manager.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('dashboard.manager.stats.overdue_invoices')),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($stats['overdue_invoices']),'tone' => 'amber','hint' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('dashboard.manager.hints.drafts')),'icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(<<<'SVG'
<svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='1.5'>
    <path stroke-linecap='round' stroke-linejoin='round' d='M12 6v6l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z' />
</svg>
SVG)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3a0860922c84ce7a6def00e905f07a73)): ?>
<?php $attributes = $__attributesOriginal3a0860922c84ce7a6def00e905f07a73; ?>
<?php unset($__attributesOriginal3a0860922c84ce7a6def00e905f07a73); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3a0860922c84ce7a6def00e905f07a73)): ?>
<?php $component = $__componentOriginal3a0860922c84ce7a6def00e905f07a73; ?>
<?php unset($__componentOriginal3a0860922c84ce7a6def00e905f07a73); ?>
<?php endif; ?>
            <?php if (isset($component)) { $__componentOriginal3a0860922c84ce7a6def00e905f07a73 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3a0860922c84ce7a6def00e905f07a73 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.manager.stat-card','data' => ['label' => __('dashboard.manager.stats.active_tenants'),'value' => $stats['active_tenants'],'tone' => 'emerald','icon' => <<<'SVG'
<svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='1.5'>
    <path stroke-linecap='round' stroke-linejoin='round' d='M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0ZM6.75 19.5a4.5 4.5 0 0 1 10.5 0' />
</svg>
SVG]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('manager.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('dashboard.manager.stats.active_tenants')),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($stats['active_tenants']),'tone' => 'emerald','icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(<<<'SVG'
<svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='1.5'>
    <path stroke-linecap='round' stroke-linejoin='round' d='M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0ZM6.75 19.5a4.5 4.5 0 0 1 10.5 0' />
</svg>
SVG)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3a0860922c84ce7a6def00e905f07a73)): ?>
<?php $attributes = $__attributesOriginal3a0860922c84ce7a6def00e905f07a73; ?>
<?php unset($__attributesOriginal3a0860922c84ce7a6def00e905f07a73); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3a0860922c84ce7a6def00e905f07a73)): ?>
<?php $component = $__componentOriginal3a0860922c84ce7a6def00e905f07a73; ?>
<?php unset($__componentOriginal3a0860922c84ce7a6def00e905f07a73); ?>
<?php endif; ?>
        </div>

        <?php if (isset($component)) { $__componentOriginal6cdb50b9c5d3d4543fd61ca89a910094 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6cdb50b9c5d3d4543fd61ca89a910094 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.manager.section-card','data' => ['title' => __('dashboard.manager.sections.operations'),'description' => __('dashboard.manager.hints.operations'),'class' => 'mt-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('manager.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('dashboard.manager.sections.operations')),'description' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('dashboard.manager.hints.operations')),'class' => 'mt-4']); ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($propertiesNeedingReadings->isNotEmpty()): ?>
                <div class="space-y-3">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $propertiesNeedingReadings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $property): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="flex flex-col gap-3 rounded-xl border border-amber-100 bg-amber-50/60 px-4 py-3 shadow-inner shadow-amber-100/60 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm font-semibold text-amber-900"><?php echo e($property->address); ?></p>
                                <p class="text-xs text-amber-800">
                                    <?php echo e(trans_choice('dashboard.manager.pending_meter_line', $property->meters->count(), ['count' => $property->meters->count()])); ?>

                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($property->building): ?>
                                        · <?php echo e($property->building->name ?? $property->building->address); ?>

                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <span class="inline-flex items-center rounded-full bg-white px-3 py-1 text-xs font-semibold text-amber-800 ring-1 ring-amber-100"><?php echo e(__('app.nav.meters')); ?>: <?php echo e($property->meters->count()); ?></span>
                                <a href="<?php echo e(route('manager.meter-readings.create', ['property_id' => $property->id])); ?>" class="inline-flex items-center gap-2 rounded-lg bg-amber-600 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-amber-500">
                                    <?php echo e(__('meter_readings.actions.enter_new')); ?>

                                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12l-7.5 7.5M21 12H3" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            <?php else: ?>
                <p class="text-sm text-slate-600"><?php echo e(__('dashboard.manager.empty.operations')); ?></p>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6cdb50b9c5d3d4543fd61ca89a910094)): ?>
<?php $attributes = $__attributesOriginal6cdb50b9c5d3d4543fd61ca89a910094; ?>
<?php unset($__attributesOriginal6cdb50b9c5d3d4543fd61ca89a910094); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6cdb50b9c5d3d4543fd61ca89a910094)): ?>
<?php $component = $__componentOriginal6cdb50b9c5d3d4543fd61ca89a910094; ?>
<?php unset($__componentOriginal6cdb50b9c5d3d4543fd61ca89a910094); ?>
<?php endif; ?>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <?php if (isset($component)) { $__componentOriginal6cdb50b9c5d3d4543fd61ca89a910094 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6cdb50b9c5d3d4543fd61ca89a910094 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.manager.section-card','data' => ['title' => __('dashboard.manager.sections.drafts'),'description' => __('dashboard.manager.hints.drafts')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('manager.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('dashboard.manager.sections.drafts')),'description' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('dashboard.manager.hints.drafts'))]); ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($draftInvoices->isNotEmpty()): ?>
                    <div class="divide-y divide-slate-100">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $draftInvoices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $invoice): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="py-3">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900"><?php echo e($invoice->tenant->property->address ?? __('app.common.na')); ?></p>
                                        <p class="text-xs text-slate-500">
                                            <?php echo e($invoice->billing_period_start->format('M d')); ?> - <?php echo e($invoice->billing_period_end->format('M d, Y')); ?>

                                        </p>
                                        <p class="mt-1 text-xs text-slate-600">
                                            <?php echo e(__('invoices.labels.amount')); ?>: €<?php echo e(number_format($invoice->total_amount, 2)); ?>

                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <?php if (isset($component)) { $__componentOriginal8860cf004fec956b6e41d036eb967550 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8860cf004fec956b6e41d036eb967550 = $attributes; } ?>
<?php $component = App\View\Components\StatusBadge::resolve(['status' => 'draft'] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('status-badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\StatusBadge::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?><?php echo e(enum_label($invoice->status)); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8860cf004fec956b6e41d036eb967550)): ?>
<?php $attributes = $__attributesOriginal8860cf004fec956b6e41d036eb967550; ?>
<?php unset($__attributesOriginal8860cf004fec956b6e41d036eb967550); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8860cf004fec956b6e41d036eb967550)): ?>
<?php $component = $__componentOriginal8860cf004fec956b6e41d036eb967550; ?>
<?php unset($__componentOriginal8860cf004fec956b6e41d036eb967550); ?>
<?php endif; ?>
                                        <div class="mt-2 flex flex-wrap justify-end gap-2">
                                            <a href="<?php echo e(route('manager.invoices.show', $invoice)); ?>" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                                                <?php echo e(__('invoices.actions.view')); ?>

                                            </a>
                                            <a href="<?php echo e(route('manager.invoices.edit', $invoice)); ?>" class="text-xs font-semibold text-slate-700 hover:text-slate-900">
                                                <?php echo e(__('invoices.actions.edit')); ?>

                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                    <div class="mt-3">
                        <a href="<?php echo e(route('manager.invoices.drafts')); ?>" class="text-sm font-semibold text-indigo-700 hover:text-indigo-900">
                            <?php echo e(__('dashboard.manager.sections.drafts')); ?>

                        </a>
                    </div>
                <?php else: ?>
                    <p class="text-sm text-slate-600"><?php echo e(__('dashboard.manager.empty.drafts')); ?></p>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6cdb50b9c5d3d4543fd61ca89a910094)): ?>
<?php $attributes = $__attributesOriginal6cdb50b9c5d3d4543fd61ca89a910094; ?>
<?php unset($__attributesOriginal6cdb50b9c5d3d4543fd61ca89a910094); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6cdb50b9c5d3d4543fd61ca89a910094)): ?>
<?php $component = $__componentOriginal6cdb50b9c5d3d4543fd61ca89a910094; ?>
<?php unset($__componentOriginal6cdb50b9c5d3d4543fd61ca89a910094); ?>
<?php endif; ?>

            <?php if (isset($component)) { $__componentOriginal6cdb50b9c5d3d4543fd61ca89a910094 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6cdb50b9c5d3d4543fd61ca89a910094 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.manager.section-card','data' => ['title' => __('dashboard.manager.sections.recent'),'description' => __('dashboard.manager.hints.recent')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('manager.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('dashboard.manager.sections.recent')),'description' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('dashboard.manager.hints.recent'))]); ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($stats['recent_invoices']->isNotEmpty()): ?>
                    <div class="space-y-3">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $stats['recent_invoices']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $invoice): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="rounded-xl border border-slate-100 px-4 py-3 shadow-sm">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">#<?php echo e($invoice->id); ?> · <?php echo e($invoice->tenant->property->address ?? __('app.common.na')); ?></p>
                                        <p class="text-xs text-slate-500">
                                            <?php echo e($invoice->billing_period_start->format('M d')); ?> - <?php echo e($invoice->billing_period_end->format('M d, Y')); ?>

                                        </p>
                                        <p class="mt-1 text-xs text-slate-600">€<?php echo e(number_format($invoice->total_amount, 2)); ?></p>
                                    </div>
                                    <div class="text-right">
                                        <?php if (isset($component)) { $__componentOriginal8860cf004fec956b6e41d036eb967550 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8860cf004fec956b6e41d036eb967550 = $attributes; } ?>
<?php $component = App\View\Components\StatusBadge::resolve(['status' => $invoice->status->value] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('status-badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\StatusBadge::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?><?php echo e(enum_label($invoice->status)); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8860cf004fec956b6e41d036eb967550)): ?>
<?php $attributes = $__attributesOriginal8860cf004fec956b6e41d036eb967550; ?>
<?php unset($__attributesOriginal8860cf004fec956b6e41d036eb967550); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8860cf004fec956b6e41d036eb967550)): ?>
<?php $component = $__componentOriginal8860cf004fec956b6e41d036eb967550; ?>
<?php unset($__componentOriginal8860cf004fec956b6e41d036eb967550); ?>
<?php endif; ?>
                                        <a href="<?php echo e(route('manager.invoices.show', $invoice)); ?>" class="mt-2 block text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                                            <?php echo e(__('invoices.actions.view')); ?>

                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                <?php else: ?>
                    <p class="text-sm text-slate-600"><?php echo e(__('dashboard.manager.empty.recent')); ?></p>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6cdb50b9c5d3d4543fd61ca89a910094)): ?>
<?php $attributes = $__attributesOriginal6cdb50b9c5d3d4543fd61ca89a910094; ?>
<?php unset($__attributesOriginal6cdb50b9c5d3d4543fd61ca89a910094); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6cdb50b9c5d3d4543fd61ca89a910094)): ?>
<?php $component = $__componentOriginal6cdb50b9c5d3d4543fd61ca89a910094; ?>
<?php unset($__componentOriginal6cdb50b9c5d3d4543fd61ca89a910094); ?>
<?php endif; ?>
        </div>

        <?php if (isset($component)) { $__componentOriginal6cdb50b9c5d3d4543fd61ca89a910094 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6cdb50b9c5d3d4543fd61ca89a910094 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.manager.section-card','data' => ['title' => __('dashboard.manager.sections.shortcuts'),'description' => __('dashboard.manager.hints.shortcuts')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('manager.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('dashboard.manager.sections.shortcuts')),'description' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('dashboard.manager.hints.shortcuts'))]); ?>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('create', App\Models\MeterReading::class)): ?>
                    <a href="<?php echo e(route('manager.meter-readings.create')); ?>" class="group relative overflow-hidden rounded-2xl border border-indigo-100 bg-gradient-to-br from-indigo-50 via-white to-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg">
                        <div class="flex items-start gap-3">
                            <div class="rounded-xl bg-white/80 p-3 text-indigo-700 shadow-sm ring-1 ring-indigo-100">
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25m-4.5-13.5h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-900"><?php echo e(__('meter_readings.actions.enter_new')); ?></p>
                                <p class="text-xs text-slate-600"><?php echo e(__('dashboard.manager.quick_actions.enter_reading_desc')); ?></p>
                            </div>
                        </div>
                    </a>
                <?php endif; ?>

                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('create', App\Models\Invoice::class)): ?>
                    <a href="<?php echo e(route('manager.invoices.create')); ?>" class="group relative overflow-hidden rounded-2xl border border-slate-100 bg-gradient-to-br from-slate-50 via-white to-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg">
                        <div class="flex items-start gap-3">
                            <div class="rounded-xl bg-white/80 p-3 text-slate-900 shadow-sm ring-1 ring-slate-100">
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-900"><?php echo e(__('invoices.manager.index.generate')); ?></p>
                                <p class="text-xs text-slate-600"><?php echo e(__('dashboard.manager.quick_actions.generate_invoice_desc')); ?></p>
                            </div>
                        </div>
                    </a>
                <?php endif; ?>

                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('viewAny', App\Models\Property::class)): ?>
                    <a href="<?php echo e(route('manager.properties.index')); ?>" class="group relative overflow-hidden rounded-2xl border border-indigo-50 bg-gradient-to-br from-white via-white to-indigo-50 p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg">
                        <div class="flex items-start gap-3">
                            <div class="rounded-xl bg-indigo-50 p-3 text-indigo-700 ring-1 ring-indigo-100">
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5m-15-3h13.5m-12-3h10.5M4.5 10.5 12 3l7.5 7.5" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-900"><?php echo e(__('app.nav.properties')); ?></p>
                                <p class="text-xs text-slate-600"><?php echo e(__('dashboard.manager.quick_actions.view_buildings_desc')); ?></p>
                            </div>
                        </div>
                    </a>
                <?php endif; ?>

                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('viewAny', App\Models\Building::class)): ?>
                    <a href="<?php echo e(route('manager.buildings.index')); ?>" class="group relative overflow-hidden rounded-2xl border border-slate-100 bg-gradient-to-br from-white via-white to-slate-50 p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg">
                        <div class="flex items-start gap-3">
                            <div class="rounded-xl bg-white p-3 text-slate-900 ring-1 ring-slate-100">
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 3.75h15m-13.5 0V21m12-17.25V21M7.5 7.5h3m-3 3h3m-3 3h3m3-6h3m-3 3h3m-3 3h3M9 21v-3a1.5 1.5 0 0 1 1.5-1.5h3A1.5 1.5 0 0 1 15 18v3" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-900"><?php echo e(__('dashboard.manager.quick_actions.view_buildings')); ?></p>
                                <p class="text-xs text-slate-600"><?php echo e(__('dashboard.manager.quick_actions.view_buildings_desc')); ?></p>
                            </div>
                        </div>
                    </a>
                <?php endif; ?>

                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('viewAny', App\Models\Meter::class)): ?>
                    <a href="<?php echo e(route('manager.meters.index')); ?>" class="group relative overflow-hidden rounded-2xl border border-indigo-50 bg-gradient-to-br from-indigo-50 via-white to-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg">
                        <div class="flex items-start gap-3">
                            <div class="rounded-xl bg-white/80 p-3 text-indigo-700 shadow-sm ring-1 ring-indigo-100">
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94a1.125 1.125 0 0 1 1.11-.94h2.593a1.125 1.125 0 0 1 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 0 1 0 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 0 1 0-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869z' />
                                    <path stroke-linecap='round' stroke-linejoin='round' d='M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z' />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-900"><?php echo e(__('dashboard.manager.quick_actions.view_meters')); ?></p>
                                <p class="text-xs text-slate-600"><?php echo e(__('dashboard.manager.quick_actions.view_meters_desc')); ?></p>
                            </div>
                        </div>
                    </a>
                <?php endif; ?>

                <a href="<?php echo e(route('manager.reports.index')); ?>" class="group relative overflow-hidden rounded-2xl border border-slate-100 bg-gradient-to-br from-white via-slate-50 to-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg">
                    <div class="flex items-start gap-3">
                        <div class="rounded-xl bg-white p-3 text-slate-900 ring-1 ring-slate-100">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.5 9 13.5l4 4.5 6.75-9M3.75 5.25h16.5" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-900"><?php echo e(__('dashboard.manager.quick_actions.view_reports')); ?></p>
                            <p class="text-xs text-slate-600"><?php echo e(__('dashboard.manager.quick_actions.view_reports_desc')); ?></p>
                        </div>
                    </div>
                </a>
            </div>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6cdb50b9c5d3d4543fd61ca89a910094)): ?>
<?php $attributes = $__attributesOriginal6cdb50b9c5d3d4543fd61ca89a910094; ?>
<?php unset($__attributesOriginal6cdb50b9c5d3d4543fd61ca89a910094); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6cdb50b9c5d3d4543fd61ca89a910094)): ?>
<?php $component = $__componentOriginal6cdb50b9c5d3d4543fd61ca89a910094; ?>
<?php unset($__componentOriginal6cdb50b9c5d3d4543fd61ca89a910094); ?>
<?php endif; ?>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald3d6b3e971fccd37d20c615a504fd43d)): ?>
<?php $attributes = $__attributesOriginald3d6b3e971fccd37d20c615a504fd43d; ?>
<?php unset($__attributesOriginald3d6b3e971fccd37d20c615a504fd43d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald3d6b3e971fccd37d20c615a504fd43d)): ?>
<?php $component = $__componentOriginald3d6b3e971fccd37d20c615a504fd43d; ?>
<?php unset($__componentOriginald3d6b3e971fccd37d20c615a504fd43d); ?>
<?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\www\rent_counter\resources\views/manager/dashboard.blade.php ENDPATH**/ ?>