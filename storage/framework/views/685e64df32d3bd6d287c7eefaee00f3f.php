<?php $__env->startSection('content'); ?>
<div class="container mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900"><?php echo e(__('manager.pages.index.title') ?? 'Managers'); ?></h1>
            <p class="text-slate-600"><?php echo e(__('manager.pages.index.subtitle') ?? 'All managers across all organizations'); ?></p>
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
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider"><?php echo e(__('manager.fields.name') ?? 'Name'); ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider"><?php echo e(__('manager.fields.email') ?? 'Email'); ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider"><?php echo e(__('manager.fields.properties') ?? 'Properties'); ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider"><?php echo e(__('manager.fields.buildings') ?? 'Buildings'); ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider"><?php echo e(__('manager.fields.invoices') ?? 'Invoices'); ?></th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider"><?php echo e(__('app.nav.actions') ?? 'Actions'); ?></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-200">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $managers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $manager): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500"><?php echo e($manager->id); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                            <a href="<?php echo e(route('superadmin.managers.show', $manager)); ?>" class="text-indigo-600 hover:text-indigo-800">
                                <?php echo e($manager->name); ?>

                            </a>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500"><?php echo e($manager->email); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500"><?php echo e($manager->properties_count); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500"><?php echo e($manager->buildings_count); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500"><?php echo e($manager->invoices_count); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end gap-2">
                                <a href="<?php echo e(route('superadmin.managers.show', $manager)); ?>" class="px-2 py-1 text-xs font-semibold text-white bg-slate-600 rounded hover:bg-slate-700">
                                    <?php echo e(__('common.view')); ?>

                                </a>
                                <a href="<?php echo e(route('filament.admin.resources.users.edit', $manager)); ?>" class="px-2 py-1 text-xs font-semibold text-white bg-indigo-600 rounded hover:bg-indigo-700">
                                    <?php echo e(__('common.edit')); ?>

                                </a>
                                <form action="<?php echo e(route('filament.admin.resources.users.destroy', $manager)); ?>" method="POST" onsubmit="return confirm('<?php echo e(__('common.confirm_delete')); ?>');">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>
                                    <button type="submit" class="px-2 py-1 text-xs font-semibold text-white bg-red-600 rounded hover:bg-red-700">
                                        <?php echo e(__('common.delete')); ?>

                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-slate-500">
                            <?php echo e(__('manager.empty') ?? 'No managers found'); ?>

                        </td>
                    </tr>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($managers->hasPages()): ?>
        <div class="mt-4">
            <?php echo e($managers->links()); ?>

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

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\www\rent_counter\resources\views/superadmin/managers/index.blade.php ENDPATH**/ ?>