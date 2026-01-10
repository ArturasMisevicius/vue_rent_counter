<?php $__env->startSection('content'); ?>
<div class="max-w-5xl mx-auto">
    <?php echo $__env->yieldContent('tenant-content'); ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\www\rent_counter\resources\views/layouts/tenant.blade.php ENDPATH**/ ?>