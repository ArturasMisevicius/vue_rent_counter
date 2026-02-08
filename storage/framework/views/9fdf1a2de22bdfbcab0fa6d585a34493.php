

<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'currentLocale' => app()->getLocale(),
    'availableLocales' => \App\Support\Localization::availableLocales(),
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'currentLocale' => app()->getLocale(),
    'availableLocales' => \App\Support\Localization::availableLocales(),
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
    - WCAG 2.1 AA compliant accessibility
    - Screen reader announcements for language changes
    - Keyboard navigation support
    - Visual indicators for current language
    - Alpine.js powered interactions
    - Translation validation
    
    Usage:
    <?php if (isset($component)) { $__componentOriginal072a11f93c8682b5e8298df5f629d1c5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal072a11f93c8682b5e8298df5f629d1c5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.accessible-language-switcher','data' => ['availableLocales' => $availableLocales,'currentLocale' => $currentLocale]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('accessible-language-switcher'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['available-locales' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($availableLocales),'current-locale' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($currentLocale)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal072a11f93c8682b5e8298df5f629d1c5)): ?>
<?php $attributes = $__attributesOriginal072a11f93c8682b5e8298df5f629d1c5; ?>
<?php unset($__attributesOriginal072a11f93c8682b5e8298df5f629d1c5); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal072a11f93c8682b5e8298df5f629d1c5)): ?>
<?php $component = $__componentOriginal072a11f93c8682b5e8298df5f629d1c5; ?>
<?php unset($__componentOriginal072a11f93c8682b5e8298df5f629d1c5); ?>
<?php endif; ?>
    
    Dependencies:
    - Alpine.js for interactivity
    - Tailwind CSS for styling
    - Laravel localization system
    - Translation keys: common.language_switcher_label, common.current_language, common.language_changed_to
    
    @see \App\View\Composers\NavigationComposer For data preparation
    @see \App\Support\Localization For locale configuration
    @see \App\Http\Controllers\LanguageController For language switching
--}}

<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'currentLocale' => app()->getLocale(),
    'availableLocales' => \App\Support\Localization::availableLocales(),
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'currentLocale' => app()->getLocale(),
    'availableLocales' => \App\Support\Localization::availableLocales(),
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<div class="relative inline-block text-left" x-data="{ open: false }" @click.away="open = false">
    
    <div>
        <button type="button" 
                class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                @click="open = !open"
                :aria-expanded="open"
                aria-haspopup="true"
                aria-label="<?php echo e(__('common.language_switcher_label')); ?>">
            
            
            <?php
                $currentLocaleConfig = $availableLocales->firstWhere('code', $currentLocale);
            ?>
            
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($currentLocaleConfig): ?>
                <span class="mr-2"><?php echo e(__($currentLocaleConfig['label'])); ?></span>
                <span class="text-xs text-gray-500">(<?php echo e($currentLocaleConfig['abbreviation']); ?>)</span>
            <?php else: ?>
                <span class="mr-2"><?php echo e(__('common.language')); ?></span>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            
            
            <svg class="ml-2 -mr-1 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
            
            
            <span class="sr-only"><?php echo e(__('common.current_language')); ?>: <?php echo e($currentLocaleConfig ? __($currentLocaleConfig['label']) : __('common.language')); ?></span>
        </button>
    </div>

    
    <div x-show="open"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="transform opacity-0 scale-95"
         x-transition:enter-end="transform opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="transform opacity-100 scale-100"
         x-transition:leave-end="transform opacity-0 scale-95"
         class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50"
         role="menu"
         aria-orientation="vertical"
         aria-labelledby="language-menu">
        
        <div class="py-1" role="none">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $availableLocales; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $locale): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="<?php echo e(route('language.switch', $locale['code'])); ?>"
                   class="group flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:bg-gray-100 focus:text-gray-900"
                   role="menuitem"
                   @click="$dispatch('language-changed', { locale: '<?php echo e($locale['code']); ?>', name: '<?php echo e(__($locale['label'])); ?>' })"
                   <?php if($locale['code'] === $currentLocale): ?> aria-current="true" <?php endif; ?>>
                   
                    
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($locale['code'] === $currentLocale): ?>
                        <svg class="mr-3 h-4 w-4 text-indigo-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    <?php else: ?>
                        <span class="mr-3 h-4 w-4"></span>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    
                    
                    <div class="flex-1">
                        <div class="font-medium"><?php echo e(__($locale['label'])); ?></div>
                        <div class="text-xs text-gray-500"><?php echo e($locale['abbreviation']); ?></div>
                    </div>
                </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </div>
    
    
    <div aria-live="polite" aria-atomic="true" class="sr-only" x-data="{ announcement: '' }">
        <span x-text="announcement"></span>
    </div>
</div>


<?php $__env->startPush('scripts'); ?>
<script>
    // Validate required translations are available
    document.addEventListener('DOMContentLoaded', function() {
        const requiredTranslations = [
            'common.language_switcher_label',
            'common.current_language',
            'common.language_changed_to'
        ];
        
        const translations = {
            'common.language_switcher_label': <?php echo json_encode(__('common.language_switcher_label'), 15, 512) ?>,
            'common.current_language': <?php echo json_encode(__('common.current_language'), 15, 512) ?>,
            'common.language_changed_to': <?php echo json_encode(__('common.language_changed_to'), 15, 512) ?>
        };
        
        requiredTranslations.forEach(function(key) {
            if (translations[key] === key) {
                console.warn('Missing translation for key: ' + key);
            }
        });
    });
    
    // Handle language change events
    document.addEventListener('language-changed', function(event) {
        const { locale, name } = event.detail;
        
        // Announce to screen readers
        const announcement = <?php echo json_encode(__('common.language_changed_to'), 15, 512) ?> + ' ' + name;
        const announcementEl = document.querySelector('[aria-live="polite"] span');
        if (announcementEl) {
            announcementEl.textContent = announcement;
        }
    });
</script>
<?php $__env->stopPush(); ?><?php /**PATH C:\www\rent_counter\resources\views/components/accessible-language-switcher.blade.php ENDPATH**/ ?>