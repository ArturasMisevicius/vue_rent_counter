<?php $__env->startSection('title', __('audit.pages.index.title')); ?>

<?php $__env->startSection('content'); ?>
<div class="px-4 sm:px-6 lg:px-8">
<div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900"><?php echo e(__('audit.pages.index.title')); ?></h1>
            <p class="mt-2 text-sm text-slate-700"><?php echo e(__('audit.pages.index.description')); ?></p>
        </div>
    </div>

    <!-- Filters -->
    <div class="mt-8">
        <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
            <form method="GET" action="<?php echo e(route('admin.audit.index')); ?>" class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                <div>
                    <label for="from_date" class="block text-sm font-medium text-slate-700"><?php echo e(__('audit.pages.index.filters.from_date')); ?></label>
                    <input 
                        type="date" 
                        name="from_date" 
                        id="from_date" 
                        value="<?php echo e(request('from_date')); ?>"
                        class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    >
                </div>

                <div>
                    <label for="to_date" class="block text-sm font-medium text-slate-700"><?php echo e(__('audit.pages.index.filters.to_date')); ?></label>
                    <input 
                        type="date" 
                        name="to_date" 
                        id="to_date" 
                        value="<?php echo e(request('to_date')); ?>"
                        class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    >
                </div>

                <div>
                    <label for="meter_serial" class="block text-sm font-medium text-slate-700"><?php echo e(__('audit.pages.index.filters.meter_serial')); ?></label>
                    <input 
                        type="text" 
                        name="meter_serial" 
                        id="meter_serial" 
                        value="<?php echo e(request('meter_serial')); ?>"
                        placeholder="<?php echo e(__('audit.pages.index.filters.meter_placeholder')); ?>"
                        class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    >
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                        <?php echo e(__('audit.pages.index.filters.apply')); ?>

                    </button>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(request()->hasAny(['from_date', 'to_date', 'meter_serial'])): ?>
                    <a href="<?php echo e(route('admin.audit.index')); ?>" class="rounded-md bg-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-300">
                        <?php echo e(__('audit.pages.index.filters.clear')); ?>

                    </a>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </form>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $attributes = $__attributesOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__attributesOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal53747ceb358d30c0105769f8471417f6)): ?>
<?php $component = $__componentOriginal53747ceb358d30c0105769f8471417f6; ?>
<?php unset($__componentOriginal53747ceb358d30c0105769f8471417f6); ?>
<?php endif; ?>
    </div>

    <!-- Audit Log Table -->
    <div class="mt-8">
        <div class="hidden sm:block">
            <?php if (isset($component)) { $__componentOriginalc8463834ba515134d5c98b88e1a9dc03 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc8463834ba515134d5c98b88e1a9dc03 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.data-table','data' => ['caption' => ''.e(__('audit.pages.index.table.caption')).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('data-table'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['caption' => ''.e(__('audit.pages.index.table.caption')).'']); ?>
                 <?php $__env->slot('header', null, []); ?> 
                    <tr>
                        <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-6"><?php echo e(__('audit.pages.index.table.timestamp')); ?></th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900"><?php echo e(__('audit.pages.index.table.meter')); ?></th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900"><?php echo e(__('audit.pages.index.table.reading_date')); ?></th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900"><?php echo e(__('audit.pages.index.table.old_value')); ?></th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900"><?php echo e(__('audit.pages.index.table.new_value')); ?></th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900"><?php echo e(__('audit.pages.index.table.changed_by')); ?></th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900"><?php echo e(__('audit.pages.index.table.reason')); ?></th>
                    </tr>
                 <?php $__env->endSlot(); ?>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $audits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $audit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm text-slate-900 sm:pl-6">
                        <?php echo e($audit->created_at->format('Y-m-d H:i:s')); ?>

                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($audit->meterReading && $audit->meterReading->meter): ?>
                            <div class="font-medium text-slate-900"><?php echo e($audit->meterReading->meter->serial_number); ?></div>
                            <div class="text-xs text-slate-500"><?php echo e($audit->meterReading->meter->getServiceDisplayName()); ?></div>
                        <?php else: ?>
                            <span class="text-slate-400"><?php echo e(__('audit.pages.index.states.not_available')); ?></span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($audit->meterReading): ?>
                            <?php echo e($audit->meterReading->reading_date->format('Y-m-d')); ?>

                        <?php else: ?>
                            <span class="text-slate-400"><?php echo e(__('audit.pages.index.states.not_available')); ?></span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                        <span class="font-mono"><?php echo e(number_format($audit->old_value, 2)); ?></span>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                        <span class="font-mono"><?php echo e(number_format($audit->new_value, 2)); ?></span>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                        <?php echo e($audit->changedByUser->name ?? __('audit.pages.index.states.system')); ?>

                    </td>
                    <td class="px-3 py-4 text-sm text-slate-500">
                        <div class="max-w-xs truncate" title="<?php echo e($audit->change_reason); ?>">
                            <?php echo e($audit->change_reason); ?>

                        </div>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="7" class="px-3 py-8 text-center text-sm text-slate-500">
                        <?php echo e(__('audit.pages.index.states.empty')); ?>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(request()->hasAny(['from_date', 'to_date', 'meter_serial'])): ?>
                            <a href="<?php echo e(route('admin.audit.index')); ?>" class="text-indigo-600 hover:text-indigo-500"><?php echo e(__('audit.pages.index.states.clear_filters')); ?></a> <?php echo e(__('audit.pages.index.states.see_all')); ?>

                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </td>
                </tr>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc8463834ba515134d5c98b88e1a9dc03)): ?>
