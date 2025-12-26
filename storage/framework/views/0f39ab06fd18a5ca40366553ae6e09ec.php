<?php $__env->startSection('title', __('dashboard.tenant.title')); ?>

<?php $__env->startSection('tenant-content'); ?>
<?php if (isset($component)) { $__componentOriginal5daf71cc63742455f9f020a381938683 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5daf71cc63742455f9f020a381938683 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tenant.page','data' => ['title' => __('dashboard.tenant.title'),'description' => __('dashboard.tenant.description')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tenant.page'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('dashboard.tenant.title')),'description' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('dashboard.tenant.description'))]); ?>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!$stats['property']): ?>
        <?php if (isset($component)) { $__componentOriginal9c1ca064170a53b948f018bbf5edd33c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9c1ca064170a53b948f018bbf5edd33c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tenant.alert','data' => ['type' => 'warning','title' => __('dashboard.tenant.alerts.no_property_title')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tenant.alert'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'warning','title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('dashboard.tenant.alerts.no_property_title'))]); ?>
            <?php echo e(__('dashboard.tenant.alerts.no_property_body')); ?>

         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9c1ca064170a53b948f018bbf5edd33c)): ?>
<?php $attributes = $__attributesOriginal9c1ca064170a53b948f018bbf5edd33c; ?>
<?php unset($__attributesOriginal9c1ca064170a53b948f018bbf5edd33c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9c1ca064170a53b948f018bbf5edd33c)): ?>
<?php $component = $__componentOriginal9c1ca064170a53b948f018bbf5edd33c; ?>
<?php unset($__componentOriginal9c1ca064170a53b948f018bbf5edd33c); ?>
<?php endif; ?>
    <?php else: ?>
        <?php if (isset($component)) { $__componentOriginala2da19b56e08af6f893a5c15b0a34669 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala2da19b56e08af6f893a5c15b0a34669 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tenant.quick-actions','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tenant.quick-actions'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala2da19b56e08af6f893a5c15b0a34669)): ?>
<?php $attributes = $__attributesOriginala2da19b56e08af6f893a5c15b0a34669; ?>
<?php unset($__attributesOriginala2da19b56e08af6f893a5c15b0a34669); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala2da19b56e08af6f893a5c15b0a34669)): ?>
<?php $component = $__componentOriginala2da19b56e08af6f893a5c15b0a34669; ?>
<?php unset($__componentOriginala2da19b56e08af6f893a5c15b0a34669); ?>
<?php endif; ?>

        <?php if (isset($component)) { $__componentOriginal1479eaaad1c219d39a5c50a0a8cbec4f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1479eaaad1c219d39a5c50a0a8cbec4f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tenant.section-card','data' => ['title' => __('dashboard.tenant.property.title')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tenant.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('dashboard.tenant.property.title'))]); ?>
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500"><?php echo e(__('dashboard.tenant.property.address')); ?></dt>
                    <dd class="mt-1 text-sm text-slate-900"><?php echo e($stats['property']->address); ?></dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500"><?php echo e(__('dashboard.tenant.property.type')); ?></dt>
                    <dd class="mt-1 text-sm text-slate-900"><?php echo e(enum_label($stats['property']->type)); ?></dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500"><?php echo e(__('dashboard.tenant.property.area')); ?></dt>
                    <dd class="mt-1 text-sm text-slate-900"><?php echo e($stats['property']->area_sqm); ?> m²</dd>
                </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($stats['property']->building): ?>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500"><?php echo e(__('dashboard.tenant.property.building')); ?></dt>
                    <dd class="mt-1 text-sm text-slate-900"><?php echo e($stats['property']->building->display_name); ?></dd>
                </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </dl>
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

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($stats['unpaid_balance'] > 0): ?>
        <?php if (isset($component)) { $__componentOriginal9c1ca064170a53b948f018bbf5edd33c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9c1ca064170a53b948f018bbf5edd33c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tenant.alert','data' => ['type' => 'error','title' => __('dashboard.tenant.balance.title')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tenant.alert'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'error','title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('dashboard.tenant.balance.title'))]); ?>
            <p class="text-sm">
                <span class="font-semibold"><?php echo e(__('dashboard.tenant.balance.outstanding')); ?></span> €<?php echo e(number_format($stats['unpaid_balance'], 2)); ?>

            </p>
            <p class="mt-1 text-sm">
                <?php echo e(trans_choice('dashboard.tenant.balance.notice', $stats['unpaid_invoices'], ['count' => $stats['unpaid_invoices']])); ?>

            </p>
             <?php $__env->slot('action', null, []); ?> 
                <a href="<?php echo e(route('tenant.invoices.index')); ?>" class="inline-flex items-center px-3 py-2 rounded-lg border border-transparent bg-rose-500 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-rose-500">
                    <?php echo e(__('dashboard.tenant.balance.cta')); ?>

                </a>
             <?php $__env->endSlot(); ?>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9c1ca064170a53b948f018bbf5edd33c)): ?>
