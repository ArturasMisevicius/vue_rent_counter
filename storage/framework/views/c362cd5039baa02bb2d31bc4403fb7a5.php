<?php $__env->startSection('title', __('tenant.profile.title')); ?>

<?php $__env->startSection('tenant-content'); ?>
<?php if (isset($component)) { $__componentOriginal5daf71cc63742455f9f020a381938683 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5daf71cc63742455f9f020a381938683 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tenant.page','data' => ['title' => __('tenant.profile.title'),'description' => __('tenant.profile.description')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tenant.page'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('tenant.profile.title')),'description' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('tenant.profile.description'))]); ?>
    <?php if (isset($component)) { $__componentOriginala2da19b56e08af6f893a5c15b0a34669 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala2da19b56e08af6f893a5c15b0a34669 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tenant.quick-actions','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tenant.quick-actions'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala2da19b56e08af6f893a5c15b0a34669)): ?>
<?php $attributes = $__attributesOriginala2da19b56e08af6f893a5c15b0a34669; ?>
<?php unset($__attributesOriginala2da19b56e08af6f893a5c15b0a34669); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala2da19b56e08af6f893a5c15b0a34669)): ?>
<?php $component = $__componentOriginala2da19b56e08af6f893a5c15b0a34669; ?>
<?php unset($__componentOriginala2da19b56e08af6f893a5c15b0a34669); ?>
<?php endif; ?>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('success')): ?>
        <?php if (isset($component)) { $__componentOriginal9c1ca064170a53b948f018bbf5edd33c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9c1ca064170a53b948f018bbf5edd33c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tenant.alert','data' => ['type' => 'success']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tenant.alert'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'success']); ?>
            <?php echo e(session('success')); ?>

         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9c1ca064170a53b948f018bbf5edd33c)): ?>
<?php $attributes = $__attributesOriginal9c1ca064170a53b948f018bbf5edd33c; ?>
<?php unset($__attributesOriginal9c1ca064170a53b948f018bbf5edd33c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9c1ca064170a53b948f018bbf5edd33c)): ?>
<?php $component = $__componentOriginal9c1ca064170a53b948f018bbf5edd33c; ?>
<?php unset($__componentOriginal9c1ca064170a53b948f018bbf5edd33c); ?>
<?php endif; ?>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <?php if (isset($component)) { $__componentOriginal1479eaaad1c219d39a5c50a0a8cbec4f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1479eaaad1c219d39a5c50a0a8cbec4f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tenant.section-card','data' => ['title' => __('tenant.profile.account_information')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tenant.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('tenant.profile.account_information'))]); ?>
        <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
            <div>
                <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500"><?php echo e(__('tenant.profile.labels.name')); ?></dt>
                <dd class="mt-1 text-sm text-slate-900"><?php echo e($user->name); ?></dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500"><?php echo e(__('tenant.profile.labels.email')); ?></dt>
                <dd class="mt-1 text-sm text-slate-900"><?php echo e($user->email); ?></dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500"><?php echo e(__('tenant.profile.labels.role')); ?></dt>
                <dd class="mt-1 text-sm text-slate-900"><?php echo e(enum_label($user->role)); ?></dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500"><?php echo e(__('tenant.profile.labels.created')); ?></dt>
                <dd class="mt-1 text-sm text-slate-900"><?php echo e($user->created_at->format('Y-m-d')); ?></dd>
            </div>
        </dl>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1479eaaad1c219d39a5c50a0a8cbec4f)): ?>
<?php $attributes = $__attributesOriginal1479eaaad1c219d39a5c50a0a8cbec4f; ?>
<?php unset($__attributesOriginal1479eaaad1c219d39a5c50a0a8cbec4f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1479eaaad1c219d39a5c50a0a8cbec4f)): ?>
<?php $component = $__componentOriginal1479eaaad1c219d39a5c50a0a8cbec4f; ?>
<?php unset($__componentOriginal1479eaaad1c219d39a5c50a0a8cbec4f); ?>
<?php endif; ?>

    <?php if (isset($component)) { $__componentOriginal1479eaaad1c219d39a5c50a0a8cbec4f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1479eaaad1c219d39a5c50a0a8cbec4f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tenant.section-card','data' => ['title' => __('tenant.profile.language_preference')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tenant.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('tenant.profile.language_preference'))]); ?>
        <form method="POST" action="<?php echo e(route('locale.set')); ?>">
            <?php echo csrf_field(); ?>
            <?php if (isset($component)) { $__componentOriginalb98937165472911853f6eaf30e23bc6a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb98937165472911853f6eaf30e23bc6a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tenant.stack','data' => ['gap' => '4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tenant.stack'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['gap' => '4']); ?>
                <div>
                    <label for="locale" class="block text-sm font-semibold text-slate-800"><?php echo e(__('tenant.profile.language.select')); ?></label>
                    <select name="locale" id="locale" onchange="this.form.submit()" class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = \App\Models\Language::query()->where('is_active', true)->orderBy('display_order')->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $language): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($language->code); ?>" <?php echo e($language->code === app()->getLocale() ? 'selected' : ''); ?>>
                                <?php echo e($language->native_name ?? $language->name); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </select>
                    <p class="mt-2 text-sm text-slate-600"><?php echo e(__('tenant.profile.language.note')); ?></p>
                </div>
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb98937165472911853f6eaf30e23bc6a)): ?>
<?php $attributes = $__attributesOriginalb98937165472911853f6eaf30e23bc6a; ?>
<?php unset($__attributesOriginalb98937165472911853f6eaf30e23bc6a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb98937165472911853f6eaf30e23bc6a)): ?>
<?php $component = $__componentOriginalb98937165472911853f6eaf30e23bc6a; ?>
<?php unset($__componentOriginalb98937165472911853f6eaf30e23bc6a); ?>
<?php endif; ?>
        </form>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1479eaaad1c219d39a5c50a0a8cbec4f)): ?>
