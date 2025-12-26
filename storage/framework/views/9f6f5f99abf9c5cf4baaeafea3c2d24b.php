<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', __('app.meta.default_title')); ?></title>

    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>

    <?php echo $__env->yieldPushContent('styles'); ?>
</head>
<body class="text-slate-900 antialiased">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:z-50 focus:top-4 focus:left-4 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:shadow-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
        <?php echo e(__('app.accessibility.skip_to_content')); ?>

    </a>
    <div id="app">
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(app(\App\Services\ImpersonationService::class)->isImpersonating()): ?>
            <?php if (isset($component)) { $__componentOriginal81fc2c1cb3a33996210a2d0eb6512684 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal81fc2c1cb3a33996210a2d0eb6512684 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.impersonation-banner','data' => ['impersonationService' => app(\App\Services\ImpersonationService::class)]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('impersonation-banner'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['impersonationService' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(app(\App\Services\ImpersonationService::class))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal81fc2c1cb3a33996210a2d0eb6512684)): ?>
<?php $attributes = $__attributesOriginal81fc2c1cb3a33996210a2d0eb6512684; ?>
<?php unset($__attributesOriginal81fc2c1cb3a33996210a2d0eb6512684); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal81fc2c1cb3a33996210a2d0eb6512684)): ?>
<?php $component = $__componentOriginal81fc2c1cb3a33996210a2d0eb6512684; ?>
<?php unset($__componentOriginal81fc2c1cb3a33996210a2d0eb6512684); ?>
<?php endif; ?>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <!-- Navigation -->
        <nav class="sticky top-0 z-40 border-b border-white/40 bg-white/80 backdrop-blur-xl shadow-[0_10px_50px_rgba(15,23,42,0.08)]" x-data="{ mobileMenuOpen: false }">
            <div class="absolute inset-0 bg-gradient-to-r from-indigo-600/15 via-sky-500/10 to-indigo-500/15"></div>
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center gap-3">
                        <a href="<?php echo e(url('/')); ?>" class="flex items-center gap-3 group">
                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 to-sky-400 text-white font-display text-lg shadow-glow transition-transform duration-300">V</span>
                            <div class="leading-tight">
                                <p class="text-[11px] uppercase tracking-[0.22em] text-slate-500"><?php echo e(__('app.brand.name')); ?></p>
                                <p class="font-display text-lg text-slate-900"><?php echo e(__('app.brand.product')); ?></p>
                            </div>
                        </a>
                    </div>

                    <!-- Desktop Navigation -->
                    <div class="hidden md:flex md:items-center md:space-x-1">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->guard()->check()): ?>
                            
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($userRole === 'superadmin'): ?>
                                <a href="<?php echo e(route('superadmin.dashboard')); ?>" class="<?php echo e(str_starts_with($currentRoute, 'superadmin.dashboard') ? $activeClass : $inactiveClass); ?> px-3 py-2 rounded-lg text-sm font-semibold inline-flex items-center transition">
                                    <?php echo e(__('app.nav.dashboard')); ?>

                                </a>
                                <a href="<?php echo e(route('superadmin.organizations.index')); ?>" class="<?php echo e(str_starts_with($currentRoute, 'superadmin.organizations') ? $activeClass : $inactiveClass); ?> px-3 py-2 rounded-lg text-sm font-semibold inline-flex items-center transition">
                                    <?php echo e(__('app.nav.organizations')); ?>

                                </a>
                                <a href="<?php echo e(route('superadmin.buildings.index')); ?>" class="<?php echo e(str_starts_with($currentRoute, 'superadmin.buildings') ? $activeClass : $inactiveClass); ?> px-3 py-2 rounded-lg text-sm font-semibold inline-flex items-center transition">
                                    <?php echo e(__('app.nav.buildings')); ?>

                                </a>
                                <a href="<?php echo e(route('superadmin.properties.index')); ?>" class="<?php echo e(str_starts_with($currentRoute, 'superadmin.properties') ? $activeClass : $inactiveClass); ?> px-3 py-2 rounded-lg text-sm font-semibold inline-flex items-center transition">
                                    <?php echo e(__('app.nav.properties')); ?>

                                </a>
                                <a href="<?php echo e(route('superadmin.tenants.index')); ?>" class="<?php echo e(str_starts_with($currentRoute, 'superadmin.tenants') ? $activeClass : $inactiveClass); ?> px-3 py-2 rounded-lg text-sm font-semibold inline-flex items-center transition">
                                    <?php echo e(__('app.nav.tenants')); ?>

                                </a>
                                <a href="<?php echo e(route('superadmin.managers.index')); ?>" class="<?php echo e(str_starts_with($currentRoute, 'superadmin.managers') ? $activeClass : $inactiveClass); ?> px-3 py-2 rounded-lg text-sm font-semibold inline-flex items-center transition">
                                    <?php echo e(__('app.nav.managers')); ?>

                                </a>
                                <a href="<?php echo e(route('superadmin.subscriptions.index')); ?>" class="<?php echo e(str_starts_with($currentRoute, 'superadmin.subscriptions') ? $activeClass : $inactiveClass); ?> px-3 py-2 rounded-lg text-sm font-semibold inline-flex items-center transition">
                                    <?php echo e(__('app.nav.subscriptions')); ?>

                                </a>
                                <a href="<?php echo e(route('superadmin.profile.show')); ?>" class="<?php echo e(str_starts_with($currentRoute, 'superadmin.profile') ? $activeClass : $inactiveClass); ?> px-3 py-2 rounded-lg text-sm font-semibold inline-flex items-center transition">
                                    <?php echo e(__('app.nav.profile')); ?>

                                </a>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                            
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($userRole === 'admin'): ?>
                                <a href="<?php echo e(route('admin.dashboard')); ?>" class="<?php echo e(str_starts_with($currentRoute, 'admin.dashboard') ? $activeClass : $inactiveClass); ?> px-3 py-2 rounded-lg text-sm font-semibold inline-flex items-center transition">
                                    <?php echo e(__('app.nav.dashboard')); ?>

                                </a>
                                <a href="<?php echo e(route('admin.users.index')); ?>" class="<?php echo e(str_starts_with($currentRoute, 'admin.users') ? $activeClass : $inactiveClass); ?> px-3 py-2 rounded-lg text-sm font-semibold inline-flex items-center transition">
                                    <?php echo e(__('app.nav.users')); ?>

                                </a>
                                <a href="<?php echo e(route('admin.providers.index')); ?>" class="<?php echo e(str_starts_with($currentRoute, 'admin.providers') ? $activeClass : $inactiveClass); ?> px-3 py-2 rounded-lg text-sm font-semibold inline-flex items-center transition">
                                    <?php echo e(__('app.nav.providers')); ?>

                                </a>
                                <a href="<?php echo e(route('admin.tariffs.index')); ?>" class="<?php echo e(str_starts_with($currentRoute, 'admin.tariffs') ? $activeClass : $inactiveClass); ?> px-3 py-2 rounded-lg text-sm font-semibold inline-flex items-center transition">
                                    <?php echo e(__('app.nav.tariffs')); ?>

                                </a>
                                <a href="<?php echo e(route('admin.settings.index')); ?>" class="<?php echo e(str_starts_with($currentRoute, 'admin.settings') ? $activeClass : $inactiveClass); ?> px-3 py-2 rounded-lg text-sm font-semibold inline-flex items-center transition">
                                    <?php echo e(__('app.nav.settings')); ?>

                                </a>
                                <a href="<?php echo e(route('admin.audit.index')); ?>" class="<?php echo e(str_starts_with($currentRoute, 'admin.audit') ? $activeClass : $inactiveClass); ?> px-3 py-2 rounded-lg text-sm font-semibold inline-flex items-center transition">
                                    <?php echo e(__('app.nav.audit')); ?>

                                </a>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                            
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($userRole === 'manager'): ?>
                                <a href="<?php echo e(route('manager.dashboard')); ?>" class="<?php echo e(str_starts_with($currentRoute, 'manager.dashboard') ? $activeClass : $inactiveClass); ?> px-3 py-2 rounded-lg text-sm font-semibold inline-flex items-center transition">
                                    <?php echo e(__('app.nav.dashboard')); ?>

                                </a>
                                <a href="<?php echo e(route('manager.properties.index')); ?>" class="<?php echo e(str_starts_with($currentRoute, 'manager.properties') ? $activeClass : $inactiveClass); ?> px-3 py-2 rounded-lg text-sm font-semibold inline-flex items-center transition">
                                    <?php echo e(__('app.nav.properties')); ?>

                                </a>
                                <a href="<?php echo e(route('manager.buildings.index')); ?>" class="<?php echo e(str_starts_with($currentRoute, 'manager.buildings') ? $activeClass : $inactiveClass); ?> px-3 py-2 rounded-lg text-sm font-semibold inline-flex items-center transition">
                                    <?php echo e(__('app.nav.buildings')); ?>

                                </a>
                                <a href="<?php echo e(route('manager.meters.index')); ?>" class="<?php echo e(str_starts_with($currentRoute, 'manager.meters') ? $activeClass : $inactiveClass); ?> px-3 py-2 rounded-lg text-sm font-semibold inline-flex items-center transition">
                                    <?php echo e(__('app.nav.meters')); ?>

                                </a>
                                <a href="<?php echo e(route('manager.meter-readings.index')); ?>" class="<?php echo e(str_starts_with($currentRoute, 'manager.meter-readings') ? $activeClass : $inactiveClass); ?> px-3 py-2 rounded-lg text-sm font-semibold inline-flex items-center transition">
                                    <?php echo e(__('app.nav.readings')); ?>

                                </a>
                                <a href="<?php echo e(route('manager.invoices.index')); ?>" class="<?php echo e(str_starts_with($currentRoute, 'manager.invoices') ? $activeClass : $inactiveClass); ?> px-3 py-2 rounded-lg text-sm font-semibold inline-flex items-center transition">
                                    <?php echo e(__('app.nav.invoices')); ?>

                                </a>
                                <a href="<?php echo e(route('manager.reports.index')); ?>" class="<?php echo e(str_starts_with($currentRoute, 'manager.reports') ? $activeClass : $inactiveClass); ?> px-3 py-2 rounded-lg text-sm font-semibold inline-flex items-center transition">
                                    <?php echo e(__('app.nav.reports')); ?>

                                </a>
                                <a href="<?php echo e(route('manager.profile.show')); ?>" class="<?php echo e(str_starts_with($currentRoute, 'manager.profile') ? $activeClass : $inactiveClass); ?> px-3 py-2 rounded-lg text-sm font-semibold inline-flex items-center transition">
                                    <?php echo e(__('app.nav.profile')); ?>

                                </a>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                            
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($userRole === 'tenant'): ?>
                                <a href="<?php echo e(route('tenant.dashboard')); ?>" class="<?php echo e(str_starts_with($currentRoute, 'tenant.dashboard') ? $activeClass : $inactiveClass); ?> px-3 py-2 rounded-lg text-sm font-semibold inline-flex items-center transition">
                                    <?php echo e(__('app.nav.dashboard')); ?>

                                </a>
                                <a href="<?php echo e(route('tenant.property.show')); ?>" class="<?php echo e(str_starts_with($currentRoute, 'tenant.property') ? $activeClass : $inactiveClass); ?> px-3 py-2 rounded-lg text-sm font-semibold inline-flex items-center transition">
                                    <?php echo e(__('app.nav.properties')); ?>

                                </a>
                                <a href="<?php echo e(route('tenant.meters.index')); ?>" class="<?php echo e(str_starts_with($currentRoute, 'tenant.meters') ? $activeClass : $inactiveClass); ?> px-3 py-2 rounded-lg text-sm font-semibold inline-flex items-center transition">
                                    <?php echo e(__('app.nav.meters')); ?>

                                </a>
                                <a href="<?php echo e(route('tenant.meter-readings.index')); ?>" class="<?php echo e(str_starts_with($currentRoute, 'tenant.meter-readings') ? $activeClass : $inactiveClass); ?> px-3 py-2 rounded-lg text-sm font-semibold inline-flex items-center transition">
                                    <?php echo e(__('app.nav.readings')); ?>

                                </a>
                                <a href="<?php echo e(route('tenant.invoices.index')); ?>" class="<?php echo e(str_starts_with($currentRoute, 'tenant.invoices') ? $activeClass : $inactiveClass); ?> px-3 py-2 rounded-lg text-sm font-semibold inline-flex items-center transition">
                                    <?php echo e(__('app.nav.invoices')); ?>

                                </a>
                                <a href="<?php echo e(route('tenant.profile.show')); ?>" class="<?php echo e(str_starts_with($currentRoute, 'tenant.profile') ? $activeClass : $inactiveClass); ?> px-3 py-2 rounded-lg text-sm font-semibold inline-flex items-center transition">
                                    <?php echo e(__('app.nav.profile')); ?>

                                </a>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->guard()->check()): ?>
                        <div class="hidden md:flex md:items-center md:gap-3">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showTopLocaleSwitcher): ?>
                                <form method="POST" action="<?php echo e(route('locale.set')); ?>">
                                    <?php echo csrf_field(); ?>
                                    <select name="locale" onchange="this.form.submit()" class="bg-white/80 border border-slate-200 text-sm rounded-lg px-2.5 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $languages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $language): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($language->code); ?>" <?php echo e($language->code === $currentLocale ? 'selected' : ''); ?>>
                                                <?php echo e($language->native_name ?? $language->name); ?>

                                            </option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </select>
                                </form>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            <form method="POST" action="<?php echo e(route('logout')); ?>" class="inline">
                                <?php echo csrf_field(); ?>
                                <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white/80 px-3 py-2 text-sm font-semibold text-slate-700 transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6A2.25 2.25 0 005.25 5.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12" />
                                    </svg>
                                    <?php echo e(__('app.nav.logout')); ?>

                                </button>
                            </form>
                        </div>

                        <!-- Mobile menu button -->
                        <div class="flex items-center md:hidden">
                            <button @click="mobileMenuOpen = !mobileMenuOpen" type="button" class="inline-flex items-center justify-center p-2 rounded-md text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <span class="sr-only"><?php echo e(__('app.accessibility.open_menu')); ?></span>
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                                </svg>
                            </button>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>

            <!-- Mobile menu -->
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->guard()->check()): ?>
            <div x-show="mobileMenuOpen" x-transition class="md:hidden bg-white/95 backdrop-blur border-t border-slate-200 shadow-lg">
                <div class="space-y-1 px-4 pb-4 pt-3">
                    
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($userRole === 'superadmin'): ?>
                            <a href="<?php echo e(route('superadmin.dashboard')); ?>" class="<?php echo e(str_starts_with($currentRoute, 'superadmin.dashboard') ? $mobileActiveClass : $mobileInactiveClass); ?> block px-3 py-2 rounded-lg text-base font-semibold">
                                <?php echo e(__('app.nav.dashboard')); ?>

                            </a>
                            <a href="<?php echo e(route('superadmin.organizations.index')); ?>" class="<?php echo e(str_starts_with($currentRoute, 'superadmin.organizations') ? $mobileActiveClass : $mobileInactiveClass); ?> block px-3 py-2 rounded-lg text-base font-semibold">
                                <?php echo e(__('app.nav.organizations')); ?>

                            </a>
                            <a href="<?php echo e(route('superadmin.buildings.index')); ?>" class="<?php echo e(str_starts_with($currentRoute, 'superadmin.buildings') ? $mobileActiveClass : $mobileInactiveClass); ?> block px-3 py-2 rounded-lg text-base font-semibold">
                                <?php echo e(__('app.nav.buildings')); ?>

                            </a>
                            <a href="<?php echo e(route('superadmin.properties.index')); ?>" class="<?php echo e(str_starts_with($currentRoute, 'superadmin.properties') ? $mobileActiveClass : $mobileInactiveClass); ?> block px-3 py-2 rounded-lg text-base font-semibold">
                                <?php echo e(__('app.nav.properties')); ?>

                            </a>
                            <a href="<?php echo e(route('superadmin.tenants.index')); ?>" class="<?php echo e(str_starts_with($currentRoute, 'superadmin.tenants') ? $mobileActiveClass : $mobileInactiveClass); ?> block px-3 py-2 rounded-lg text-base font-semibold">
                                <?php echo e(__('app.nav.tenants')); ?>

                            </a>
                            <a href="<?php echo e(route('superadmin.managers.index')); ?>" class="<?php echo e(str_starts_with($currentRoute, 'superadmin.managers') ? $mobileActiveClass : $mobileInactiveClass); ?> block px-3 py-2 rounded-lg text-base font-semibold">
                                <?php echo e(__('app.nav.managers')); ?>

                        </a>
                        <a href="<?php echo e(route('superadmin.subscriptions.index')); ?>" class="<?php echo e(str_starts_with($currentRoute, 'superadmin.subscriptions') ? $mobileActiveClass : $mobileInactiveClass); ?> block px-3 py-2 rounded-lg text-base font-semibold">
                            <?php echo e(__('app.nav.subscriptions')); ?>

                        </a>
                        <a href="<?php echo e(route('superadmin.profile.show')); ?>" class="<?php echo e(str_starts_with($currentRoute, 'superadmin.profile') ? $mobileActiveClass : $mobileInactiveClass); ?> block px-3 py-2 rounded-lg text-base font-semibold">
                            <?php echo e(__('app.nav.profile')); ?>

                        </a>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($userRole === 'admin'): ?>
                        <a href="<?php echo e(route('admin.dashboard')); ?>" class="<?php echo e(str_starts_with($currentRoute, 'admin.dashboard') ? $mobileActiveClass : $mobileInactiveClass); ?> block px-3 py-2 rounded-lg text-base font-semibold">
                            <?php echo e(__('app.nav.dashboard')); ?>

                        </a>
                        <a href="<?php echo e(route('admin.users.index')); ?>" class="<?php echo e(str_starts_with($currentRoute, 'admin.users') ? $mobileActiveClass : $mobileInactiveClass); ?> block px-3 py-2 rounded-lg text-base font-semibold">
                            <?php echo e(__('app.nav.users')); ?>

                        </a>
                        <a href="<?php echo e(route('admin.providers.index')); ?>" class="<?php echo e(str_starts_with($currentRoute, 'admin.providers') ? $mobileActiveClass : $mobileInactiveClass); ?> block px-3 py-2 rounded-lg text-base font-semibold">
                            <?php echo e(__('app.nav.providers')); ?>

                        </a>
                        <a href="<?php echo e(route('admin.tariffs.index')); ?>" class="<?php echo e(str_starts_with($currentRoute, 'admin.tariffs') ? $mobileActiveClass : $mobileInactiveClass); ?> block px-3 py-2 rounded-lg text-base font-semibold">
                            <?php echo e(__('app.nav.tariffs')); ?>

                        </a>
                        <a href="<?php echo e(route('admin.settings.index')); ?>" class="<?php echo e(str_starts_with($currentRoute, 'admin.settings') ? $mobileActiveClass : $mobileInactiveClass); ?> block px-3 py-2 rounded-lg text-base font-semibold">
                            <?php echo e(__('app.nav.settings')); ?>

                        </a>
                        <a href="<?php echo e(route('admin.audit.index')); ?>" class="<?php echo e(str_starts_with($currentRoute, 'admin.audit') ? $mobileActiveClass : $mobileInactiveClass); ?> block px-3 py-2 rounded-lg text-base font-semibold">
                            <?php echo e(__('app.nav.audit')); ?>

                        </a>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($userRole === 'manager'): ?>
                        <a href="<?php echo e(route('manager.dashboard')); ?>" class="<?php echo e(str_starts_with($currentRoute, 'manager.dashboard') ? $mobileActiveClass : $mobileInactiveClass); ?> block px-3 py-2 rounded-lg text-base font-semibold">
                            <?php echo e(__('app.nav.dashboard')); ?>

                        </a>
                        <a href="<?php echo e(route('manager.properties.index')); ?>" class="<?php echo e(str_starts_with($currentRoute, 'manager.properties') ? $mobileActiveClass : $mobileInactiveClass); ?> block px-3 py-2 rounded-lg text-base font-semibold">
                            <?php echo e(__('app.nav.properties')); ?>

                        </a>
                        <a href="<?php echo e(route('manager.buildings.index')); ?>" class="<?php echo e(str_starts_with($currentRoute, 'manager.buildings') ? $mobileActiveClass : $mobileInactiveClass); ?> block px-3 py-2 rounded-lg text-base font-semibold">
                            <?php echo e(__('app.nav.buildings')); ?>

                        </a>
                        <a href="<?php echo e(route('manager.meters.index')); ?>" class="<?php echo e(str_starts_with($currentRoute, 'manager.meters') ? $mobileActiveClass : $mobileInactiveClass); ?> block px-3 py-2 rounded-lg text-base font-semibold">
                            <?php echo e(__('app.nav.meters')); ?>

                        </a>
                        <a href="<?php echo e(route('manager.meter-readings.index')); ?>" class="<?php echo e(str_starts_with($currentRoute, 'manager.meter-readings') ? $mobileActiveClass : $mobileInactiveClass); ?> block px-3 py-2 rounded-lg text-base font-semibold">
                            <?php echo e(__('app.nav.readings')); ?>

                        </a>
                        <a href="<?php echo e(route('manager.invoices.index')); ?>" class="<?php echo e(str_starts_with($currentRoute, 'manager.invoices') ? $mobileActiveClass : $mobileInactiveClass); ?> block px-3 py-2 rounded-lg text-base font-semibold">
                            <?php echo e(__('app.nav.invoices')); ?>

                        </a>
                        <a href="<?php echo e(route('manager.reports.index')); ?>" class="<?php echo e(str_starts_with($currentRoute, 'manager.reports') ? $mobileActiveClass : $mobileInactiveClass); ?> block px-3 py-2 rounded-lg text-base font-semibold">
                            <?php echo e(__('app.nav.reports')); ?>

                        </a>
                        <a href="<?php echo e(route('manager.profile.show')); ?>" class="<?php echo e(str_starts_with($currentRoute, 'manager.profile') ? $mobileActiveClass : $mobileInactiveClass); ?> block px-3 py-2 rounded-lg text-base font-semibold">
                            <?php echo e(__('app.nav.profile')); ?>

                        </a>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($userRole === 'tenant'): ?>
                        <a href="<?php echo e(route('tenant.dashboard')); ?>" class="<?php echo e(str_starts_with($currentRoute, 'tenant.dashboard') ? $mobileActiveClass : $mobileInactiveClass); ?> block px-3 py-2 rounded-lg text-base font-semibold">
                            <?php echo e(__('app.nav.dashboard')); ?>

                        </a>
                        <a href="<?php echo e(route('tenant.property.show')); ?>" class="<?php echo e(str_starts_with($currentRoute, 'tenant.property') ? $mobileActiveClass : $mobileInactiveClass); ?> block px-3 py-2 rounded-lg text-base font-semibold">
                            <?php echo e(__('app.nav.properties')); ?>

                        </a>
                        <a href="<?php echo e(route('tenant.meters.index')); ?>" class="<?php echo e(str_starts_with($currentRoute, 'tenant.meters') ? $mobileActiveClass : $mobileInactiveClass); ?> block px-3 py-2 rounded-lg text-base font-semibold">
                            <?php echo e(__('app.nav.meters')); ?>

                        </a>
                        <a href="<?php echo e(route('tenant.meter-readings.index')); ?>" class="<?php echo e(str_starts_with($currentRoute, 'tenant.meter-readings') ? $mobileActiveClass : $mobileInactiveClass); ?> block px-3 py-2 rounded-lg text-base font-semibold">
                            <?php echo e(__('app.nav.readings')); ?>

                        </a>
                        <a href="<?php echo e(route('tenant.invoices.index')); ?>" class="<?php echo e(str_starts_with($currentRoute, 'tenant.invoices') ? $mobileActiveClass : $mobileInactiveClass); ?> block px-3 py-2 rounded-lg text-base font-semibold">
                            <?php echo e(__('app.nav.invoices')); ?>

                        </a>
                        <a href="<?php echo e(route('tenant.profile.show')); ?>" class="<?php echo e(str_starts_with($currentRoute, 'tenant.profile') ? $mobileActiveClass : $mobileInactiveClass); ?> block px-3 py-2 rounded-lg text-base font-semibold">
                            <?php echo e(__('app.nav.profile')); ?>

                        </a>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    <div class="border-t border-slate-200 pt-2 mt-2">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showTopLocaleSwitcher): ?>
                            <form method="POST" action="<?php echo e(route('locale.set')); ?>" class="px-3 py-2">
                                <?php echo csrf_field(); ?>
                                <select name="locale" onchange="this.form.submit()" class="w-full bg-white border border-slate-200 text-sm rounded-lg px-2.5 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $languages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $language): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($language->code); ?>" <?php echo e($language->code === $currentLocale ? 'selected' : ''); ?>>
                                            <?php echo e($language->native_name ?? $language->name); ?>

                                        </option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </select>
                            </form>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <form method="POST" action="<?php echo e(route('logout')); ?>">
                            <?php echo csrf_field(); ?>
                            <button type="submit" class="text-slate-700 block w-full text-left px-3 py-2 rounded-lg text-base font-semibold">
                                <?php echo e(__('app.nav.logout')); ?>

                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </nav>

        <!-- Flash Messages -->
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('success')): ?>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)">
            <div class="relative overflow-hidden rounded-2xl border border-emerald-200/80 bg-white/85 shadow-lg shadow-emerald-200/40" role="status" aria-live="polite">
                <div class="absolute inset-0 bg-gradient-to-r from-emerald-50 via-white to-emerald-50"></div>
                <div class="relative flex items-start gap-3 p-4">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7" />
                        </svg>
                    </div>
                    <div class="flex-1 text-sm text-emerald-900">
                        <?php echo e(session('success')); ?>

                    </div>
                    <button @click="show = false" class="text-emerald-500 focus:outline-none">
                        <span class="sr-only"><?php echo e(__('app.accessibility.dismiss')); ?></span>
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M6 18L18 6" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('error')): ?>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)">
            <div class="relative overflow-hidden rounded-2xl border border-rose-200/90 bg-white/90 shadow-lg shadow-rose-200/50" role="alert" aria-live="polite">
                <div class="absolute inset-0 bg-gradient-to-r from-rose-50 via-white to-rose-50"></div>
                <div class="relative flex items-start gap-3 p-4">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-rose-100 text-rose-700">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M4.93 4.93l14.14 14.14M4.93 19.07 19.07 4.93" />
                        </svg>
                    </div>
                    <div class="flex-1 text-sm text-rose-900">
                        <?php echo e(session('error')); ?>

                    </div>
                    <button @click="show = false" class="text-rose-500 focus:outline-none">
                        <span class="sr-only"><?php echo e(__('app.accessibility.dismiss')); ?></span>
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M6 18L18 6" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <!-- Main Content -->
        <main id="main-content" class="py-10 relative" role="main" aria-label="Main content">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            </div>
            
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <?php echo $__env->yieldContent('content'); ?>
            </div>
        </main>
    </div>

    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH C:\www\rent_counter\resources\views/layouts/app.blade.php ENDPATH**/ ?>