<?php $__env->startSection('content'); ?>
<div class="container mx-auto px-4 py-8" wire:poll.60s>
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-slate-900"><?php echo e(__('superadmin.dashboard.title')); ?></h1>
        <p class="text-slate-600 mt-2"><?php echo e(__('superadmin.dashboard.subtitle')); ?></p>
    </div>

    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">
        <?php if (isset($component)) { $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['title' => __('superadmin.dashboard.stats.total_subscriptions'),'value' => $totalSubscriptions,'icon' => '📊','href' => ''.e(route('superadmin.subscriptions.index')).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('superadmin.dashboard.stats.total_subscriptions')),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($totalSubscriptions),'icon' => '📊','href' => ''.e(route('superadmin.subscriptions.index')).'']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $attributes = $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $component = $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
        <?php if (isset($component)) { $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['title' => __('superadmin.dashboard.stats.active_subscriptions'),'value' => $activeSubscriptions,'icon' => '✅','color' => 'green','href' => ''.e(route('superadmin.subscriptions.index', ['status' => \App\Enums\SubscriptionStatus::ACTIVE->value])).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('superadmin.dashboard.stats.active_subscriptions')),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($activeSubscriptions),'icon' => '✅','color' => 'green','href' => ''.e(route('superadmin.subscriptions.index', ['status' => \App\Enums\SubscriptionStatus::ACTIVE->value])).'']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $attributes = $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $component = $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
        <?php if (isset($component)) { $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['title' => __('superadmin.dashboard.stats.expired_subscriptions'),'value' => $expiredSubscriptions,'icon' => '⏰','color' => 'red','href' => ''.e(route('superadmin.subscriptions.index', ['status' => \App\Enums\SubscriptionStatus::EXPIRED->value])).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('superadmin.dashboard.stats.expired_subscriptions')),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($expiredSubscriptions),'icon' => '⏰','color' => 'red','href' => ''.e(route('superadmin.subscriptions.index', ['status' => \App\Enums\SubscriptionStatus::EXPIRED->value])).'']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $attributes = $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $component = $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
        <?php if (isset($component)) { $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['title' => __('superadmin.dashboard.stats.suspended_subscriptions'),'value' => $suspendedSubscriptions,'icon' => '⏸️','color' => 'yellow','href' => ''.e(route('superadmin.subscriptions.index', ['status' => \App\Enums\SubscriptionStatus::SUSPENDED->value])).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('superadmin.dashboard.stats.suspended_subscriptions')),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($suspendedSubscriptions),'icon' => '⏸️','color' => 'yellow','href' => ''.e(route('superadmin.subscriptions.index', ['status' => \App\Enums\SubscriptionStatus::SUSPENDED->value])).'']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $attributes = $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $component = $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
    </div>

    
    <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['class' => 'mb-8','id' => 'system-health']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'mb-8','id' => 'system-health']); ?>
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-xl font-semibold"><?php echo e(__('superadmin.dashboard.system_health.title')); ?></h2>
                <p class="text-slate-500 text-sm"><?php echo e(__('superadmin.dashboard.system_health.description')); ?></p>
            </div>
            <form method="POST" action="<?php echo e(route('superadmin.dashboard.health-check')); ?>">
                <?php echo csrf_field(); ?>
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-slate-900 text-white rounded hover:bg-slate-800 text-sm font-semibold">
                    <?php echo e(__('superadmin.dashboard.system_health.actions.run_check')); ?>

                </button>
            </form>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $systemHealthMetrics; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $metric): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="p-4 bg-slate-50 rounded-xl border border-slate-200">
                    <div class="flex items-center justify-between">
                        <div class="font-semibold text-slate-900"><?php echo e(ucfirst($metric->metric_type)); ?></div>
                        <span class="text-xs font-semibold px-2 py-1 rounded-full bg-white border border-slate-200 text-slate-700">
                            <?php echo e(ucfirst($metric->status)); ?>

                        </span>
                    </div>
                    <div class="text-xs text-slate-500 mt-2"><?php echo e($metric->checked_at?->diffForHumans()); ?></div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <p class="text-slate-500"><?php echo e(__('superadmin.dashboard.system_health.empty')); ?></p>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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

    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <a href="<?php echo e(route('superadmin.organizations.index')); ?>" class="group block focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-100 rounded-2xl">
            <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['class' => 'h-full group-hover:-translate-y-0.5 group-hover:shadow-xl transition duration-200']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'h-full group-hover:-translate-y-0.5 group-hover:shadow-xl transition duration-200']); ?>
                <h2 class="text-xl font-semibold mb-4"><?php echo e(__('superadmin.dashboard.organizations.title')); ?></h2>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-slate-600"><?php echo e(__('superadmin.dashboard.organizations.total')); ?></span>
                        <span class="text-2xl font-bold"><?php echo e($totalOrganizations); ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-600"><?php echo e(__('superadmin.dashboard.organizations.active')); ?></span>
                        <span class="text-2xl font-bold text-green-600"><?php echo e($activeOrganizations); ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-600"><?php echo e(__('superadmin.dashboard.organizations.inactive')); ?></span>
                        <span class="text-2xl font-bold text-red-600"><?php echo e($totalOrganizations - $activeOrganizations); ?></span>
                    </div>
                </div>
                <div class="mt-4 flex items-center text-blue-600 hover:text-blue-800">
                    <span><?php echo e(__('superadmin.dashboard.organizations.view_all')); ?></span>
                    <svg class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
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
        </a>

        <a href="<?php echo e(route('superadmin.subscriptions.index')); ?>" class="group block focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-100 rounded-2xl">
            <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['class' => 'h-full group-hover:-translate-y-0.5 group-hover:shadow-xl transition duration-200']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'h-full group-hover:-translate-y-0.5 group-hover:shadow-xl transition duration-200']); ?>
                <h2 class="text-xl font-semibold mb-4"><?php echo e(__('superadmin.dashboard.subscription_plans.title')); ?></h2>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-slate-600"><?php echo e(__('superadmin.dashboard.subscription_plans.basic')); ?></span>
                        <span class="text-2xl font-bold"><?php echo e($subscriptionsByPlan['basic'] ?? 0); ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-600"><?php echo e(__('superadmin.dashboard.subscription_plans.professional')); ?></span>
                        <span class="text-2xl font-bold"><?php echo e($subscriptionsByPlan['professional'] ?? 0); ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-600"><?php echo e(__('superadmin.dashboard.subscription_plans.enterprise')); ?></span>
                        <span class="text-2xl font-bold"><?php echo e($subscriptionsByPlan['enterprise'] ?? 0); ?></span>
                    </div>
                </div>
                <div class="mt-4 flex items-center text-blue-600 hover:text-blue-800">
                    <span><?php echo e(__('superadmin.dashboard.subscription_plans.view_all')); ?></span>
                    <svg class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
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
        </a>
    </div>

    
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <?php if (isset($component)) { $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['title' => __('superadmin.dashboard.stats.total_properties'),'value' => $totalProperties,'icon' => '🏢','href' => ''.e(route('filament.admin.resources.properties.index')).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('superadmin.dashboard.stats.total_properties')),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($totalProperties),'icon' => '🏢','href' => ''.e(route('filament.admin.resources.properties.index')).'']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $attributes = $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $component = $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
        <?php if (isset($component)) { $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['title' => __('superadmin.dashboard.stats.total_buildings'),'value' => $totalBuildings,'icon' => '🏗️','href' => ''.e(route('filament.admin.resources.buildings.index')).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('superadmin.dashboard.stats.total_buildings')),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($totalBuildings),'icon' => '🏗️','href' => ''.e(route('filament.admin.resources.buildings.index')).'']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $attributes = $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $component = $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
        <?php if (isset($component)) { $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['title' => __('superadmin.dashboard.stats.total_tenants'),'value' => $totalTenants,'icon' => '👥','href' => ''.e(route('filament.admin.resources.users.index')).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('superadmin.dashboard.stats.total_tenants')),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($totalTenants),'icon' => '👥','href' => ''.e(route('filament.admin.resources.users.index')).'']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $attributes = $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $component = $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
        <?php if (isset($component)) { $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.stat-card','data' => ['title' => __('superadmin.dashboard.stats.total_invoices'),'value' => $totalInvoices,'icon' => '📄','href' => ''.e(route('filament.admin.resources.invoices.index')).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('superadmin.dashboard.stats.total_invoices')),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($totalInvoices),'icon' => '📄','href' => ''.e(route('filament.admin.resources.invoices.index')).'']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $attributes = $__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__attributesOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682)): ?>
<?php $component = $__componentOriginal527fae77f4db36afc8c8b7e9f5f81682; ?>
<?php unset($__componentOriginal527fae77f4db36afc8c8b7e9f5f81682); ?>
<?php endif; ?>
    </div>

    
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($expiringSubscriptions->count() > 0): ?>
    <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['class' => 'mb-8 border-yellow-300 bg-yellow-50']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'mb-8 border-yellow-300 bg-yellow-50']); ?>
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <span class="text-3xl">⚠️</span>
            </div>
            <div class="ml-3 flex-1">
                <h3 class="text-lg font-semibold text-yellow-800"><?php echo e(__('superadmin.dashboard.expiring_subscriptions.title')); ?></h3>
                <p class="text-yellow-700 mt-1"><?php echo e(__('superadmin.dashboard.expiring_subscriptions.alert', ['count' => $expiringSubscriptions->count()])); ?></p>
                <div class="mt-4 space-y-2">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $expiringSubscriptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $subscription): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="flex justify-between items-center bg-white p-3 rounded">
                        <div>
                            <span class="font-medium"><?php echo e($subscription->user->organization_name); ?></span>
                            <span class="text-sm text-slate-600 ml-2">(<?php echo e($subscription->user->email); ?>)</span>
                        </div>
                        <div class="text-right">
                            <span class="text-sm text-slate-600"><?php echo e(__('superadmin.dashboard.expiring_subscriptions.expires')); ?></span>
                            <span class="font-medium text-yellow-700"><?php echo e($subscription->expires_at->format('M d, Y')); ?></span>
                            <span class="text-sm text-slate-600 ml-2">(<?php echo e(now()->startOfDay()->diffInDays($subscription->expires_at->startOfDay())); ?> days)</span>
                        </div>
                    </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>
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
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
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
            <h2 class="text-xl font-semibold mb-4"><?php echo e(__('superadmin.dashboard.organizations.top_by_properties')); ?></h2>
            <div class="space-y-3">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $topOrganizations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $org): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="flex justify-between items-center p-3 bg-slate-50 rounded">
                    <div>
                        <div class="font-medium"><?php echo e($org->name); ?></div>
                        <div class="text-sm text-slate-600"><?php echo e($org->email); ?></div>
                    </div>
                    <div class="text-right">
                        <div class="text-2xl font-bold text-blue-600"><?php echo e($org->properties_count); ?></div>
                        <div class="text-xs text-slate-600"><?php echo e(__('superadmin.dashboard.organizations.properties_count')); ?></div>
                    </div>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <p class="text-slate-500 text-center py-4"><?php echo e(__('superadmin.dashboard.organizations.no_organizations')); ?></p>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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

        <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['id' => 'recent-activity']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'recent-activity']); ?>
            <h2 class="text-xl font-semibold mb-4"><?php echo e(__('superadmin.dashboard.recent_activity.title')); ?></h2>
            <div class="space-y-3">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $recentActivity; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $activity): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="flex justify-between items-center p-3 bg-slate-50 rounded">
                    <div>
                        <div class="font-medium"><?php echo e($activity->organization?->name ?? __('superadmin.dashboard.recent_activity.system')); ?></div>
                        <div class="text-sm text-slate-600"><?php echo e($activity->action); ?></div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-slate-600"><?php echo e(__('superadmin.dashboard.recent_activity.occurred')); ?></div>
                        <div class="text-sm font-medium"><?php echo e($activity->created_at->diffForHumans()); ?></div>
                    </div>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <p class="text-slate-500 text-center py-4"><?php echo e(__('superadmin.dashboard.recent_activity.no_activity')); ?></p>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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

    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['id' => 'subscriptions-overview']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'subscriptions-overview']); ?>
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-xl font-semibold"><?php echo e(__('superadmin.dashboard.overview.subscriptions.title')); ?></h2>
                    <p class="text-slate-500 text-sm"><?php echo e(__('superadmin.dashboard.overview.subscriptions.description')); ?></p>
                </div>
                <a href="<?php echo e(route('superadmin.subscriptions.index')); ?>" class="text-blue-600 hover:text-blue-800 text-sm font-semibold"><?php echo e(__('superadmin.dashboard.overview.subscriptions.open')); ?></a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wide"><?php echo e(__('superadmin.dashboard.overview.subscriptions.headers.organization')); ?></th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wide"><?php echo e(__('superadmin.dashboard.overview.subscriptions.headers.plan')); ?></th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wide"><?php echo e(__('superadmin.dashboard.overview.subscriptions.headers.status')); ?></th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wide"><?php echo e(__('superadmin.dashboard.overview.subscriptions.headers.expires')); ?></th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wide"><?php echo e(__('superadmin.dashboard.overview.subscriptions.headers.manage')); ?></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $subscriptionList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $subscription): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-900"><?php echo e($subscription->user->organization_name); ?></div>
                                    <div class="text-sm text-slate-500"><?php echo e($subscription->user->email); ?></div>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700"><?php echo e(enum_label($subscription->plan_type, \App\Enums\SubscriptionPlanType::class)); ?></td>
                                <td class="px-4 py-3"><?php if (isset($component)) { $__componentOriginal8860cf004fec956b6e41d036eb967550 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8860cf004fec956b6e41d036eb967550 = $attributes; } ?>
