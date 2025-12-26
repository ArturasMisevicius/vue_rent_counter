<?php $__env->startSection('tenant-content'); ?>
<?php if (isset($component)) { $__componentOriginal5daf71cc63742455f9f020a381938683 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5daf71cc63742455f9f020a381938683 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tenant.page','data' => ['title' => __('invoices.tenant.title'),'description' => __('invoices.tenant.description')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tenant.page'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('invoices.tenant.title')),'description' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('invoices.tenant.description'))]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tenant.section-card','data' => ['title' => __('invoices.tenant.filters.title'),'description' => __('invoices.tenant.filters.description')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tenant.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('invoices.tenant.filters.title')),'description' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('invoices.tenant.filters.description'))]); ?>
        <form method="GET" action="<?php echo e(route('tenant.invoices.index')); ?>">
            <?php if (isset($component)) { $__componentOriginalb98937165472911853f6eaf30e23bc6a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb98937165472911853f6eaf30e23bc6a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tenant.stack','data' => ['gap' => '4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tenant.stack'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['gap' => '4']); ?>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(count($properties) > 1): ?>
                    <div>
                        <label for="property_id" class="block text-sm font-semibold text-slate-800"><?php echo e(__('invoices.tenant.filters.property')); ?></label>
                        <select name="property_id" id="property_id" class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value=""><?php echo e(__('invoices.tenant.filters.all_properties')); ?></option>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $properties; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $property): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($property->id); ?>" <?php echo e(request('property_id') == $property->id ? 'selected' : ''); ?>>
                                    <?php echo e($property->address); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </select>
                    </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    <div>
                        <label for="status" class="block text-sm font-semibold text-slate-800"><?php echo e(__('invoices.tenant.filters.status')); ?></label>
                        <select name="status" id="status" class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value=""><?php echo e(__('invoices.tenant.filters.all_statuses')); ?></option>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $invoiceStatusLabels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($value); ?>" <?php echo e(request('status') === $value ? 'selected' : ''); ?>><?php echo e($label); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </select>
                    </div>

                    <div>
                        <label for="from_date" class="block text-sm font-semibold text-slate-800"><?php echo e(__('invoices.tenant.filters.from_date')); ?></label>
                        <input type="date" name="from_date" id="from_date" value="<?php echo e(request('from_date')); ?>"
                               class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label for="to_date" class="block text-sm font-semibold text-slate-800"><?php echo e(__('invoices.tenant.filters.to_date')); ?></label>
                        <input type="date" name="to_date" id="to_date" value="<?php echo e(request('to_date')); ?>"
                               class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <?php echo e(__('invoices.tenant.filters.apply')); ?>

                    </button>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(request()->hasAny(['property_id', 'status', 'from_date', 'to_date'])): ?>
                    <a href="<?php echo e(route('tenant.invoices.index')); ?>" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <?php echo e(__('invoices.tenant.filters.clear')); ?>

                    </a>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
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
        </form>
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

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoices->isEmpty()): ?>
        <?php if (isset($component)) { $__componentOriginal1479eaaad1c219d39a5c50a0a8cbec4f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1479eaaad1c219d39a5c50a0a8cbec4f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tenant.section-card','data' => ['title' => __('invoices.tenant.empty.title')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tenant.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('invoices.tenant.empty.title'))]); ?>
            <p class="text-sm text-slate-600"><?php echo e(__('invoices.tenant.empty.description')); ?></p>
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
    <?php else: ?>
        <?php if (isset($component)) { $__componentOriginalb98937165472911853f6eaf30e23bc6a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb98937165472911853f6eaf30e23bc6a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tenant.stack','data' => ['gap' => '4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tenant.stack'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['gap' => '4']); ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $invoices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $invoice): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php if (isset($component)) { $__componentOriginal1479eaaad1c219d39a5c50a0a8cbec4f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1479eaaad1c219d39a5c50a0a8cbec4f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tenant.section-card','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tenant.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <?php if (isset($component)) { $__componentOriginalb98937165472911853f6eaf30e23bc6a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb98937165472911853f6eaf30e23bc6a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tenant.stack','data' => ['gap' => '1']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tenant.stack'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['gap' => '1']); ?>
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500"><?php echo e(__('invoices.tenant.list.invoice_label')); ?></p>
                            <h3 class="text-xl font-semibold text-slate-900">#<?php echo e($invoice->id); ?></h3>
                            <p class="text-sm text-slate-600">
                                <?php echo e(__('invoices.tenant.list.period', ['from' => $invoice->billing_period_start->format('Y-m-d'), 'to' => $invoice->billing_period_end->format('Y-m-d')])); ?>

                            </p>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->tenant && $invoice->tenant->property): ?>
                                <p class="text-sm text-slate-600">
                                    <?php echo e(__('invoices.tenant.list.property', ['address' => $invoice->tenant->property->address])); ?>

                                </p>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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
                        <?php if (isset($component)) { $__componentOriginalb98937165472911853f6eaf30e23bc6a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb98937165472911853f6eaf30e23bc6a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tenant.stack','data' => ['gap' => '2','class' => 'text-left sm:text-right']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tenant.stack'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['gap' => '2','class' => 'text-left sm:text-right']); ?>
                            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold <?php echo e($statusStyles[$invoice->status->value] ?? 'bg-slate-100 text-slate-800'); ?>">
                                <?php echo e(enum_label($invoice->status)); ?>

                            </span>
                            <p class="text-3xl font-semibold text-slate-900">€<?php echo e(number_format($invoice->total_amount, 2)); ?></p>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->due_date): ?>
                                <?php ($isOverdue = !$invoice->isPaid() && $invoice->due_date->isPast()); ?>
                                <p class="text-sm <?php echo e($isOverdue ? 'text-rose-600' : 'text-slate-600'); ?>">
                                    <?php echo e(__('invoices.tenant.list.due')); ?> <?php echo e($invoice->due_date->format('Y-m-d')); ?>

                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isOverdue): ?>
                                        <span class="ml-1 inline-flex items-center rounded-full bg-rose-100 px-2 py-0.5 text-[11px] font-semibold text-rose-700"><?php echo e(__('invoices.tenant.list.overdue')); ?></span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </p>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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

                    <div class="mt-5 flex flex-col gap-3 border-t border-slate-200 pt-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="text-sm text-slate-600">
                            <?php echo e(trans_choice('invoices.tenant.list.items', $invoice->items->count(), ['count' => $invoice->items->count()])); ?>

                        </div>
                        <a href="<?php echo e(route('tenant.invoices.show', $invoice)); ?>"
                           class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:w-auto">
                            <?php echo e(__('invoices.tenant.list.view_details')); ?>

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

        <div>
            <?php echo e($invoices->links()); ?>

        </div>
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

<?php echo $__env->make('layouts.tenant', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\www\rent_counter\resources\views/tenant/invoices/index.blade.php ENDPATH**/ ?>