<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>" dir="<?php echo e(app()->getLocale() === 'fa' ? 'rtl' : 'ltr'); ?>" id="htmlElement" data-input-language="english" style="font-family: 'Vazirmatn', Tahoma, Arial, sans-serif;">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

        <title><?php echo e(config('app.name', 'Laravel')); ?></title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
        
        <script>
            // Initialize dark mode IMMEDIATELY before any rendering
            (function initDarkMode() {
                const htmlElement = document.documentElement;
                const darkModeFromStorage = localStorage.getItem('darkMode');
                
                // If nothing in storage, check user preference from DB
                if (darkModeFromStorage === null) {
                    if (<?php echo e(auth()->check() && auth()->user()->dark_mode ? 'true' : 'false'); ?>) {
                        htmlElement.classList.add('dark');
                        localStorage.setItem('darkMode', 'true');
                    } else {
                        htmlElement.classList.remove('dark');
                        localStorage.setItem('darkMode', 'false');
                    }
                } else {
                    // Use localStorage preference
                    if (darkModeFromStorage === 'true') {
                        htmlElement.classList.add('dark');
                    } else {
                        htmlElement.classList.remove('dark');
                    }
                }
            })();
        </script>
    </head>
    <body class="font-sans antialiased bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 transition-colors duration-300">
        <div class="min-h-screen bg-gray-100 dark:bg-gray-800">
            <?php echo $__env->make('layouts.navigation', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

            <!-- Page Heading -->
            <?php if(isset($header)): ?>
                <header class="bg-white dark:bg-gray-800 shadow transition-colors duration-300">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 text-gray-900 dark:text-gray-100">
                        <?php echo e($header); ?>

                    </div>
                </header>
            <?php endif; ?>

            <!-- Page Content -->
            <main>
                <?php if (! empty(trim($__env->yieldContent('content')))): ?>
                    <?php echo $__env->yieldContent('content'); ?>
                <?php else: ?>
                    <?php echo e($slot); ?>

                <?php endif; ?>
            </main>
        </div>
    </body>
</html>
<?php /**PATH E:\smart-cryptopay\resources\views/layouts/app.blade.php ENDPATH**/ ?>