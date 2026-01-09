<?php if (isset($component)) { $__componentOriginalca52de3bb9c3312a4c9c230381dba9e1 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalca52de3bb9c3312a4c9c230381dba9e1 = $attributes; } ?>
<?php $component = App\View\Components\LanguageSwitcher::resolve(['languages' => $languages,'currentLocale' => 'en','showLabels' => false] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
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
<?php endif; ?><?php /**PATH C:\Users\menok\AppData\Local\Temp/larC88A.blade.php ENDPATH**/ ?>