<?php $attributes = $__attributesOriginal9c1ca064170a53b948f018bbf5edd33c; ?>
<?php unset($__attributesOriginal9c1ca064170a53b948f018bbf5edd33c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9c1ca064170a53b948f018bbf5edd33c)): ?>
<?php $component = $__componentOriginal9c1ca064170a53b948f018bbf5edd33c; ?>
<?php unset($__componentOriginal9c1ca064170a53b948f018bbf5edd33c); ?>
<?php endif; ?>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
            <?php if (isset($component)) { $__componentOriginal34944d4d2c82cf149b0c83e118bc90c2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal34944d4d2c82cf149b0c83e118bc90c2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tenant.stat-card','data' => ['label' => __('dashboard.tenant.stats.total_invoices'),'value' => $stats['total_invoices']]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tenant.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('dashboard.tenant.stats.total_invoices')),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($stats['total_invoices'])]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal34944d4d2c82cf149b0c83e118bc90c2)): ?>
<?php $attributes = $__attributesOriginal34944d4d2c82cf149b0c83e118bc90c2; ?>
<?php unset($__attributesOriginal34944d4d2c82cf149b0c83e118bc90c2); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal34944d4d2c82cf149b0c83e118bc90c2)): ?>
<?php $component = $__componentOriginal34944d4d2c82cf149b0c83e118bc90c2; ?>
<?php unset($__componentOriginal34944d4d2c82cf149b0c83e118bc90c2); ?>
<?php endif; ?>
            <?php if (isset($component)) { $__componentOriginal34944d4d2c82cf149b0c83e118bc90c2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal34944d4d2c82cf149b0c83e118bc90c2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tenant.stat-card','data' => ['label' => __('dashboard.tenant.stats.unpaid_invoices'),'value' => $stats['unpaid_invoices'],'valueColor' => 'text-orange-600']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tenant.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('dashboard.tenant.stats.unpaid_invoices')),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($stats['unpaid_invoices']),'value-color' => 'text-orange-600']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal34944d4d2c82cf149b0c83e118bc90c2)): ?>
<?php $attributes = $__attributesOriginal34944d4d2c82cf149b0c83e118bc90c2; ?>
<?php unset($__attributesOriginal34944d4d2c82cf149b0c83e118bc90c2); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal34944d4d2c82cf149b0c83e118bc90c2)): ?>
<?php $component = $__componentOriginal34944d4d2c82cf149b0c83e118bc90c2; ?>
<?php unset($__componentOriginal34944d4d2c82cf149b0c83e118bc90c2); ?>
<?php endif; ?>
            <?php if (isset($component)) { $__componentOriginal34944d4d2c82cf149b0c83e118bc90c2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal34944d4d2c82cf149b0c83e118bc90c2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tenant.stat-card','data' => ['label' => __('dashboard.tenant.stats.active_meters'),'value' => $stats['property']->meters->count()]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tenant.stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('dashboard.tenant.stats.active_meters')),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($stats['property']->meters->count())]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal34944d4d2c82cf149b0c83e118bc90c2)): ?>
<?php $attributes = $__attributesOriginal34944d4d2c82cf149b0c83e118bc90c2; ?>
<?php unset($__attributesOriginal34944d4d2c82cf149b0c83e118bc90c2); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal34944d4d2c82cf149b0c83e118bc90c2)): ?>
<?php $component = $__componentOriginal34944d4d2c82cf149b0c83e118bc90c2; ?>
<?php unset($__componentOriginal34944d4d2c82cf149b0c83e118bc90c2); ?>
<?php endif; ?>
        </div>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($stats['latest_readings']->isNotEmpty()): ?>
        <?php if (isset($component)) { $__componentOriginal1479eaaad1c219d39a5c50a0a8cbec4f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1479eaaad1c219d39a5c50a0a8cbec4f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tenant.section-card','data' => ['title' => __('dashboard.tenant.readings.title')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tenant.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('dashboard.tenant.readings.title'))]); ?>
            <div class="hidden sm:block overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-[0.14em] text-slate-500"><?php echo e(__('dashboard.tenant.readings.meter_type')); ?></th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-[0.14em] text-slate-500"><?php echo e(__('dashboard.tenant.readings.serial')); ?></th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-[0.14em] text-slate-500"><?php echo e(__('dashboard.tenant.readings.reading')); ?></th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-[0.14em] text-slate-500"><?php echo e(__('dashboard.tenant.readings.date')); ?></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $stats['latest_readings']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $reading): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900">
                                <?php echo e($reading->meter->getServiceDisplayName()); ?>

                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                                <?php echo e($reading->meter->serial_number); ?>

                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                                <?php ($unit = $reading->meter->getUnitOfMeasurement()); ?>
                                <?php echo e(number_format($reading->value, 2)); ?> <?php echo e($unit); ?>

                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                                <?php echo e($reading->reading_date->format('Y-m-d')); ?>

                            </td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if (isset($component)) { $__componentOriginalb98937165472911853f6eaf30e23bc6a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb98937165472911853f6eaf30e23bc6a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tenant.stack','data' => ['gap' => '3','class' => 'sm:hidden']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tenant.stack'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['gap' => '3','class' => 'sm:hidden']); ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $stats['latest_readings']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $reading): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-semibold text-slate-900"><?php echo e($reading->meter->getServiceDisplayName()); ?></p>
                            <p class="text-xs font-semibold text-slate-500"><?php echo e($reading->reading_date->format('Y-m-d')); ?></p>
                        </div>
                        <p class="mt-1 text-sm text-slate-600"><?php echo e(__('dashboard.tenant.readings.serial_short')); ?> <?php echo e($reading->meter->serial_number); ?></p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">
                            <?php ($unit = $reading->meter->getUnitOfMeasurement()); ?>
                            <?php echo e(number_format($reading->value, 2)); ?> <?php echo e($unit); ?>

                        </p>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <?php if (isset($component)) { $__componentOriginal1479eaaad1c219d39a5c50a0a8cbec4f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1479eaaad1c219d39a5c50a0a8cbec4f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tenant.section-card','data' => ['title' => __('dashboard.tenant.consumption.title'),'description' => __('dashboard.tenant.consumption.description')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tenant.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('dashboard.tenant.consumption.title')),'description' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('dashboard.tenant.consumption.description'))]); ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(empty($stats['consumption_trends']) || $stats['consumption_trends']->every(fn($t) => !$t['previous'])): ?>
                <p class="text-sm text-slate-600"><?php echo e(__('dashboard.tenant.consumption.need_more')); ?></p>
            <?php else: ?>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $stats['consumption_trends']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $trend): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-semibold text-slate-900"><?php echo e($trend['meter']->getServiceDisplayName()); ?></p>
                                <p class="text-xs text-slate-500"><?php echo e($trend['meter']->serial_number); ?></p>
                            </div>
                            <div class="mt-2 flex items-baseline gap-2">
                                <p class="text-2xl font-semibold text-slate-900">
                                    <?php echo e($trend['latest'] ? number_format($trend['latest']->value, 2) : '—'); ?>

                                </p>
                                <p class="text-xs text-slate-600"><?php echo e(__('dashboard.tenant.consumption.current')); ?></p>
                            </div>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($trend['previous']): ?>
                                <p class="text-sm text-slate-600">
                                    <?php echo e(__('dashboard.tenant.consumption.previous', ['value' => number_format($trend['previous']->value, 2), 'date' => $trend['previous']->reading_date->format('Y-m-d')])); ?>

                                </p>
                                <p class="mt-1 text-sm <?php echo e($trend['delta'] !== null && $trend['delta'] >= 0 ? 'text-emerald-700' : 'text-rose-700'); ?>">
                                    <?php echo e($trend['delta'] !== null && $trend['delta'] >= 0 ? '▲' : '▼'); ?> <?php echo e(number_format(abs($trend['delta'] ?? 0), 2)); ?>

                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!is_null($trend['percent'])): ?>
                                        (<?php echo e(number_format($trend['percent'], 1)); ?>%)
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    <?php echo e(__('dashboard.tenant.consumption.since_last')); ?>

                                </p>
                            <?php else: ?>
                                <p class="text-sm text-slate-500"><?php echo e(__('dashboard.tenant.consumption.missing_previous')); ?></p>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5daf71cc63742455f9f020a381938683)): ?>
<?php $attributes = $__attributesOriginal5daf71cc63742455f9f020a381938683; ?>
<?php unset($__attributesOriginal5daf71cc63742455f9f020a381938683); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5daf71cc63742455f9f020a381938683)): ?>
<?php $component = $__componentOriginal5daf71cc63742455f9f020a381938683; ?>
<?php unset($__componentOriginal5daf71cc63742455f9f020a381938683); ?>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.tenant', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\www\rent_counter\resources\views/tenant/dashboard.blade.php ENDPATH**/ ?>