<?php $attributes = $__attributesOriginal1479eaaad1c219d39a5c50a0a8cbec4f; ?>
<?php unset($__attributesOriginal1479eaaad1c219d39a5c50a0a8cbec4f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1479eaaad1c219d39a5c50a0a8cbec4f)): ?>
<?php $component = $__componentOriginal1479eaaad1c219d39a5c50a0a8cbec4f; ?>
<?php unset($__componentOriginal1479eaaad1c219d39a5c50a0a8cbec4f); ?>
<?php endif; ?>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($user->property): ?>
    <?php if (isset($component)) { $__componentOriginal1479eaaad1c219d39a5c50a0a8cbec4f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1479eaaad1c219d39a5c50a0a8cbec4f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tenant.section-card','data' => ['title' => __('tenant.profile.assigned_property')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tenant.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('tenant.profile.assigned_property'))]); ?>
        <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
            <div>
                <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500"><?php echo e(__('tenant.profile.labels.address')); ?></dt>
                <dd class="mt-1 text-sm text-slate-900"><?php echo e($user->property->address); ?></dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500"><?php echo e(__('tenant.profile.labels.type')); ?></dt>
                <dd class="mt-1 text-sm text-slate-900"><?php echo e(enum_label($user->property->type)); ?></dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500"><?php echo e(__('tenant.profile.labels.area')); ?></dt>
                <dd class="mt-1 text-sm text-slate-900"><?php echo e($user->property->area_sqm); ?> m²</dd>
            </div>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($user->property->building): ?>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500"><?php echo e(__('tenant.profile.labels.building')); ?></dt>
                <dd class="mt-1 text-sm text-slate-900"><?php echo e($user->property->building->display_name); ?></dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500"><?php echo e(__('tenant.property.labels.building_address')); ?></dt>
                <dd class="mt-1 text-sm text-slate-900"><?php echo e($user->property->building->address); ?></dd>
            </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </dl>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1479eaaad1c219d39a5c50a0a8cbec4f)): ?>
<?php $attributes = $__attributesOriginal1479eaaad1c219d39a5c50a0a8cbec4f; ?>
<?php unset($__attributesOriginal1479eaaad1c219d39a5c50a0a8cbec4f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1479eaaad1c219d39a5c50a0a8cbec4f)): ?>
<?php $component = $__componentOriginal1479eaaad1c219d39a5c50a0a8cbec4f; ?>
<?php unset($__componentOriginal1479eaaad1c219d39a5c50a0a8cbec4f); ?>
<?php endif; ?>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($user->parentUser): ?>
    <?php if (isset($component)) { $__componentOriginal1479eaaad1c219d39a5c50a0a8cbec4f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1479eaaad1c219d39a5c50a0a8cbec4f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tenant.section-card','data' => ['title' => __('tenant.profile.manager_contact.title'),'description' => __('tenant.profile.manager_contact.description')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tenant.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('tenant.profile.manager_contact.title')),'description' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('tenant.profile.manager_contact.description'))]); ?>
        <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($user->parentUser->organization_name): ?>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500"><?php echo e(__('tenant.profile.labels.organization')); ?></dt>
                <dd class="mt-1 text-sm text-slate-900"><?php echo e($user->parentUser->organization_name); ?></dd>
            </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500"><?php echo e(__('tenant.profile.labels.contact_name')); ?></dt>
                <dd class="mt-1 text-sm text-slate-900"><?php echo e($user->parentUser->name); ?></dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500"><?php echo e(__('tenant.profile.labels.email')); ?></dt>
                <dd class="mt-1 text-sm text-slate-900">
                    <a href="mailto:<?php echo e($user->parentUser->email); ?>" class="text-indigo-700 font-semibold hover:text-indigo-800">
                        <?php echo e($user->parentUser->email); ?>

                    </a>
                </dd>
            </div>
        </dl>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1479eaaad1c219d39a5c50a0a8cbec4f)): ?>
