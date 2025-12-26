
<span <?php echo e($attributes->merge(['class' => 'inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold border ' . $badgeClasses])); ?>>
    <span class="h-2.5 w-2.5 rounded-full <?php echo e($dotClasses); ?>" aria-hidden="true"></span>
    <span><?php echo e($slot->isEmpty() ? $label : $slot); ?></span>
</span>
<?php /**PATH C:\www\rent_counter\resources\views/components/status-badge.blade.php ENDPATH**/ ?>