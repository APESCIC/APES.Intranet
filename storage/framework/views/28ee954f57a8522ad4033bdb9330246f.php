<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?php echo e(config('app.name')); ?></title>
        <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    </head>
    <body class="flex items-center justify-center p-6">
        <main class="w-full max-w-xl rounded-sg-card bg-sg-white p-8 shadow-sg-card">
            <p class="text-sm font-semibold uppercase tracking-wide text-sg-muted">APES CIC</p>
            <h1 class="mt-2 font-serif text-4xl font-semibold text-sg-ink"><?php echo e(config('app.name')); ?></h1>
            <p class="mt-4 text-sg-muted">
                The intranet for APES staff, volunteers and students is being built.
            </p>
            <ul class="mt-6 grid gap-3 sm:grid-cols-2">
                <li class="flex items-center gap-3 rounded-sg-field bg-sg-info-mist p-3">
                    <i data-lucide="house" class="size-5 text-sg-teal"></i>
                    <span>Updates</span>
                </li>
                <li class="flex items-center gap-3 rounded-sg-field bg-sg-info-mist p-3">
                    <i data-lucide="users" class="size-5 text-sg-teal"></i>
                    <span>People</span>
                </li>
                <li class="flex items-center gap-3 rounded-sg-field bg-sg-info-mist p-3">
                    <i data-lucide="life-buoy" class="size-5 text-sg-teal"></i>
                    <span>Help desk</span>
                </li>
                <li class="flex items-center gap-3 rounded-sg-field bg-sg-info-mist p-3">
                    <i data-lucide="shield-check" class="size-5 text-sg-teal"></i>
                    <span>HR and agreements</span>
                </li>
            </ul>
        </main>
    </body>
</html>
<?php /**PATH C:\Users\BambieMurphy\Documents\GitHub\APES.Intranet\resources\views/welcome.blade.php ENDPATH**/ ?>