<?php $attributes = $__attributesOriginal1479eaaad1c219d39a5c50a0a8cbec4f; ?>
<?php unset($__attributesOriginal1479eaaad1c219d39a5c50a0a8cbec4f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1479eaaad1c219d39a5c50a0a8cbec4f)): ?>
<?php $component = $__componentOriginal1479eaaad1c219d39a5c50a0a8cbec4f; ?>
<?php unset($__componentOriginal1479eaaad1c219d39a5c50a0a8cbec4f); ?>
<?php endif; ?>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <?php if (isset($component)) { $__componentOriginal1479eaaad1c219d39a5c50a0a8cbec4f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1479eaaad1c219d39a5c50a0a8cbec4f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tenant.section-card','data' => ['title' => __('tenant.profile.update_profile'),'description' => __('tenant.profile.update_description')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tenant.section-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('tenant.profile.update_profile')),'description' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('tenant.profile.update_description'))]); ?>
        <form method="POST" action="<?php echo e(route('tenant.profile.update')); ?>">
            <?php echo csrf_field(); ?>
            <?php echo method_field('PUT'); ?>
            
            <?php if (isset($component)) { $__componentOriginalb98937165472911853f6eaf30e23bc6a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb98937165472911853f6eaf30e23bc6a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tenant.stack','data' => ['gap' => '4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tenant.stack'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['gap' => '4']); ?>
                <div>
                    <label for="name" class="block text-sm font-semibold text-slate-800"><?php echo e(__('tenant.profile.labels.name')); ?></label>
                    <input type="text" name="name" id="name" value="<?php echo e(old('name', $user->name)); ?>" required
                           class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-rose-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p class="mt-1 text-sm text-rose-600"><?php echo e($message); ?></p>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                <div>
                    <label for="email" class="block text-sm font-semibold text-slate-800"><?php echo e(__('tenant.profile.labels.email')); ?></label>
                    <input type="email" name="email" id="email" value="<?php echo e(old('email', $user->email)); ?>" required
                           class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-rose-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <p class="mt-1 text-sm text-rose-600"><?php echo e($message); ?></p>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                <div class="border-t border-slate-200 pt-4">
                    <h4 class="text-sm font-semibold text-slate-900 mb-3"><?php echo e(__('tenant.profile.change_password')); ?></h4>
                    <p class="text-sm text-slate-600 mb-4"><?php echo e(__('tenant.profile.password_note')); ?></p>
                    
                    <div class="space-y-4">
                        <div>
                            <label for="current_password" class="block text-sm font-semibold text-slate-800"><?php echo e(__('tenant.profile.labels.current_password')); ?></label>
                            <input type="password" name="current_password" id="current_password" autocomplete="current-password"
                                   class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 <?php $__errorArgs = ['current_password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-rose-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['current_password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <p class="mt-1 text-sm text-rose-600"><?php echo e($message); ?></p>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-semibold text-slate-800"><?php echo e(__('tenant.profile.labels.new_password')); ?></label>
                            <input type="password" name="password" id="password" autocomplete="new-password"
                                   class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-rose-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <p class="mt-1 text-sm text-rose-600"><?php echo e($message); ?></p>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-sm font-semibold text-slate-800"><?php echo e(__('tenant.profile.labels.confirm_password')); ?></label>
                            <input type="password" name="password_confirmation" id="password_confirmation" autocomplete="new-password"
                                   class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <?php echo e(__('tenant.profile.save_changes')); ?>

                    </button>
                </div>
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb98937165472911853f6eaf30e23bc6a)): ?>
<?php $attributes = $__attributesOriginalb98937165472911853f6eaf30e23bc6a; ?>
<?php unset($__attributesOriginalb98937165472911853f6eaf30e23bc6a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb98937165472911853f6eaf30e23bc6a)): ?>
<?php $component = $__componentOriginalb98937165472911853f6eaf30e23bc6a; ?>
<?php unset($__componentOriginalb98937165472911853f6eaf30e23bc6a); ?>
<?php endif; ?>
        </form>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1479eaaad1c219d39a5c50a0a8cbec4f)): ?>
<?php $attributes = $__attributesOriginal1479eaaad1c219d39a5c50a0a8cbec4f; ?>
<?php unset($__attributesOriginal1479eaaad1c219d39a5c50a0a8cbec4f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1479eaaad1c219d39a5c50a0a8cbec4f)): ?>
<?php $component = $__componentOriginal1479eaaad1c219d39a5c50a0a8cbec4f; ?>
<?php unset($__componentOriginal1479eaaad1c219d39a5c50a0a8cbec4f); ?>
<?php endif; ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5daf71cc63742455f9f020a381938683)): ?>
<?php $attributes = $__attributesOriginal5daf71cc63742455f9f020a381938683; ?>
<?php unset($__attributesOriginal5daf71cc63742455f9f020a381938683); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5daf71cc63742455f9f020a381938683)): ?>
<?php $component = $__componentOriginal5daf71cc63742455f9f020a381938683; ?>
<?php unset($__componentOriginal5daf71cc63742455f9f020a381938683); ?>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.tenant', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\www\rent_counter\resources\views/tenant/profile/show.blade.php ENDPATH**/ ?>