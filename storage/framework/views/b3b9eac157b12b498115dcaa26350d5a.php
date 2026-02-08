<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e(__('error_pages.422.title')); ?></title>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css']); ?>
</head>
<body class="bg-slate-100">
    <div class="min-h-screen flex items-center justify-center px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl w-full space-y-8">
            <div class="text-center">
                <h1 class="text-9xl font-bold text-indigo-600">422</h1>
                <h2 class="mt-6 text-3xl font-extrabold text-slate-900">
                    <?php echo e(__('error_pages.422.headline')); ?>

                </h2>
                <p class="mt-2 text-sm text-slate-600">
                    <?php echo e(__('error_pages.422.description')); ?>

                </p>
            </div>
            
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($errors) && $errors->any()): ?>
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-slate-900 mb-4"><?php echo e(__('error_pages.422.errors_title')); ?></h3>
                    <ul class="space-y-2">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li class="flex items-start">
                                <svg class="h-5 w-5 text-red-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-sm text-slate-700"><?php echo e($error); ?></span>
                            </li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </ul>
                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            
            <div class="text-center space-y-4">
                <button onclick="history.back()" class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                    <?php echo e(__('error_pages.common.back_fix')); ?>

                </button>
                
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->guard()->check()): ?>
                    <div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->user()->role->value === 'admin'): ?>
                            <a href="<?php echo e(route('filament.admin.pages.dashboard')); ?>" class="text-indigo-600 hover:text-indigo-500">
                                <?php echo e(__('error_pages.common.dashboard')); ?>

                            </a>
                        <?php elseif(auth()->user()->role->value === 'manager'): ?>
                            <a href="<?php echo e(route('manager.dashboard')); ?>" class="text-indigo-600 hover:text-indigo-500">
                                <?php echo e(__('error_pages.common.dashboard')); ?>

                            </a>
                        <?php elseif(auth()->user()->role->value === 'tenant'): ?>
                            <a href="<?php echo e(route('tenant.dashboard')); ?>" class="text-indigo-600 hover:text-indigo-500">
                                <?php echo e(__('error_pages.common.dashboard')); ?>

                            </a>
                        <?php else: ?>
                            <a href="<?php echo e(url('/')); ?>" class="text-indigo-600 hover:text-indigo-500">
                                <?php echo e(__('error_pages.common.home')); ?>

                            </a>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
<?php /**PATH /Users/andrejprus/Library/CloudStorage/Dropbox/projects/rentcounter/vue_rent_counter/resources/views/errors/422.blade.php ENDPATH**/ ?>