<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo e(__('app.brand.name')); ?> · <?php echo e(__('app.brand.product')); ?></title>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
</head>
<body class="bg-slate-950 text-slate-50 antialiased">

<div class="relative overflow-hidden min-h-screen">
    <div class="pointer-events-none absolute inset-0">
        <div class="absolute -left-24 -top-32 h-80 w-80 rounded-full bg-indigo-500/30 blur-[120px]"></div>
        <div class="absolute right-0 top-10 h-72 w-72 rounded-full bg-sky-400/25 blur-[110px]"></div>
        <div class="absolute inset-0 bg-gradient-to-b from-slate-900 via-slate-950 to-slate-950"></div>
    </div>

    <header class="relative max-w-6xl mx-auto px-6 pt-10 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 to-sky-400 text-white font-display text-xl shadow-glow">V</span>
            <div class="leading-tight">
                <p class="text-[11px] uppercase tracking-[0.22em] text-slate-300"><?php echo e(__('app.brand.name')); ?></p>
                <p class="font-display text-lg text-white"><?php echo e(__('app.brand.product')); ?></p>
            </div>
        </div>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($canLogin): ?>
            <div class="flex items-center gap-3">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($canSwitchLocale): ?>
                    <?php if (isset($component)) { $__componentOriginalca52de3bb9c3312a4c9c230381dba9e1 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalca52de3bb9c3312a4c9c230381dba9e1 = $attributes; } ?>
