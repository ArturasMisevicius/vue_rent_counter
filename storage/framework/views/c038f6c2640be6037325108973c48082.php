<?php $__env->startSection('title', __('buildings.pages.manager_index.title')); ?>

<?php $__env->startSection('content'); ?>
<div class="px-4 sm:px-6 lg:px-8">
<div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900"><?php echo e(__('buildings.pages.manager_index.title')); ?></h1>
            <p class="mt-2 text-sm text-slate-700"><?php echo e(__('buildings.pages.manager_index.description')); ?></p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('create', App\Models\Building::class)): ?>
            <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['href' => ''.e(route('manager.buildings.create')).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => ''.e(route('manager.buildings.create')).'']); ?>
                <?php echo e(__('buildings.pages.manager_index.add')); ?>

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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.data-table','data' => ['caption' => __('buildings.pages.manager_index.table_caption')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('data-table'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['caption' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('buildings.pages.manager_index.table_caption'))]); ?>
             <?php $__env->slot('header', null, []); ?> 
                <tr>
                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-0"><?php echo e(__('buildings.pages.manager_index.headers.building')); ?></th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900"><?php echo e(__('buildings.pages.manager_index.headers.total_apartments')); ?></th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900"><?php echo e(__('buildings.pages.manager_index.headers.properties')); ?></th>
                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0">
                        <span class="sr-only"><?php echo e(__('buildings.pages.manager_index.headers.actions')); ?></span>
                    </th>
                </tr>
             <?php $__env->endSlot(); ?>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $buildings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $building): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr>
                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-0">
                    <a href="<?php echo e(route('manager.buildings.show', $building)); ?>" class="text-indigo-600 hover:text-indigo-900">
                        <span class="block font-semibold text-slate-900"><?php echo e($building->display_name); ?></span>
                        <span class="block text-xs font-normal text-slate-600"><?php echo e($building->address); ?></span>
                    </a>
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                    <?php echo e($building->total_apartments); ?>

                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                    <?php echo e($building->properties_count); ?>

                </td>
                <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                    <div class="flex justify-end gap-2">
                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('view', $building)): ?>
                        <a href="<?php echo e(route('manager.buildings.show', $building)); ?>" class="text-indigo-600 hover:text-indigo-900">
                            <?php echo e(__('buildings.pages.manager_index.mobile.view')); ?>

                        </a>
                        <?php endif; ?>
                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('update', $building)): ?>
                        <a href="<?php echo e(route('manager.buildings.edit', $building)); ?>" class="text-indigo-600 hover:text-indigo-900">
                            <?php echo e(__('buildings.pages.manager_index.mobile.edit')); ?>

                        </a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr>
                <td colspan="4" class="px-3 py-8 text-center text-sm text-slate-500">
                    <?php echo e(__('buildings.pages.manager_index.empty')); ?> 
                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('create', App\Models\Building::class)): ?>
                        <a href="<?php echo e(route('manager.buildings.create')); ?>" class="text-indigo-600 hover:text-indigo-900"><?php echo e(__('buildings.pages.manager_index.create_now')); ?></a>
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
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $buildings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $building): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-slate-900"><?php echo e($building->display_name); ?></p>
                            <p class="text-xs text-slate-600"><?php echo e($building->address); ?></p>
                            <p class="text-xs text-slate-600"><?php echo e(__('buildings.pages.manager_index.mobile.apartments')); ?> <?php echo e($building->total_apartments); ?></p>
                            <p class="text-xs text-slate-600"><?php echo e(__('buildings.pages.manager_index.mobile.properties')); ?> <?php echo e($building->properties_count); ?></p>
                        </div>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('view', $building)): ?>
                        <a href="<?php echo e(route('manager.buildings.show', $building)); ?>" class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-indigo-700 shadow-sm transition hover:border-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <?php echo e(__('buildings.pages.manager_index.mobile.view')); ?>

                        </a>
                        <?php endif; ?>
                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('update', $building)): ?>
                        <a href="<?php echo e(route('manager.buildings.edit', $building)); ?>" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <?php echo e(__('buildings.pages.manager_index.mobile.edit')); ?>

                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="rounded-2xl border border-dashed border-slate-200 bg-white px-4 py-6 text-center text-sm text-slate-600 shadow-sm">
                    <?php echo e(__('buildings.pages.manager_index.empty')); ?>

                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('create', App\Models\Building::class)): ?>
                        <a href="<?php echo e(route('manager.buildings.create')); ?>" class="text-indigo-700 font-semibold"><?php echo e(__('buildings.pages.manager_index.create_now')); ?></a>
                    <?php endif; ?>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($buildings->hasPages()): ?>
        <div class="mt-4">
            <?php echo e($buildings->links()); ?>

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

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\www\rent_counter\resources\views/manager/buildings/index.blade.php ENDPATH**/ ?>