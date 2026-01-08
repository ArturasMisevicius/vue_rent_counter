<?php $__env->startSection('content'); ?>
<div class="container mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900"><?php echo e(__('superadmin.buildings.title')); ?></h1>
            <p class="text-slate-600"><?php echo e(__('superadmin.buildings.singular')); ?> overview across all organizations</p>
        </div>
    </div>

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
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider"><?php echo e(__('superadmin.buildings.fields.name')); ?></th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider"><?php echo e(__('superadmin.buildings.fields.address')); ?></th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider"><?php echo e(__('superadmin.buildings.fields.total_apartments')); ?></th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider"><?php echo e(__('superadmin.buildings.fields.properties')); ?></th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider"><?php echo e(__('app.nav.actions')); ?></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-200">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $buildings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $building): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500"><?php echo e($building->id); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                                <a href="<?php echo e(route('superadmin.buildings.show', $building)); ?>" class="text-indigo-600 hover:text-indigo-800">
                                    <?php echo e($building->name ?? __('superadmin.buildings.singular') . ' #' . $building->id); ?>

                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500"><?php echo e($building->address); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500"><?php echo e($building->total_apartments ?? '—'); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                <?php echo e($building->properties_count); ?>

                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right space-x-2">
                                <a href="<?php echo e(route('superadmin.buildings.show', $building)); ?>" class="inline-flex items-center px-3 py-1 rounded-md text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100"><?php echo e(__('common.view')); ?></a>
                                <a href="<?php echo e(route('filament.admin.resources.buildings.edit', $building)); ?>" class="inline-flex items-center px-3 py-1 rounded-md text-xs font-semibold text-amber-700 bg-amber-50 hover:bg-amber-100"><?php echo e(__('common.edit')); ?></a>
                                <form action="<?php echo e(route('filament.admin.resources.buildings.destroy', $building)); ?>" method="POST" class="inline">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>
                                    <button type="submit" onclick="return confirm('<?php echo e(__('common.confirm_delete')); ?>')" class="inline-flex items-center px-3 py-1 rounded-md text-xs font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100"><?php echo e(__('common.delete')); ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-sm text-slate-500"><?php echo e(__('superadmin.empty')); ?></td>
                        </tr>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </tbody>
            </table>
        </div>
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

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\www\rent_counter\resources\views/superadmin/buildings/index.blade.php ENDPATH**/ ?>