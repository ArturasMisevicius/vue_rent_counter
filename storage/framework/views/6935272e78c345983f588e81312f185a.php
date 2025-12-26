<?php $__env->startSection('title', __('tenant.property.title')); ?>

<?php $__env->startSection('tenant-content'); ?>
<?php if (isset($component)) { $__componentOriginal5daf71cc63742455f9f020a381938683 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5daf71cc63742455f9f020a381938683 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tenant.page','data' => ['title' => __('tenant.property.title'),'description' => __('tenant.property.description')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tenant.page'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('tenant.property.title')),'description' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('tenant.property.description'))]); ?>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!$property): ?>
        <?php if (isset($component)) { $__componentOriginal9c1ca064170a53b948f018bbf5edd33c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9c1ca064170a53b948f018bbf5edd33c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tenant.alert','data' => ['type' => 'warning','title' => __('tenant.property.no_property_title')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tenant.alert'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'warning','title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('tenant.property.no_property_title'))]); ?>
            <?php echo e(__('tenant.property.no_property_body')); ?>

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
        <?php if (isset($component)) { $__componentOriginal1479eaaad1c219d39a5c50a0a8cbec4f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1479eaaad1c219d39a5c50a0a8cbec4f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tenant.section-card','data' => ['title' => __('tenant.property.info_title')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tenant.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('tenant.property.info_title'))]); ?>
            <dl class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500"><?php echo e(__('tenant.property.labels.address')); ?></dt>
                    <dd class="mt-1 text-sm text-slate-900"><?php echo e($property->address); ?></dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500"><?php echo e(__('tenant.property.labels.type')); ?></dt>
                    <dd class="mt-1 text-sm text-slate-900"><?php echo e(enum_label($property->type)); ?></dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500"><?php echo e(__('tenant.property.labels.area')); ?></dt>
                    <dd class="mt-1 text-sm text-slate-900"><?php echo e($property->area_sqm); ?> m²</dd>
                </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($property->building): ?>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500"><?php echo e(__('tenant.property.labels.building')); ?></dt>
                    <dd class="mt-1 text-sm text-slate-900"><?php echo e($property->building->display_name); ?></dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500"><?php echo e(__('tenant.property.labels.building_address')); ?></dt>
                    <dd class="mt-1 text-sm text-slate-900"><?php echo e($property->building->address); ?></dd>
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

        <?php if (isset($component)) { $__componentOriginal1479eaaad1c219d39a5c50a0a8cbec4f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1479eaaad1c219d39a5c50a0a8cbec4f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tenant.section-card','data' => ['title' => __('tenant.property.meters_title'),'description' => __('tenant.property.meters_description')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tenant.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('tenant.property.meters_title')),'description' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('tenant.property.meters_description'))]); ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($property->meters && $property->meters->count() > 0): ?>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $property->meters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $meter): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php($serviceName = $meter->getServiceDisplayName())
                        <div class="rounded-2xl border border-slate-200/80 bg-white/95 p-4 shadow-sm shadow-slate-200/60">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">{{ __('tenant.meters.labels.type') }}</p>
                                    <p class="text-base font-semibold text-slate-900">{{ $serviceName }}</p>
                                </div>
                                <x-status-badge status="active">{{ __('tenant.property.meter_status') }}</x-status-badge>
                            </div>
                            <p class="mt-2 text-sm text-slate-700">
                                <span class="font-semibold text-slate-800">{{ __('tenant.property.labels.serial') }}</span> {{ $meter->serial_number }}
                            </p>
                            <div class="mt-4">
                                <a href="{{ route('tenant.meters.show', $meter) }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    {{ __('tenant.property.view_details') }}
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-slate-600">{{ __('tenant.property.no_meters') }}</p>
            @endif
        </x-tenant.section-card>

        <x-tenant.section-card :title="__('tenant.property.services_title')" :description="__('tenant.property.services_description')" class="mt-6">
            @if($property->serviceConfigurations && $property->serviceConfigurations->count() > 0)
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach($property->serviceConfigurations as $configuration)
                        @php
                            $service = $configuration->utilityService;
                            $requiresReadings = $configuration->requiresConsumptionData();
                            $metersCount = $configuration->meters?->count() ?? 0;
                        ?>

                        <div class="rounded-2xl border border-slate-200/80 bg-white/95 p-4 shadow-sm shadow-slate-200/60">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-base font-semibold text-slate-900"><?php echo e($service?->name ?? __('app.common.na')); ?></p>
                                    <p class="mt-1 text-sm text-slate-600">
                                        <?php echo e($configuration->pricing_model?->label() ?? $configuration->pricing_model?->value); ?>

                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($service?->unit_of_measurement): ?>
                                            • <?php echo e($service->unit_of_measurement); ?>

                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </p>
                                </div>
                                <span class="inline-flex items-center rounded-full bg-slate-900/5 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-700">
                                    <?php echo e($requiresReadings ? __('tenant.property.service_dynamic') : __('tenant.property.service_fixed')); ?>

                                </span>
                            </div>

                            <div class="mt-4 grid grid-cols-2 gap-3">
                                <div class="rounded-xl border border-slate-100 bg-white px-3 py-2 shadow-sm">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500"><?php echo e(__('tenant.property.service_meters')); ?></p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900"><?php echo e($metersCount); ?></p>
                                </div>
                                <div class="rounded-xl border border-slate-100 bg-white px-3 py-2 shadow-sm">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500"><?php echo e(__('tenant.property.service_input')); ?></p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">
                                        <?php echo e($requiresReadings ? __('tenant.property.service_input_meter') : __('tenant.property.service_input_none')); ?>

                                    </p>
                                </div>
                            </div>

                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($requiresReadings): ?>
                                <div class="mt-4">
                                    <a href="<?php echo e(route('tenant.meter-readings.index')); ?>" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        <?php echo e(__('tenant.property.submit_reading')); ?>

                                    </a>
                                </div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            <?php else: ?>
                <p class="text-sm text-slate-600"><?php echo e(__('tenant.property.no_services')); ?></p>
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

<?php echo $__env->make('layouts.tenant', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\www\rent_counter\resources\views/tenant/property/show.blade.php ENDPATH**/ ?>