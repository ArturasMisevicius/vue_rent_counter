<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e(__('error_pages.404.title')); ?></title>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css']); ?>
</head>
<body class="bg-slate-100">
    <div class="min-h-screen flex items-center justify-center px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8 text-center">
            <div>
                <h1 class="text-9xl font-bold text-indigo-600">404</h1>
                <h2 class="mt-6 text-3xl font-extrabold text-slate-900">
                    <?php echo e(__('error_pages.404.headline')); ?>

                </h2>
                <p class="mt-2 text-sm text-slate-600">
                    <?php echo e(__('error_pages.404.description')); ?>

                </p>
            </div>
            
            <div class="mt-8 space-y-4">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->guard()->check()): ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->user()->role->value === 'admin'): ?>
                        <a href="<?php echo e(route('filament.admin.pages.dashboard')); ?>" class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                            <?php echo e(__('error_pages.common.dashboard')); ?>

                        </a>
                    <?php elseif(auth()->user()->role->value === 'manager'): ?>
                        <a href="<?php echo e(route('manager.dashboard')); ?>" class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                            <?php echo e(__('error_pages.common.dashboard')); ?>

                        </a>
                    <?php elseif(auth()->user()->role->value === 'tenant'): ?>
                        <a href="<?php echo e(route('tenant.dashboard')); ?>" class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                            <?php echo e(__('error_pages.common.dashboard')); ?>

                        </a>
                    <?php else: ?>
                        <a href="<?php echo e(url('/')); ?>" class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                            <?php echo e(__('error_pages.common.home')); ?>

                        </a>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php else: ?>
                    <a href="<?php echo e(url('/')); ?>" class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                        <?php echo e(__('error_pages.common.home')); ?>

                    </a>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                
                <div>
                    <button onclick="history.back()" class="text-indigo-600 hover:text-indigo-500">
                        <?php echo e(__('error_pages.common.back')); ?>

                    </button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php /**PATH C:\www\rent_counter\resources\views/errors/404.blade.php ENDPATH**/ ?>