<?php if (isset($component)) { $__componentOriginal5863877a5171c196453bfa0bd807e410 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5863877a5171c196453bfa0bd807e410 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layouts.app','data' => ['title' => 'Roles']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layouts.app'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Roles']); ?>
    <h1 class="font-serif text-3xl font-semibold">Roles</h1>
    <p class="mt-2 text-sg-muted">
        Roles come from Cloudron directory groups (<code>intranet.*</code>). This page is read-only.
    </p>

    <div class="mt-6 overflow-x-auto rounded-sg-card bg-sg-white shadow-sg-card">
        <table class="w-full text-left">
            <caption class="sr-only">Roles, their permissions and how many people have each role</caption>
            <thead class="border-b border-sg-info-mist text-sm text-sg-muted">
                <tr>
                    <th scope="col" class="p-4">Role</th>
                    <th scope="col" class="p-4">People</th>
                    <th scope="col" class="p-4">Permissions</th>
                </tr>
            </thead>
            <tbody>
                <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr class="border-b border-sg-info-mist last:border-0 align-top">
                        <th scope="row" class="p-4 font-semibold">
                            <?php echo e(\App\Core\Access\RoleName::tryFrom($role->name)?->label() ?? $role->name); ?>

                        </th>
                        <td class="p-4"><?php echo e($role->users_count); ?></td>
                        <td class="p-4">
                            <ul class="space-y-1">
                                <?php $__currentLoopData = $role->permissions->sortBy('name'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $permission): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <li>
                                        <?php echo e($descriptions[$permission->name] ?? $permission->name); ?>

                                        <code class="text-sm text-sg-muted"><?php echo e($permission->name); ?></code>
                                    </li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </ul>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5863877a5171c196453bfa0bd807e410)): ?>
<?php $attributes = $__attributesOriginal5863877a5171c196453bfa0bd807e410; ?>
<?php unset($__attributesOriginal5863877a5171c196453bfa0bd807e410); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5863877a5171c196453bfa0bd807e410)): ?>
<?php $component = $__componentOriginal5863877a5171c196453bfa0bd807e410; ?>
<?php unset($__componentOriginal5863877a5171c196453bfa0bd807e410); ?>
<?php endif; ?>
<?php /**PATH C:\Users\BambieMurphy\Documents\GitHub\APES.Intranet\resources\views/core/admin/roles/index.blade.php ENDPATH**/ ?>