<?php $component = App\View\Components\StatusBadge::resolve(['status' => $subscription->status] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('status-badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\StatusBadge::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8860cf004fec956b6e41d036eb967550)): ?>
<?php $attributes = $__attributesOriginal8860cf004fec956b6e41d036eb967550; ?>
<?php unset($__attributesOriginal8860cf004fec956b6e41d036eb967550); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8860cf004fec956b6e41d036eb967550)): ?>
<?php $component = $__componentOriginal8860cf004fec956b6e41d036eb967550; ?>
<?php unset($__componentOriginal8860cf004fec956b6e41d036eb967550); ?>
<?php endif; ?></td>
                                <td class="px-4 py-3 text-sm text-slate-700">
                                    <div><?php echo e($subscription->expires_at->format('M d, Y')); ?></div>
                                    <div class="text-xs text-slate-500"><?php echo e($subscription->expires_at->diffForHumans()); ?></div>
                                </td>
                                <td class="px-4 py-3 text-right text-sm font-medium">
                                    <a href="<?php echo e(route('superadmin.subscriptions.show', $subscription)); ?>" class="text-blue-600 hover:text-blue-800"><?php echo e(__('superadmin.dashboard.overview.subscriptions.headers.manage')); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="5" class="px-4 py-4 text-center text-slate-500"><?php echo e(__('superadmin.dashboard.overview.subscriptions.empty')); ?></td>
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

        <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['id' => 'organizations-overview']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'organizations-overview']); ?>
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-xl font-semibold"><?php echo e(__('superadmin.dashboard.overview.organizations.title')); ?></h2>
                    <p class="text-slate-500 text-sm"><?php echo e(__('superadmin.dashboard.overview.organizations.description')); ?></p>
                </div>
                <a href="<?php echo e(route('superadmin.organizations.index')); ?>" class="text-blue-600 hover:text-blue-800 text-sm font-semibold"><?php echo e(__('superadmin.dashboard.overview.organizations.open')); ?></a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wide"><?php echo e(__('superadmin.dashboard.overview.organizations.headers.organization')); ?></th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wide"><?php echo e(__('superadmin.dashboard.overview.organizations.headers.subscription')); ?></th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wide"><?php echo e(__('superadmin.dashboard.overview.organizations.headers.status')); ?></th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wide"><?php echo e(__('superadmin.dashboard.overview.organizations.headers.created')); ?></th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wide"><?php echo e(__('superadmin.dashboard.overview.organizations.headers.manage')); ?></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $organizationList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $organization): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-900"><?php echo e($organization->name); ?></div>
                                    <div class="text-sm text-slate-500"><?php echo e($organization->email); ?></div>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($organization->plan): ?>
                                        <?php echo e(enum_label($organization->plan, \App\Enums\SubscriptionPlan::class)); ?>

                                    <?php else: ?>
                                        <span class="text-slate-400"><?php echo e(__('superadmin.dashboard.overview.organizations.no_subscription')); ?></span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </td>
                                <td class="px-4 py-3">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($organization->is_active): ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800"><?php echo e(__('superadmin.dashboard.overview.organizations.status_active')); ?></span>
                                    <?php else: ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800"><?php echo e(__('superadmin.dashboard.overview.organizations.status_inactive')); ?></span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700"><?php echo e($organization->created_at->format('M d, Y')); ?></td>
                                <td class="px-4 py-3 text-right text-sm font-medium">
                                    <a href="<?php echo e(route('superadmin.organizations.show', $organization)); ?>" class="text-blue-600 hover:text-blue-800"><?php echo e(__('superadmin.dashboard.overview.organizations.headers.manage')); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="5" class="px-4 py-4 text-center text-slate-500"><?php echo e(__('superadmin.dashboard.overview.organizations.empty')); ?></td>
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

    
    <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['id' => 'resources-overview','class' => 'mb-8']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => 'resources-overview','class' => 'mb-8']); ?>
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-xl font-semibold"><?php echo e(__('superadmin.dashboard.overview.resources.title')); ?></h2>
                <p class="text-slate-500 text-sm"><?php echo e(__('superadmin.dashboard.overview.resources.description')); ?></p>
            </div>
            <a href="<?php echo e(route('superadmin.organizations.index')); ?>" class="text-blue-600 hover:text-blue-800 text-sm font-semibold"><?php echo e(__('superadmin.dashboard.overview.resources.manage_orgs')); ?></a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div id="resource-properties" class="space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold"><?php echo e(__('superadmin.dashboard.overview.resources.properties.title')); ?></h3>
                    <a href="<?php echo e(route('superadmin.organizations.index')); ?>" class="text-sm text-blue-600 hover:text-blue-800"><?php echo e(__('superadmin.dashboard.overview.resources.properties.open_owners')); ?></a>
                </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $latestProperties; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $property): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php $org = $organizationLookup[$property->tenant_id] ?? null; ?>
                    <div class="flex items-start justify-between p-3 bg-slate-50 rounded-xl border border-slate-200">
                        <div>
                            <div class="font-medium text-slate-900"><?php echo e($property->address); ?></div>
                            <div class="text-xs text-slate-500"><?php echo e(__('superadmin.dashboard.overview.resources.properties.building')); ?>: <?php echo e($property->building?->display_name ?? '—'); ?></div>
                            <div class="text-xs text-slate-500"><?php echo e(__('superadmin.dashboard.overview.resources.properties.organization')); ?>: <?php echo e($org->name ?? __('superadmin.dashboard.overview.resources.properties.unknown_org')); ?></div>
                        </div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($org): ?>
                            <a href="<?php echo e(route('superadmin.organizations.show', $org->id)); ?>" class="text-xs font-semibold text-blue-600 hover:text-blue-800"><?php echo e(__('superadmin.dashboard.overview.organizations.headers.manage')); ?></a>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <p class="text-slate-500"><?php echo e(__('superadmin.dashboard.overview.resources.properties.empty')); ?></p>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

            <div id="resource-buildings" class="space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold"><?php echo e(__('superadmin.dashboard.overview.resources.buildings.title')); ?></h3>
                    <a href="<?php echo e(route('superadmin.organizations.index')); ?>" class="text-sm text-blue-600 hover:text-blue-800"><?php echo e(__('superadmin.dashboard.overview.resources.buildings.open_owners')); ?></a>
                </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $latestBuildings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $building): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php $org = $organizationLookup[$building->tenant_id] ?? null; ?>
                    <div class="flex items-start justify-between p-3 bg-slate-50 rounded-xl border border-slate-200">
                        <div>
                            <div class="font-medium text-slate-900"><?php echo e($building->display_name); ?></div>
                            <div class="text-xs text-slate-500"><?php echo e(__('superadmin.dashboard.overview.resources.buildings.address')); ?>: <?php echo e($building->address); ?></div>
                            <div class="text-xs text-slate-500"><?php echo e(__('superadmin.dashboard.overview.resources.buildings.organization')); ?>: <?php echo e($org->name ?? __('superadmin.dashboard.overview.resources.properties.unknown_org')); ?></div>
                        </div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($org): ?>
                            <a href="<?php echo e(route('superadmin.organizations.show', $org->id)); ?>" class="text-xs font-semibold text-blue-600 hover:text-blue-800"><?php echo e(__('superadmin.dashboard.overview.organizations.headers.manage')); ?></a>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <p class="text-slate-500"><?php echo e(__('superadmin.dashboard.overview.resources.buildings.empty')); ?></p>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

            <div id="resource-tenants" class="space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold"><?php echo e(__('superadmin.dashboard.overview.resources.tenants.title')); ?></h3>
                    <a href="<?php echo e(route('superadmin.organizations.index')); ?>" class="text-sm text-blue-600 hover:text-blue-800"><?php echo e(__('superadmin.dashboard.overview.resources.tenants.open_owners')); ?></a>
                </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $latestTenants; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tenant): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php $org = $organizationLookup[$tenant->tenant_id] ?? null; ?>
                    <div class="flex items-start justify-between p-3 bg-slate-50 rounded-xl border border-slate-200">
                        <div>
                            <div class="font-medium text-slate-900"><?php echo e($tenant->name); ?></div>
                            <div class="text-xs text-slate-500"><?php echo e($tenant->email); ?></div>
                            <div class="text-xs text-slate-500"><?php echo e(__('superadmin.dashboard.overview.resources.tenants.property')); ?>: <?php echo e($tenant->property?->address ?? __('superadmin.dashboard.overview.resources.tenants.not_assigned')); ?></div>
                            <div class="text-xs text-slate-500"><?php echo e(__('superadmin.dashboard.overview.resources.tenants.organization')); ?>: <?php echo e($org->name ?? __('superadmin.dashboard.overview.resources.properties.unknown_org')); ?></div>
                        </div>
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo e($tenant->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'); ?>"><?php echo e($tenant->is_active ? __('superadmin.dashboard.overview.resources.tenants.status_active') : __('superadmin.dashboard.overview.resources.tenants.status_inactive')); ?></span>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <p class="text-slate-500"><?php echo e(__('superadmin.dashboard.overview.resources.tenants.empty')); ?></p>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

            <div id="resource-invoices" class="space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold"><?php echo e(__('superadmin.dashboard.overview.resources.invoices.title')); ?></h3>
                    <a href="<?php echo e(route('superadmin.organizations.index')); ?>" class="text-sm text-blue-600 hover:text-blue-800"><?php echo e(__('superadmin.dashboard.overview.resources.invoices.open_owners')); ?></a>
                </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $latestInvoices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $invoice): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php $org = $organizationLookup[$invoice->tenant_id] ?? null; ?>
                    <div class="flex items-start justify-between p-3 bg-slate-50 rounded-xl border border-slate-200">
                        <div>
                            <div class="font-medium text-slate-900"><?php echo e($invoice->tenant?->name ?? __('tenants.labels.name')); ?></div>
                            <div class="text-xs text-slate-500"><?php echo e(__('superadmin.dashboard.overview.resources.invoices.amount')); ?>: <?php echo e(number_format($invoice->total_amount, 2)); ?></div>
                            <div class="text-xs text-slate-500"><?php echo e(__('superadmin.dashboard.overview.resources.invoices.status')); ?>: <?php echo e(enum_label($invoice->status, \App\Enums\InvoiceStatus::class)); ?></div>
                            <div class="text-xs text-slate-500"><?php echo e(__('superadmin.dashboard.overview.resources.invoices.organization')); ?>: <?php echo e($org->name ?? __('superadmin.dashboard.overview.resources.properties.unknown_org')); ?></div>
                        </div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($org): ?>
                            <a href="<?php echo e(route('superadmin.organizations.show', $org->id)); ?>" class="text-xs font-semibold text-blue-600 hover:text-blue-800"><?php echo e(__('superadmin.dashboard.overview.resources.invoices.manage')); ?></a>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <p class="text-slate-500"><?php echo e(__('superadmin.dashboard.overview.resources.invoices.empty')); ?></p>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
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

    
    <?php if (isset($component)) { $__componentOriginal53747ceb358d30c0105769f8471417f6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal53747ceb358d30c0105769f8471417f6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => ['class' => 'mb-8','id' => 'analytics']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'mb-8','id' => 'analytics']); ?>
        <h2 class="text-xl font-semibold mb-2"><?php echo e(__('superadmin.dashboard.analytics.title')); ?></h2>
        <p class="text-slate-500"><?php echo e(__('superadmin.dashboard.analytics.empty')); ?></p>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.card','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
        <h2 class="text-xl font-semibold mb-4"><?php echo e(__('superadmin.dashboard.quick_actions.title')); ?></h2>
        <div class="flex flex-wrap gap-4">
            <a href="<?php echo e(route('superadmin.organizations.create')); ?>" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                <span class="mr-2">➕</span>
                <?php echo e(__('superadmin.dashboard.quick_actions.create_organization')); ?>

            </a>
            <a href="<?php echo e(route('superadmin.subscriptions.index')); ?>" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                <span class="mr-2">🧾</span>
                <?php echo e(__('superadmin.dashboard.quick_actions.create_subscription')); ?>

            </a>
            <a href="#recent-activity" class="inline-flex items-center px-4 py-2 bg-slate-600 text-white rounded hover:bg-slate-700">
                <span class="mr-2">🕒</span>
                <?php echo e(__('superadmin.dashboard.quick_actions.view_all_activity')); ?>

            </a>
            <a href="<?php echo e(route('superadmin.organizations.index')); ?>" class="inline-flex items-center px-4 py-2 bg-slate-600 text-white rounded hover:bg-slate-700">
                <span class="mr-2">🏢</span>
                <?php echo e(__('superadmin.dashboard.quick_actions.manage_organizations')); ?>

            </a>
            <a href="<?php echo e(route('superadmin.subscriptions.index')); ?>" class="inline-flex items-center px-4 py-2 bg-slate-600 text-white rounded hover:bg-slate-700">
                <span class="mr-2">📊</span>
                <?php echo e(__('superadmin.dashboard.quick_actions.manage_subscriptions')); ?>

            </a>
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

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\www\rent_counter\resources\views/superadmin/dashboard.blade.php ENDPATH**/ ?>