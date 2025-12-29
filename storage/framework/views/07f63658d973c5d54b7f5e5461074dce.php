<?php $__env->startSection('title', __('meters.manager.index.title')); ?>

<?php $__env->startSection('content'); ?>
<div class="px-4 sm:px-6 lg:px-8">
<div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900"><?php echo e(__('meters.manager.index.title')); ?></h1>
            <p class="mt-2 text-sm text-slate-700"><?php echo e(__('meters.manager.index.description')); ?></p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('create', App\Models\Meter::class)): ?>
            <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['href' => ''.e(route('manager.meters.create')).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => ''.e(route('manager.meters.create')).'']); ?>
                <?php echo e(__('meters.actions.add')); ?>

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
        </div>
    </div>

    <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['class' => 'mt-8']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'mt-8']); ?>
        <div class="hidden sm:block">
        <?php if (isset($component)) { $__componentOriginalc8463834ba515134d5c98b88e1a9dc03 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc8463834ba515134d5c98b88e1a9dc03 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.data-table','data' => ['caption' => __('meters.manager.index.caption')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('data-table'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['caption' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('meters.manager.index.caption'))]); ?>
             <?php $__env->slot('header', null, []); ?> 
                <tr>
                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-0"><?php echo e(__('meters.manager.index.headers.serial_number')); ?></th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900"><?php echo e(__('meters.manager.index.headers.type')); ?></th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900"><?php echo e(__('meters.manager.index.headers.property')); ?></th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900"><?php echo e(__('meters.manager.index.headers.installation_date')); ?></th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900"><?php echo e(__('meters.manager.index.headers.latest_reading')); ?></th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900"><?php echo e(__('meters.manager.index.headers.zones')); ?></th>
                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0">
                        <span class="sr-only"><?php echo e(__('meters.manager.index.headers.actions')); ?></span>
                    </th>
                </tr>
             <?php $__env->endSlot(); ?>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $meters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $meter): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr>
                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-0">
                    <a href="<?php echo e(route('manager.meters.show', $meter)); ?>" class="text-indigo-600 hover:text-indigo-900">
                        <?php echo e($meter->serial_number); ?>

                    </a>
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                    <?php echo e($meter->getServiceDisplayName()); ?>

                    <span class="text-slate-400 text-xs">(<?php echo e($meter->getUnitOfMeasurement()); ?>)</span>
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                    <a href="<?php echo e(route('manager.properties.show', $meter->property)); ?>" class="text-indigo-600 hover:text-indigo-900">
                        <?php echo e($meter->property->address); ?>

                    </a>
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                    <?php echo e($meter->installation_date->format('M d, Y')); ?>

                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($meter->readings->isNotEmpty()): ?>
                        <?php echo e(number_format($meter->readings->first()->getEffectiveValue(), 2)); ?>

                        <span class="text-slate-400 text-xs">(<?php echo e($meter->readings->first()->reading_date->format('M d')); ?>)</span>
                    <?php else: ?>
                        <span class="text-slate-400"><?php echo e(__('meter_readings.empty.readings')); ?></span>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($meter->supports_zones): ?>
                        <?php if (isset($component)) { $__componentOriginal8860cf004fec956b6e41d036eb967550 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8860cf004fec956b6e41d036eb967550 = $attributes; } ?>
<?php $component = App\View\Components\StatusBadge::resolve(['status' => 'active'] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('status-badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\StatusBadge::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?><?php echo e(__('meters.manager.index.zones.yes')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8860cf004fec956b6e41d036eb967550)): ?>
<?php $attributes = $__attributesOriginal8860cf004fec956b6e41d036eb967550; ?>
<?php unset($__attributesOriginal8860cf004fec956b6e41d036eb967550); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8860cf004fec956b6e41d036eb967550)): ?>
<?php $component = $__componentOriginal8860cf004fec956b6e41d036eb967550; ?>
<?php unset($__componentOriginal8860cf004fec956b6e41d036eb967550); ?>
<?php endif; ?>
                    <?php else: ?>
                        <span class="text-slate-400"><?php echo e(__('meters.manager.index.zones.no')); ?></span>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </td>
                <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                    <div class="flex justify-end gap-2">
                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('view', $meter)): ?>
                        <a href="<?php echo e(route('manager.meters.show', $meter)); ?>" class="text-indigo-600 hover:text-indigo-900">
                            <?php echo e(__('meters.actions.view')); ?>

                        </a>
                        <?php endif; ?>
                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('update', $meter)): ?>
                        <a href="<?php echo e(route('manager.meters.edit', $meter)); ?>" class="text-indigo-600 hover:text-indigo-900">
                            <?php echo e(__('meters.actions.edit')); ?>

                        </a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr>
                <td colspan="7" class="px-3 py-8 text-center text-sm text-slate-500">
                    <?php echo e(__('meters.manager.index.empty.text')); ?> 
                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('create', App\Models\Meter::class)): ?>
                        <a href="<?php echo e(route('manager.meters.create')); ?>" class="text-indigo-600 hover:text-indigo-900"><?php echo e(__('meters.manager.index.empty.cta')); ?></a>
                    <?php endif; ?>
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
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $meters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $meter): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-slate-900"><?php echo e($meter->serial_number); ?></p>
                            <p class="text-xs text-slate-600 capitalize">
                                <?php echo e($meter->getServiceDisplayName()); ?>

                            </p>
                            <p class="text-xs text-slate-600 mt-1"><?php echo e($meter->property->address); ?></p>
                        </div>
                        <div class="text-right text-xs text-slate-600">
                            <p><?php echo e(__('meters.manager.index.headers.installation_date')); ?>: <?php echo e($meter->installation_date->format('M d, Y')); ?></p>
                            <p class="mt-1"><?php echo e(__('meters.manager.index.headers.zones')); ?>: <?php echo e($meter->supports_zones ? __('meters.manager.index.zones.yes') : __('meters.manager.index.zones.no')); ?></p>
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-slate-600">
                        <?php echo e(__('meters.manager.index.headers.latest_reading')); ?>:
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($meter->readings->isNotEmpty()): ?>
                            <span class="font-semibold text-slate-900"><?php echo e(number_format($meter->readings->first()->getEffectiveValue(), 2)); ?></span>
                            <span class="text-slate-400">(<?php echo e($meter->readings->first()->reading_date->format('M d')); ?>)</span>
                        <?php else: ?>
                            <span class="text-slate-400"><?php echo e(__('meter_readings.empty.readings')); ?></span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('view', $meter)): ?>
                        <a href="<?php echo e(route('manager.meters.show', $meter)); ?>" class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-indigo-700 shadow-sm transition hover:border-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <?php echo e(__('meters.actions.view')); ?>

                        </a>
                        <?php endif; ?>
                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('update', $meter)): ?>
                        <a href="<?php echo e(route('manager.meters.edit', $meter)); ?>" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <?php echo e(__('meters.actions.edit')); ?>

                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="rounded-2xl border border-dashed border-slate-200 bg-white px-4 py-6 text-center text-sm text-slate-600 shadow-sm">
                    <?php echo e(__('meters.manager.index.empty.text')); ?>

                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('create', App\Models\Meter::class)): ?>
                        <a href="<?php echo e(route('manager.meters.create')); ?>" class="text-indigo-700 font-semibold"><?php echo e(__('meters.manager.index.empty.cta')); ?></a>
                    <?php endif; ?>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($meters->hasPages()): ?>
        <div class="mt-4">
            <?php echo e($meters->links()); ?>

        </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\www\rent_counter\resources\views/manager/meters/index.blade.php ENDPATH**/ ?>