<?php $__env->startSection('title', __('tenant.meters.index_title')); ?>

<?php $__env->startSection('tenant-content'); ?>
<?php if (isset($component)) { $__componentOriginal5daf71cc63742455f9f020a381938683 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5daf71cc63742455f9f020a381938683 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tenant.page','data' => ['title' => __('tenant.meters.index_title'),'description' => __('tenant.meters.index_description')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tenant.page'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('tenant.meters.index_title')),'description' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('tenant.meters.index_description'))]); ?>
    <?php 
        $unitFor = fn($meter) => $meter->getUnitOfMeasurement();
        $metersCollection = $meters instanceof \Illuminate\Pagination\LengthAwarePaginator ? $meters->getCollection() : $meters;
        $latestReadingDate = $metersCollection
            ->flatMap(fn($meter) => $meter->readings)
            ->filter()
            ->pluck('reading_date')
            ->filter()
            ->sortDesc()
            ->first();

        $stylePalettes = [
            ['chip' => 'bg-indigo-100 text-indigo-800', 'halo' => 'from-indigo-200/70 via-white to-white'],
            ['chip' => 'bg-sky-100 text-sky-800', 'halo' => 'from-sky-200/80 via-white to-white'],
            ['chip' => 'bg-emerald-100 text-emerald-800', 'halo' => 'from-emerald-200/75 via-white to-white'],
            ['chip' => 'bg-amber-100 text-amber-800', 'halo' => 'from-amber-200/70 via-white to-white'],
            ['chip' => 'bg-rose-100 text-rose-800', 'halo' => 'from-rose-200/80 via-white to-white'],
            ['chip' => 'bg-violet-100 text-violet-800', 'halo' => 'from-violet-200/75 via-white to-white'],
        ];

        $styleForMeter = function ($meter) use ($stylePalettes) {
            $serviceId = $meter->serviceConfiguration?->utilityService?->id;
            $seed = is_int($serviceId) ? $serviceId : crc32((string) $meter->serial_number);
            $index = abs((int) $seed) % count($stylePalettes);

            return $stylePalettes[$index];
        };
    ?>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($metersCollection->isEmpty()): ?>
        <?php if (isset($component)) { $__componentOriginal9c1ca064170a53b948f018bbf5edd33c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9c1ca064170a53b948f018bbf5edd33c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tenant.alert','data' => ['type' => 'info','title' => __('tenant.meters.empty_title')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tenant.alert'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'info','title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('tenant.meters.empty_title'))]); ?>
            <?php echo e(__('tenant.meters.empty_body')); ?>

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
        <div class="grid grid-cols-1 gap-3 lg:grid-cols-3">
            <div class="relative overflow-hidden rounded-2xl border border-indigo-100 bg-gradient-to-br from-indigo-50 via-white to-white p-4 shadow-sm">
                <div class="absolute -left-4 -top-6 h-24 w-24 rounded-full bg-indigo-500/10 blur-3xl"></div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-indigo-700"><?php echo e(__('tenant.meters.overview.title')); ?></p>
                <p class="mt-2 text-3xl font-semibold text-slate-900"><?php echo e($metersCollection->count()); ?></p>
                <p class="text-sm text-slate-600"><?php echo e(__('tenant.meters.overview.active')); ?></p>
            </div>
            <div class="relative overflow-hidden rounded-2xl border border-sky-100 bg-gradient-to-br from-sky-50 via-white to-white p-4 shadow-sm">
                <div class="absolute -right-5 -top-8 h-24 w-24 rounded-full bg-sky-400/10 blur-3xl"></div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-sky-700"><?php echo e(__('tenant.meters.overview.zones')); ?></p>
                <p class="mt-2 text-3xl font-semibold text-slate-900"><?php echo e($metersCollection->where('supports_zones', true)->count()); ?></p>
                <p class="text-sm text-slate-600"><?php echo e(__('tenant.meters.overview.zones_hint')); ?></p>
            </div>
            <div class="relative overflow-hidden rounded-2xl border border-emerald-100 bg-gradient-to-br from-emerald-50 via-white to-white p-4 shadow-sm">
                <div class="absolute -right-4 -bottom-10 h-24 w-24 rounded-full bg-emerald-400/10 blur-3xl"></div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-emerald-700"><?php echo e(__('tenant.meters.overview.latest_update')); ?></p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">
                    <?php echo e($latestReadingDate ? $latestReadingDate->format('M d, Y') : __('tenant.meters.overview.no_readings')); ?>

                </p>
                <p class="text-sm text-slate-600"><?php echo e(__('tenant.meters.overview.recency_hint')); ?></p>
            </div>
        </div>

        <?php if (isset($component)) { $__componentOriginal1479eaaad1c219d39a5c50a0a8cbec4f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1479eaaad1c219d39a5c50a0a8cbec4f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tenant.section-card','data' => ['class' => 'mt-6']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tenant.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'mt-6']); ?>
            <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900"><?php echo e(__('tenant.meters.list_title')); ?></h2>
                    <p class="text-sm text-slate-600"><?php echo e(__('tenant.meters.list_description')); ?></p>
                </div>
                <a href="<?php echo e(route('tenant.meter-readings.index')); ?>" class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <?php echo e(__('tenant.meters.all_readings')); ?>

                </a>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $meters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $meter): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php 
                        $latest = $meter->readings->first(); 
                        $style = $styleForMeter($meter);
                    ?>
                    <div class="relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white/95 shadow-md shadow-slate-200/60 transition hover:border-indigo-200">
                        <div class="absolute inset-0 bg-gradient-to-br <?php echo e($style['halo']); ?>"></div>
                        <div class="absolute right-4 top-4 h-16 w-16 rounded-full bg-slate-200/40 blur-3xl"></div>
                        <div class="relative flex flex-col gap-4 p-5">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold <?php echo e($style['chip']); ?>">
                                        <span class="text-base">&bull;</span>
                                        <?php echo e($meter->getServiceDisplayName()); ?>

                                    </span>
                                    <span class="inline-flex items-center gap-2 rounded-full bg-slate-900/5 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-700">
                                        <?php echo e($meter->supports_zones ? __('tenant.meters.labels.day_night') : __('tenant.meters.labels.single_zone')); ?>

                                    </span>
                                </div>
                                <?php if (isset($component)) { $__componentOriginal8860cf004fec956b6e41d036eb967550 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8860cf004fec956b6e41d036eb967550 = $attributes; } ?>
<?php $component = App\View\Components\StatusBadge::resolve(['status' => 'active'] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('status-badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\StatusBadge::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?><?php echo e(__('tenant.meters.status_active')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8860cf004fec956b6e41d036eb967550)): ?>
<?php $attributes = $__attributesOriginal8860cf004fec956b6e41d036eb967550; ?>
<?php unset($__attributesOriginal8860cf004fec956b6e41d036eb967550); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8860cf004fec956b6e41d036eb967550)): ?>
<?php $component = $__componentOriginal8860cf004fec956b6e41d036eb967550; ?>
<?php unset($__componentOriginal8860cf004fec956b6e41d036eb967550); ?>
<?php endif; ?>
                            </div>

                            <div class="rounded-xl border border-slate-100 bg-white/80 px-4 py-3 shadow-sm">
                                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500"><?php echo e(__('tenant.meters.labels.serial')); ?></p>
                                <p class="mt-1 text-sm font-semibold text-slate-900"><?php echo e($meter->serial_number); ?></p>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div class="rounded-xl border border-slate-100 bg-white px-3 py-2 shadow-sm">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500"><?php echo e(__('tenant.meters.labels.latest')); ?></p>
                                    <div class="mt-1 flex items-baseline gap-2">
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($latest): ?>
                                            <p class="text-xl font-semibold text-slate-900">
                                                <?php echo e(number_format($latest->getEffectiveValue(), 2)); ?>

                                            </p>
                                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500"><?php echo e($unitFor($meter)); ?></p>
                                        <?php else: ?>
                                            <span class="text-sm text-slate-500"><?php echo e(__('tenant.meters.labels.not_recorded')); ?></span>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </div>
                                </div>
                                <div class="rounded-xl border border-slate-100 bg-white px-3 py-2 shadow-sm">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500"><?php echo e(__('tenant.meters.labels.updated')); ?></p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">
                                        <?php echo e($latest ? $latest->reading_date->format('Y-m-d') : '—'); ?>

                                    </p>
                                    <p class="text-xs text-slate-500"><?php echo e(__('tenant.meters.overview.latest_update')); ?></p>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <a href="<?php echo e(route('tenant.meters.show', $meter)); ?>" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <?php echo e(__('tenant.meters.view_history')); ?>

                                </a>
                                <a href="<?php echo e(route('tenant.meter-readings.index')); ?>" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <?php echo e(__('tenant.meters.all_readings')); ?>

                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($meters instanceof \Illuminate\Pagination\LengthAwarePaginator): ?>
                <div class="mt-6">
                    <?php echo e($meters->links()); ?>

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

<?php echo $__env->make('layouts.tenant', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\www\rent_counter\resources\views/tenant/meters/index.blade.php ENDPATH**/ ?>