<?php $component = App\View\Components\LanguageSwitcher::resolve(['variant' => 'select','class' => 'hidden sm:block','languages' => $languages,'currentLocale' => $currentLocale] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('language-switcher'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\LanguageSwitcher::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalca52de3bb9c3312a4c9c230381dba9e1)): ?>
<?php $attributes = $__attributesOriginalca52de3bb9c3312a4c9c230381dba9e1; ?>
<?php unset($__attributesOriginalca52de3bb9c3312a4c9c230381dba9e1); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalca52de3bb9c3312a4c9c230381dba9e1)): ?>
<?php $component = $__componentOriginalca52de3bb9c3312a4c9c230381dba9e1; ?>
<?php unset($__componentOriginalca52de3bb9c3312a4c9c230381dba9e1); ?>
<?php endif; ?>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <a href="<?php echo e(route('login')); ?>" class="text-sm font-semibold text-slate-200 hover:text-white"><?php echo e(__('app.cta.login')); ?></a>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($canRegister): ?>
                    <a href="<?php echo e(route('register')); ?>" class="inline-flex items-center gap-2 rounded-xl bg-white text-slate-900 px-4 py-2 text-sm font-semibold shadow-lg shadow-white/20 transition">
                        <?php echo e(__('app.cta.register')); ?>

                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4.5 12h15m0 0-6.75-6.75M19.5 12l-6.75 6.75" />
                        </svg>
                    </a>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </header>

    <main class="relative max-w-6xl mx-auto px-6 pb-16">
        <section class="grid lg:grid-cols-2 gap-12 pt-16">
            <div class="space-y-6">
                <p class="inline-flex items-center gap-2 rounded-full bg-white/5 px-4 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-sky-200 ring-1 ring-white/10">
                    <?php echo e(__('landing.hero.badge')); ?>

                </p>
                <h1 class="font-display text-4xl sm:text-5xl font-bold text-white leading-tight">
                    <?php echo e(__('landing.hero.title')); ?>

                </h1>
                <p class="text-lg text-slate-300 leading-relaxed">
                    <?php echo e(__('landing.hero.tagline')); ?>

                </p>

                <div class="flex flex-wrap gap-3">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($canLogin): ?>
                        <a href="<?php echo e(route('login')); ?>" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-indigo-500 to-sky-500 px-5 py-3 text-sm font-semibold text-white shadow-glow transition">
                            <?php echo e(__('app.cta.go_to_app')); ?>

                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13.5 4.5 21 12l-7.5 7.5M21 12H3" />
                            </svg>
                        </a>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($canRegister): ?>
                        <a href="<?php echo e(route('register')); ?>" class="inline-flex items-center gap-2 rounded-xl border border-white/20 px-5 py-3 text-sm font-semibold text-white/90 backdrop-blur transition hover:border-white/40">
                            <?php echo e(__('app.cta.create_account')); ?>

                        </a>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm text-slate-300">
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                        <p class="text-2xl font-display text-white"><?php echo e(__('landing.metric_values.five_minutes')); ?></p>
                        <p class="text-slate-400"><?php echo e(__('landing.metrics.cache')); ?></p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                        <p class="text-2xl font-display text-white"><?php echo e(__('landing.metric_values.zero')); ?></p>
                        <p class="text-slate-400"><?php echo e(__('landing.metrics.readings')); ?></p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                        <p class="text-2xl font-display text-white"><?php echo e(__('landing.metric_values.full')); ?></p>
                        <p class="text-slate-400"><?php echo e(__('landing.metrics.isolation')); ?></p>
                    </div>
                </div>
            </div>

            <div class="relative">
                <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/20 via-sky-400/15 to-transparent blur-3xl"></div>
                <div class="relative rounded-3xl border border-white/10 bg-white/5 p-6 shadow-[0_24px_80px_rgba(0,0,0,0.35)] backdrop-blur">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-300"><?php echo e(__('landing.dashboard.live_overview')); ?></p>
                            <p class="mt-2 text-xl font-display text-white"><?php echo e(__('landing.dashboard.portfolio_health')); ?></p>
                        </div>
                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-500/15 px-3 py-1 text-xs font-semibold text-emerald-200 ring-1 ring-emerald-500/30">
                            <?php echo e(__('landing.dashboard.healthy')); ?>

                        </span>
                    </div>
                    <div class="mt-6 grid grid-cols-2 gap-4">
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <p class="text-sm text-slate-300"><?php echo e(__('landing.dashboard.draft_invoices')); ?></p>
                            <p class="mt-1 text-3xl font-display text-white">42</p>
                            <p class="text-xs text-slate-400"><?php echo e(__('landing.dashboard.draft_invoices_hint')); ?></p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <p class="text-sm text-slate-300"><?php echo e(__('landing.dashboard.meters_validated')); ?></p>
                            <p class="mt-1 text-3xl font-display text-white">98%</p>
                            <p class="text-xs text-slate-400"><?php echo e(__('landing.dashboard.meters_validated_hint')); ?></p>
                        </div>
                        <div class="col-span-2 rounded-2xl border border-white/10 bg-white/5 p-4">
                            <p class="text-sm text-slate-300 mb-2"><?php echo e(__('landing.dashboard.recent_readings')); ?></p>
                            <div class="grid grid-cols-3 gap-3 text-xs text-slate-300">
                                <div class="rounded-xl bg-white/5 px-3 py-2 border border-white/5">
                                    <p class="font-semibold text-white"><?php echo e(__('landing.dashboard.water')); ?></p>
                                    <p class="text-slate-400"><?php echo e(__('landing.dashboard.water_status')); ?></p>
                                </div>
                                <div class="rounded-xl bg-white/5 px-3 py-2 border border-white/5">
                                    <p class="font-semibold text-white"><?php echo e(__('landing.dashboard.electricity')); ?></p>
                                    <p class="text-slate-400"><?php echo e(__('landing.dashboard.electricity_status')); ?></p>
                                </div>
                                <div class="rounded-xl bg-white/5 px-3 py-2 border border-white/5">
                                    <p class="font-semibold text-white"><?php echo e(__('landing.dashboard.heating')); ?></p>
                                    <p class="text-slate-400"><?php echo e(__('landing.dashboard.heating_status')); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="features" class="mt-20 space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs uppercase tracking-[0.22em] text-slate-400"><?php echo e(__('landing.features_subtitle')); ?></p>
                    <h2 class="mt-2 text-3xl font-display font-bold text-white"><?php echo e(__('landing.features_title')); ?></h2>
                </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($canRegister): ?>
                    <a href="<?php echo e(route('register')); ?>" class="hidden sm:inline-flex items-center gap-2 rounded-xl border border-white/15 px-4 py-2 text-sm font-semibold text-white/90 hover:border-white/30">
                        <?php echo e(__('app.cta.start_now')); ?>

                    </a>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $features; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $feature): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="group relative rounded-2xl border border-white/10 bg-white/5 p-5 backdrop-blur transition hover:border-white/20">
                        <div class="flex items-center justify-between">
                            <div class="h-11 w-11 rounded-xl bg-gradient-to-br from-indigo-500 to-sky-400 text-white inline-flex items-center justify-center shadow-glow">
                                <?php echo svgIcon($feature['icon'] ?? 'default'); ?>

                            </div>
                            <span class="text-xs font-semibold text-slate-400"><?php echo e(__('landing.dashboard.trusted')); ?></span>
                        </div>
                        <h3 class="mt-4 text-xl font-semibold text-white"><?php echo e(__($feature['title'])); ?></h3>
                        <p class="mt-3 text-sm text-slate-300 leading-relaxed"><?php echo e(__($feature['description'])); ?></p>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </section>

        <section id="faq" class="mt-20 grid lg:grid-cols-2 gap-10">
            <div class="space-y-3">
                <p class="text-xs uppercase tracking-[0.22em] text-slate-400"><?php echo e(__('landing.faq_section.eyebrow')); ?></p>
                <h2 class="text-3xl font-display font-bold text-white"><?php echo e(__('landing.faq_section.title')); ?></h2>
                <p class="text-slate-300">
                    <?php echo e(__('landing.faq_intro')); ?>

                </p>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($canLogin): ?>
                    <div class="flex gap-3 pt-3">
                        <a href="<?php echo e(route('login')); ?>" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-indigo-500 to-sky-500 px-4 py-2.5 text-sm font-semibold text-white shadow-glow">
                            <?php echo e(__('app.cta.login')); ?>

                        </a>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($canRegister): ?>
                        <a href="<?php echo e(route('register')); ?>" class="inline-flex items-center gap-2 rounded-xl border border-white/15 px-4 py-2.5 text-sm font-semibold text-white/90 hover:border-white/30">
                            <?php echo e(__('app.cta.register')); ?>

                        </a>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

            <div class="space-y-3">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $faqItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $faq): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <details class="group rounded-2xl border border-white/10 bg-white/5 px-5 py-4 backdrop-blur">
                        <summary class="flex cursor-pointer items-center justify-between text-left text-base font-semibold text-white">
                            <span><?php echo e(__($faq['question'])); ?></span>
                            <span class="ml-4 inline-flex h-8 w-8 items-center justify-center rounded-full bg-white/10 text-slate-200 transition group-open:rotate-45">+</span>
                        </summary>
                        <p class="mt-3 text-sm text-slate-300 leading-relaxed"><?php echo e(__($faq['answer'])); ?></p>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($faq['category'])): ?>
                            <p class="mt-2 text-xs text-slate-400"><?php echo e(__('landing.faq_section.category_prefix')); ?> <?php echo e(__($faq['category'])); ?></p>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </details>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </section>

        <section class="mt-16">
            <div class="rounded-3xl border border-white/10 bg-gradient-to-r from-indigo-600/80 via-sky-500/70 to-indigo-600/80 px-6 py-8 shadow-glow">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <p class="text-sm uppercase tracking-[0.2em] text-white/80"><?php echo e(__('landing.cta_bar.eyebrow')); ?></p>
                        <h3 class="text-2xl font-display font-bold text-white mt-1"><?php echo e(__('landing.cta_bar.title')); ?></h3>
                    </div>
                    <div class="flex gap-3">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($canLogin): ?>
                            <a href="<?php echo e(route('login')); ?>" class="inline-flex items-center gap-2 rounded-xl bg-white text-slate-900 px-5 py-3 text-sm font-semibold shadow-lg shadow-slate-900/20">
                                <?php echo e(__('app.cta.login')); ?>

                            </a>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($canRegister): ?>
                            <a href="<?php echo e(route('register')); ?>" class="inline-flex items-center gap-2 rounded-xl border border-white/60 px-5 py-3 text-sm font-semibold text-white">
                                <?php echo e(__('app.cta.register')); ?>

                            </a>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
    </main>
</div>

</body>
</html>
<?php /**PATH C:\www\rent_counter\resources\views/welcome.blade.php ENDPATH**/ ?>