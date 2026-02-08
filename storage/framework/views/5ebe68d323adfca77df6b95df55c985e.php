


<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($languageData->isNotEmpty()): ?>
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($variant === 'select'): ?>
        <div class="language-switcher-container inline-flex <?php echo e($class); ?>">
            <form method="GET" action="<?php echo e(route('language.switch', ['locale' => $currentLocale])); ?>" class="language-switcher-form inline-flex">
                <label for="language-select" class="sr-only"><?php echo e(__('common.select_language')); ?></label>
                <select
                    id="language-select"
                    name="locale"
                    data-language-switcher="true"
                    data-base-url="<?php echo e(url('/language')); ?>/"
                    class="block w-full rounded-md border-0 bg-white/10 py-1.5 pl-3 pr-10 text-white ring-1 ring-inset ring-white/20 focus:ring-2 focus:ring-inset focus:ring-white sm:text-sm sm:leading-6"
                    aria-label="<?php echo e(__('common.select_language')); ?>"
                >
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $languageData; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $language): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option
                            value="<?php echo e($language->code); ?>"
                            <?php if($language->matches($currentLocale)): ?> selected <?php endif; ?>
                        >
                            <?php echo e($showLabels ? $language->getDisplayName() : $language->getUppercaseCode()); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </select>
                <noscript>
                    <button type="submit" class="ml-2 px-2 py-1 text-xs bg-white/20 rounded hover:bg-white/30 transition-colors">
                        <?php echo e(__('common.change_language')); ?>

                    </button>
                </noscript>
            </form>
        </div>
    <?php else: ?>
        
        <div class="relative language-switcher-container inline-block text-left <?php echo e($class); ?>" x-data="{ open: false }">
            <button
                @click="open = !open"
                @keydown.escape="open = false"
                class="inline-flex items-center gap-x-1.5 rounded-md bg-white/10 px-3 py-2 text-sm font-semibold text-white shadow-sm ring-1 ring-inset ring-white/20 hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-white/40 transition-all duration-200"
                aria-expanded="false"
                aria-haspopup="true"
                aria-label="<?php echo e(__('common.select_language')); ?>"
                type="button"
            >
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($currentLanguageData): ?>
                    <?php echo e($showLabels ? $currentLanguageData->getDisplayName() : $currentLanguageData->getUppercaseCode()); ?>

                <?php else: ?>
                    <?php echo e(strtoupper($currentLocale)); ?>

                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <svg class="ml-1 h-4 w-4 transition-transform duration-200"
                     :class="{ 'rotate-180': open }"
                     fill="none"
                     stroke="currentColor"
                     viewBox="0 0 24 24"
                     aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>

            <div
                x-show="open"
                @click.away="open = false"
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="transform opacity-0 scale-95"
                x-transition:enter-end="transform opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="transform opacity-100 scale-100"
                x-transition:leave-end="transform opacity-0 scale-95"
                class="absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
                role="menu"
                aria-orientation="vertical"
                x-cloak
            >
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $languageData; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $language): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <a
                        href="<?php echo e(route('language.switch', $language->code)); ?>"
                        class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 focus:bg-gray-100 focus:outline-none <?php echo e($language->matches($currentLocale) ? 'bg-gray-50 font-medium' : ''); ?>"
                        role="menuitem"
                        @click="open = false"
                        <?php if($language->matches($currentLocale)): ?>
                            aria-current="true"
                        <?php endif; ?>
                    >
                        <span class="flex items-center justify-between">
                            <?php echo e($showLabels ? $language->getDisplayName() : $language->getUppercaseCode()); ?>

                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($language->matches($currentLocale)): ?>
                                <svg class="h-4 w-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </span>
                    </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php else: ?>
    
    <div class="inline-flex <?php echo e($class); ?>" role="status" aria-live="polite">
        <span class="text-sm text-gray-500"><?php echo e(__('common.no_languages_available')); ?></span>
    </div>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php /**PATH C:\www\rent_counter\resources\views/components/language-switcher.blade.php ENDPATH**/ ?>