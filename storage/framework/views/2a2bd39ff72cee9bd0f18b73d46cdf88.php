<?php $__env->startSection('title', __('meter_readings.manager.index.title')); ?>

<?php $__env->startSection('content'); ?>
<div class="px-4 sm:px-6 lg:px-8">
<div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900"><?php echo e(__('meter_readings.manager.index.title')); ?></h1>
            <p class="mt-2 text-sm text-slate-700"><?php echo e(__('meter_readings.manager.index.description')); ?></p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('create', App\Models\MeterReading::class)): ?>
            <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['href' => ''.e(route('manager.meter-readings.create')).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => ''.e(route('manager.meter-readings.create')).'']); ?>
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
        </div>
    </div>

    <!-- Filters and Grouping -->
    <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['class' => 'mt-6']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'mt-6']); ?>
        <form method="GET" action="<?php echo e(route('manager.meter-readings.index')); ?>" class="grid grid-cols-1 gap-4 sm:grid-cols-4">
            <div>
                <label for="group_by" class="block text-sm font-medium text-slate-700"><?php echo e(__('meter_readings.manager.index.filters.group_by')); ?></label>
                <select name="group_by" id="group_by" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="none" <?php echo e($groupBy === 'none' ? 'selected' : ''); ?>><?php echo e(__('meter_readings.manager.index.filters.none')); ?></option>
                    <option value="property" <?php echo e($groupBy === 'property' ? 'selected' : ''); ?>><?php echo e(__('meter_readings.manager.index.filters.property')); ?></option>
                    <option value="service" <?php echo e($groupBy === 'service' ? 'selected' : ''); ?>><?php echo e(__('meter_readings.tenant.filters.service')); ?></option>
                </select>
            </div>

            <div>
                <label for="property_id" class="block text-sm font-medium text-slate-700"><?php echo e(__('meter_readings.manager.index.filters.property_label')); ?></label>
                <select name="property_id" id="property_id" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value=""><?php echo e(__('meter_readings.manager.index.filters.all_properties')); ?></option>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $properties; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $property): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($property->id); ?>" <?php echo e(request('property_id') == $property->id ? 'selected' : ''); ?>>
                        <?php echo e($property->address); ?>

                    </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </select>
            </div>

            <div>
                <label for="service" class="block text-sm font-medium text-slate-700"><?php echo e(__('meter_readings.tenant.filters.service')); ?></label>
                <select name="service" id="service" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value=""><?php echo e(__('meter_readings.tenant.filters.all_services')); ?></option>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $serviceFilterOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($value); ?>" <?php echo e($serviceFilter === $value ? 'selected' : ''); ?>><?php echo e($label); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </select>
            </div>

            <div class="flex items-end">
                <?php if (isset($component)) { $__componentOriginald0f1fd2689e4bb7060122a5b91fe8561 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald0f1fd2689e4bb7060122a5b91fe8561 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button','data' => ['type' => 'submit','class' => 'w-full']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit','class' => 'w-full']); ?>
                    <?php echo e(__('meter_readings.manager.index.filters.apply')); ?>

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
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($groupBy === 'property'): ?>
            <!-- Grouped by Property -->
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $readings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $propertyId => $propertyReadings): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="mb-8 last:mb-0">
                    <h3 class="text-lg font-semibold text-slate-900 mb-4 flex items-center">
                        <svg class="h-5 w-5 text-slate-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <a href="<?php echo e(route('manager.properties.show', $propertyReadings->first()->meter->property)); ?>" class="text-indigo-600 hover:text-indigo-900">
                            <?php echo e($propertyReadings->first()->meter->property->address); ?>

                        </a>
                        <span class="ml-2 text-sm font-normal text-slate-500">(<?php echo e(trans_choice('meter_readings.manager.index.count', $propertyReadings->count(), ['count' => $propertyReadings->count()])); ?>)</span>
                    </h3>
                    
                    <div class="hidden sm:block">
                    <?php if (isset($component)) { $__componentOriginalc8463834ba515134d5c98b88e1a9dc03 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc8463834ba515134d5c98b88e1a9dc03 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.data-table','data' => ['caption' => __('meter_readings.manager.index.captions.property')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('data-table'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['caption' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('meter_readings.manager.index.captions.property'))]); ?>
                         <?php $__env->slot('header', null, []); ?> 
                            <tr>
                                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-0"><?php echo e(__('meter_readings.tables.date')); ?></th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900"><?php echo e(__('meter_readings.tables.meter')); ?></th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900"><?php echo e(__('meter_readings.tenant.filters.service')); ?></th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900"><?php echo e(__('meter_readings.tables.value')); ?></th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900"><?php echo e(__('meter_readings.tables.zone')); ?></th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900"><?php echo e(__('meter_readings.tables.entered_by')); ?></th>
                                <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0">
                                <span class="sr-only"><?php echo e(__('meter_readings.tables.actions')); ?></span>
                                </th>
                            </tr>
                         <?php $__env->endSlot(); ?>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $propertyReadings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $reading): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-0">
                                <?php echo e($reading->reading_date->format('M d, Y')); ?>

                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                <a href="<?php echo e(route('manager.meters.show', $reading->meter)); ?>" class="text-indigo-600 hover:text-indigo-900">
                                    <?php echo e($reading->meter->serial_number); ?>

                                </a>
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                <?php echo e($reading->meter->getServiceDisplayName()); ?>

                                <span class="text-xs text-slate-400">(<?php echo e($reading->meter->getUnitOfMeasurement()); ?>)</span>
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                <?php echo e(number_format($reading->value, 2)); ?>

                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                <?php echo e($reading->zone ?? '-'); ?>

                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                <?php echo e($reading->enteredBy->name ?? __('meter_readings.na')); ?>

                            </td>
                            <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                                <div class="flex justify-end gap-2">
                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('view', $reading)): ?>
                                    <a href="<?php echo e(route('manager.meter-readings.show', $reading)); ?>" class="text-indigo-600 hover:text-indigo-900">
                                        <?php echo e(__('meter_readings.actions.view')); ?>

                                    </a>
                                    <?php endif; ?>
                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('update', $reading)): ?>
                                    <a href="<?php echo e(route('manager.meter-readings.edit', $reading)); ?>" class="text-indigo-600 hover:text-indigo-900">
                                        <?php echo e(__('meter_readings.actions.edit')); ?>

                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $propertyReadings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $reading): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-semibold text-slate-900"><?php echo e($reading->reading_date->format('M d, Y')); ?></p>
                                <p class="text-xs font-semibold text-slate-500"><?php echo e($reading->meter->getServiceDisplayName()); ?></p>
                            </div>
                            <p class="text-xs text-slate-600"><?php echo e(__('meter_readings.tables.meter')); ?>: <?php echo e($reading->meter->serial_number); ?></p>
                            <p class="text-xs text-slate-600"><?php echo e(__('meter_readings.tables.value')); ?>: <span class="font-semibold text-slate-900"><?php echo e(number_format($reading->value, 2)); ?></span></p>
                            <p class="text-xs text-slate-600"><?php echo e(__('meter_readings.tables.zone')); ?>: <?php echo e($reading->zone ?? '—'); ?></p>
                            <p class="text-xs text-slate-600 mt-1"><?php echo e(__('meter_readings.tables.entered_by')); ?>: <?php echo e($reading->enteredBy->name ?? __('meter_readings.na')); ?></p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('view', $reading)): ?>
                                <a href="<?php echo e(route('manager.meter-readings.show', $reading)); ?>" class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-indigo-700 shadow-sm transition hover:border-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <?php echo e(__('meter_readings.actions.view')); ?>

                                </a>
                                <?php endif; ?>
                                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('update', $reading)): ?>
                                <a href="<?php echo e(route('manager.meter-readings.edit', $reading)); ?>" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <?php echo e(__('meter_readings.actions.edit')); ?>

                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <p class="px-3 py-8 text-center text-sm text-slate-500">
                    <?php echo e(__('meter_readings.manager.index.empty.text')); ?> 
                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('create', App\Models\MeterReading::class)): ?>
                        <a href="<?php echo e(route('manager.meter-readings.create')); ?>" class="text-indigo-600 hover:text-indigo-900"><?php echo e(__('meter_readings.manager.index.empty.cta')); ?></a>
                    <?php endif; ?>
                </p>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <?php elseif($groupBy === 'service'): ?>
            <!-- Grouped by Service -->
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $readings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $serviceKey => $typeReadings): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php
                    $firstReading = $typeReadings->first();
                    $serviceLabel = $firstReading?->meter?->getServiceDisplayName() ?? $serviceKey;
                    $serviceUnit = $firstReading?->meter?->getUnitOfMeasurement();
                ?>
                <div class="mb-8 last:mb-0">
                    <h3 class="text-lg font-semibold text-slate-900 mb-4 flex items-center">
                        <svg class="h-5 w-5 text-slate-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <span class="capitalize"><?php echo e($serviceLabel); ?></span>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($serviceUnit)): ?>
                            <span class="ml-2 text-xs font-semibold text-slate-500">(<?php echo e($serviceUnit); ?>)</span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <span class="ml-2 text-sm font-normal text-slate-500">(<?php echo e(trans_choice('meter_readings.manager.index.count', $typeReadings->count(), ['count' => $typeReadings->count()])); ?>)</span>
                    </h3>
                    
                    <div class="hidden sm:block">
                    <?php if (isset($component)) { $__componentOriginalc8463834ba515134d5c98b88e1a9dc03 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc8463834ba515134d5c98b88e1a9dc03 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.data-table','data' => ['caption' => __('meter_readings.tenant.filters.services_group')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('data-table'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['caption' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('meter_readings.tenant.filters.services_group'))]); ?>
                         <?php $__env->slot('header', null, []); ?> 
                            <tr>
                                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-0"><?php echo e(__('meter_readings.tables.date')); ?></th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900"><?php echo e(__('meters.labels.property')); ?></th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900"><?php echo e(__('meter_readings.tables.meter')); ?></th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900"><?php echo e(__('meter_readings.tables.value')); ?></th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900"><?php echo e(__('meter_readings.tables.zone')); ?></th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900"><?php echo e(__('meter_readings.tables.entered_by')); ?></th>
                                <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0">
                                    <span class="sr-only"><?php echo e(__('meter_readings.tables.actions')); ?></span>
                                </th>
                            </tr>
                         <?php $__env->endSlot(); ?>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $typeReadings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $reading): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-0">
                                <?php echo e($reading->reading_date->format('M d, Y')); ?>

                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                <a href="<?php echo e(route('manager.properties.show', $reading->meter->property)); ?>" class="text-indigo-600 hover:text-indigo-900">
                                    <?php echo e($reading->meter->property->address); ?>

                                </a>
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                <a href="<?php echo e(route('manager.meters.show', $reading->meter)); ?>" class="text-indigo-600 hover:text-indigo-900">
                                    <?php echo e($reading->meter->serial_number); ?>

                                </a>
                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                <?php echo e(number_format($reading->value, 2)); ?>

                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                <?php echo e($reading->zone ?? '-'); ?>

                            </td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                                <?php echo e($reading->enteredBy->name ?? __('meter_readings.na')); ?>

                            </td>
                            <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                                <div class="flex justify-end gap-2">
                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('view', $reading)): ?>
                                    <a href="<?php echo e(route('manager.meter-readings.show', $reading)); ?>" class="text-indigo-600 hover:text-indigo-900">
                                        <?php echo e(__('meter_readings.actions.view')); ?>

                                    </a>
                                    <?php endif; ?>
                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('update', $reading)): ?>
                                    <a href="<?php echo e(route('manager.meter-readings.edit', $reading)); ?>" class="text-indigo-600 hover:text-indigo-900">
                                        <?php echo e(__('meter_readings.actions.edit')); ?>

                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $typeReadings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $reading): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-semibold text-slate-900"><?php echo e($reading->reading_date->format('M d, Y')); ?></p>
                                <p class="text-xs font-semibold text-slate-500"><?php echo e($reading->meter->property->address); ?></p>
                            </div>
                            <p class="text-xs text-slate-600"><?php echo e(__('meter_readings.manager.mobile.meter')); ?> <?php echo e($reading->meter->serial_number); ?></p>
                            <p class="text-xs text-slate-600"><?php echo e(__('meter_readings.manager.mobile.value')); ?> <span class="font-semibold text-slate-900"><?php echo e(number_format($reading->value, 2)); ?></span></p>
                            <p class="text-xs text-slate-600"><?php echo e(__('meter_readings.manager.mobile.zone')); ?> <?php echo e($reading->zone ?? '—'); ?></p>
                            <p class="text-xs text-slate-600 mt-1"><?php echo e(__('meter_readings.tables.entered_by')); ?>: <?php echo e($reading->enteredBy->name ?? __('meter_readings.na')); ?></p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('view', $reading)): ?>
                                <a href="<?php echo e(route('manager.meter-readings.show', $reading)); ?>" class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-indigo-700 shadow-sm transition hover:border-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <?php echo e(__('meter_readings.actions.view')); ?>

                                </a>
                                <?php endif; ?>
                                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('update', $reading)): ?>
                                <a href="<?php echo e(route('manager.meter-readings.edit', $reading)); ?>" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <?php echo e(__('meter_readings.actions.edit')); ?>

                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <p class="px-3 py-8 text-center text-sm text-slate-500">
                    <?php echo e(__('meter_readings.manager.index.empty.text')); ?> 
                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('create', App\Models\MeterReading::class)): ?>
                        <a href="<?php echo e(route('manager.meter-readings.create')); ?>" class="text-indigo-600 hover:text-indigo-900"><?php echo e(__('meter_readings.manager.index.empty.cta')); ?></a>
                    <?php endif; ?>
                </p>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <?php else: ?>
            <!-- No Grouping - Standard List -->
            <div class="hidden sm:block">
            <?php if (isset($component)) { $__componentOriginalc8463834ba515134d5c98b88e1a9dc03 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc8463834ba515134d5c98b88e1a9dc03 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.data-table','data' => ['caption' => __('meter_readings.manager.captions.list')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('data-table'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['caption' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('meter_readings.manager.captions.list'))]); ?>
                 <?php $__env->slot('header', null, []); ?> 
                    <tr>
                        <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-0"><?php echo e(__('meter_readings.tables.date')); ?></th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900"><?php echo e(__('meters.labels.property')); ?></th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900"><?php echo e(__('meter_readings.tables.meter')); ?></th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900"><?php echo e(__('meter_readings.tenant.filters.service')); ?></th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900"><?php echo e(__('meter_readings.tables.value')); ?></th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900"><?php echo e(__('meter_readings.tables.zone')); ?></th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900"><?php echo e(__('meter_readings.tables.entered_by')); ?></th>
                        <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0">
                            <span class="sr-only"><?php echo e(__('meter_readings.tables.actions')); ?></span>
                        </th>
                    </tr>
                 <?php $__env->endSlot(); ?>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $readings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $reading): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-0">
                        <?php echo e($reading->reading_date->format('M d, Y')); ?>

                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                        <a href="<?php echo e(route('manager.properties.show', $reading->meter->property)); ?>" class="text-indigo-600 hover:text-indigo-900">
                            <?php echo e($reading->meter->property->address); ?>

                        </a>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                        <a href="<?php echo e(route('manager.meters.show', $reading->meter)); ?>" class="text-indigo-600 hover:text-indigo-900">
                            <?php echo e($reading->meter->serial_number); ?>

                        </a>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                        <?php echo e($reading->meter->getServiceDisplayName()); ?>

                        <span class="text-xs text-slate-400">(<?php echo e($reading->meter->getUnitOfMeasurement()); ?>)</span>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                        <?php echo e(number_format($reading->value, 2)); ?>

                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                        <?php echo e($reading->zone ?? '-'); ?>

                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                        <?php echo e($reading->enteredBy->name ?? __('meter_readings.na')); ?>

                    </td>
                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                        <div class="flex justify-end gap-2">
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('view', $reading)): ?>
                                    <a href="<?php echo e(route('manager.meter-readings.show', $reading)); ?>" class="text-indigo-600 hover:text-indigo-900">
                                <?php echo e(__('meter_readings.actions.view')); ?>

                            </a>
                            <?php endif; ?>
                                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('update', $reading)): ?>
                                <a href="<?php echo e(route('manager.meter-readings.edit', $reading)); ?>" class="text-indigo-600 hover:text-indigo-900">
                                        <?php echo e(__('meter_readings.actions.edit')); ?>

                                    </a>
                                    <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="8" class="px-3 py-8 text-center text-sm text-slate-500">
                        <?php echo e(__('meter_readings.manager.index.empty.text')); ?> 
                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('create', App\Models\MeterReading::class)): ?>
                        <a href="<?php echo e(route('manager.meter-readings.create')); ?>" class="text-indigo-600 hover:text-indigo-900"><?php echo e(__('meter_readings.manager.index.empty.cta')); ?></a>
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
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $readings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $reading): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-semibold text-slate-900"><?php echo e($reading->reading_date->format('M d, Y')); ?></p>
                        <p class="text-xs font-semibold text-slate-500"><?php echo e($reading->meter->getServiceDisplayName()); ?></p>
                    </div>
                    <p class="text-xs text-slate-600"><?php echo e($reading->meter->property->address); ?></p>
                    <p class="text-xs text-slate-600"><?php echo e(__('meter_readings.manager.mobile.meter')); ?> <?php echo e($reading->meter->serial_number); ?></p>
                    <p class="text-xs text-slate-600"><?php echo e(__('meter_readings.manager.mobile.value')); ?> <span class="font-semibold text-slate-900"><?php echo e(number_format($reading->value, 2)); ?></span></p>
                    <p class="text-xs text-slate-600"><?php echo e(__('meter_readings.manager.mobile.zone')); ?> <?php echo e($reading->zone ?? '—'); ?></p>
                    <p class="text-xs text-slate-600 mt-1"><?php echo e(__('meter_readings.tables.entered_by')); ?>: <?php echo e($reading->enteredBy->name ?? __('meter_readings.na')); ?></p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('view', $reading)): ?>
                        <a href="<?php echo e(route('manager.meter-readings.show', $reading)); ?>" class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-indigo-700 shadow-sm transition hover:border-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <?php echo e(__('meter_readings.actions.view')); ?>

                        </a>
                        <?php endif; ?>
                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('update', $reading)): ?>
                        <a href="<?php echo e(route('manager.meter-readings.edit', $reading)); ?>" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <?php echo e(__('meter_readings.actions.edit')); ?>

                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="rounded-2xl border border-dashed border-slate-200 bg-white px-4 py-6 text-center text-sm text-slate-600 shadow-sm">
                    <?php echo e(__('meter_readings.manager.index.empty.text')); ?>

                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('create', App\Models\MeterReading::class)): ?>
                        <a href="<?php echo e(route('manager.meter-readings.create')); ?>" class="text-indigo-700 font-semibold"><?php echo e(__('meter_readings.manager.index.empty.cta')); ?></a>
                    <?php endif; ?>
                </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($readings->hasPages()): ?>
            <div class="mt-4">
                <?php echo e($readings->links()); ?>

            </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\www\rent_counter\resources\views/manager/meter-readings/index.blade.php ENDPATH**/ ?>