<?php $attributes = $__attributesOriginalc8463834ba515134d5c98b88e1a9dc03; ?>
<?php unset($__attributesOriginalc8463834ba515134d5c98b88e1a9dc03); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc8463834ba515134d5c98b88e1a9dc03)): ?>
<?php $component = $__componentOriginalc8463834ba515134d5c98b88e1a9dc03; ?>
<?php unset($__componentOriginalc8463834ba515134d5c98b88e1a9dc03); ?>
<?php endif; ?>
        </div>

        <div class="sm:hidden space-y-3">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $audits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $audit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-slate-900"><?php echo e($audit->created_at->format('Y-m-d H:i:s')); ?></p>
                        <p class="text-xs text-slate-600"><?php echo e($audit->meterReading?->meter?->serial_number ?? __('audit.pages.index.states.not_available')); ?></p>
                        <p class="text-xs text-slate-600">
                            <?php echo e(__('audit.pages.index.table.reading')); ?> <?php echo e($audit->meterReading?->reading_date?->format('Y-m-d') ?? __('audit.pages.index.states.not_available')); ?>

                        </p>
                    </div>
                    <div class="text-right text-xs text-slate-600">
                        <p><?php echo e(__('audit.pages.index.states.old_short')); ?> <span class="font-mono"><?php echo e(number_format($audit->old_value, 2)); ?></span></p>
                        <p><?php echo e(__('audit.pages.index.states.new_short')); ?> <span class="font-mono"><?php echo e(number_format($audit->new_value, 2)); ?></span></p>
                    </div>
                </div>
                <p class="mt-1 text-xs text-slate-600"><?php echo e(__('audit.pages.index.states.by')); ?> <?php echo e($audit->changedByUser->name ?? __('audit.pages.index.states.system')); ?></p>
                <p class="mt-1 text-xs text-slate-600 truncate" title="<?php echo e($audit->change_reason); ?>"><?php echo e($audit->change_reason); ?></p>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="rounded-2xl border border-dashed border-slate-200 bg-white px-4 py-6 text-center text-sm text-slate-600 shadow-sm">
                <?php echo e(__('audit.pages.index.states.empty')); ?>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(request()->hasAny(['from_date', 'to_date', 'meter_serial'])): ?>
                    <a href="<?php echo e(route('admin.audit.index')); ?>" class="text-indigo-700 font-semibold"><?php echo e(__('audit.pages.index.states.clear_filters')); ?></a> <?php echo e(__('audit.pages.index.states.see_all')); ?>

                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </div>

    <div class="mt-4">
        <?php echo e($audits->links()); ?>

    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\www\rent_counter\resources\views/admin/audit/index.blade.php ENDPATH**/ ?>