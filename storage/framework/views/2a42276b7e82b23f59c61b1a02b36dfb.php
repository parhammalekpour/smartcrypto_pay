<nav x-data="{ open: false, notificationOpen: false, notificationCount: 0, notifications: [], theme: document.documentElement.classList.contains('dark'), toggleTheme() { this.theme = !this.theme; document.documentElement.classList.toggle('dark', this.theme); localStorage.setItem('darkMode', this.theme ? 'true' : 'false'); } }" class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700 transition-colors duration-300" style="font-family: 'Vazirmatn', Tahoma, Arial, sans-serif; direction: <?php echo e(app()->getLocale() === 'fa' ? 'rtl' : 'ltr'); ?>;" x-init="(()=>{ try { window.NOTIFICATION_ENDPOINTS = {
        list: '<?php echo e(LaravelLocalization::getLocalizedURL(app()->getLocale(), '/notifications')); ?>',
        unread: '<?php echo e(LaravelLocalization::getLocalizedURL(app()->getLocale(), '/notifications/unread-count')); ?>',
        markAll: '<?php echo e(LaravelLocalization::getLocalizedURL(app()->getLocale(), '/notifications/mark-all-read')); ?>',
        base: '<?php echo e(LaravelLocalization::getLocalizedURL(app()->getLocale(), '/notifications')); ?>'
    };

    // Seed initial notifications server-side to avoid fetch-order race when Alpine initializes
    <?php if(auth()->guard()->check()): ?>
        window.INITIAL_NOTIFICATIONS = <?php echo json_encode(
            
            App\Models\Notification::where('user_id', auth()->id())->orderBy('id', 'desc')->take(5)->get()->map(function($n){
                return [
                    'id' => $n->id,
                    'title' => App\Models\Notification::localizeText($n->title),
                    'message' => App\Models\Notification::localizeText($n->message),
                    'icon' => $n->icon,
                    'type' => $n->type,
                    'read' => (bool) $n->read,
                    'created_at' => $n->created_at->diffForHumans(),
                ];
            })
        ); ?>;
    <?php else: ?>
        window.INITIAL_NOTIFICATIONS = null;
    <?php endif; ?>

    // If initial notifications were provided by server, use them immediately
    try { if (window.INITIAL_NOTIFICATIONS && Array.isArray(window.INITIAL_NOTIFICATIONS)) { notifications = window.INITIAL_NOTIFICATIONS; notificationCount = notifications.filter(n => !n.read).length; } } catch(e){}

    // Fetch unread count immediately and then every 5s. If the unread count increases,
    // fetch the latest notifications list and merge new items into the existing list.
    const updateUnread = async () => {
        console.debug('[Notifications] updateUnread called (nav)');
        try {
            console.debug('[Notifications] requesting unread:', window.NOTIFICATION_ENDPOINTS.unread);
            const res = await fetch(window.NOTIFICATION_ENDPOINTS.unread, {credentials: 'same-origin'});
            console.debug('[Notifications] unread response (nav):', res);
            const data = await res.json();
            console.debug('[Notifications] unread data (nav):', data);
            const newCount = Number(data.count || 0);

            // Ensure a global last-known count exists so multiple Alpine instances don't duplicate work
            if (typeof window.__notificationLastCount === 'undefined') {
                window.__notificationLastCount = newCount;
            }

            // If count increased, fetch list and merge new notifications
            if (newCount > (window.__notificationLastCount || 0)) {
                try {
                    const listRes = await fetch(window.NOTIFICATION_ENDPOINTS.list, {credentials: 'same-origin'});
                    const listData = await listRes.json();
                    console.log('Notifications fetched (nav on delta):', listData);

                    // Merge new notifications at the beginning while avoiding duplicates
                    try {
                        const existingIds = new Set((notifications || []).map(n => n.id));
                        const newItems = (listData || []).filter(n => !existingIds.has(n.id));
                        if (newItems.length > 0) {
                            // Prepend new items so newest appear on top
                            notifications = newItems.concat(notifications || []);
                            // Cap to reasonable size (keep server-side seed of 5 but allow growth)
                            if (notifications.length > 50) notifications = notifications.slice(0, 50);

                            // Update unread count based on merged list
                            try { notificationCount = notifications.filter(n => !n.read).length; } catch(e) { notificationCount = newCount; }

                            // If a toast function exists, show toasts for new items
                            try {
                                if (typeof window.showToast === 'function') {
                                    newItems.forEach(item => {
                                        try { window.showToast(item.title || 'Notification', item.message || ''); } catch(e){}
                                    });
                                }
                            } catch(e){}
                        } else {
                            notificationCount = newCount;
                        }
                    } catch(e) {
                        // Fallback: just replace notifications if merge fails
                        notifications = listData;
                        try { notificationCount = notifications.filter(n => !n.read).length; } catch(e) { notificationCount = newCount; }
                    }
                } catch(err) {
                    console.error('Notifications fetch error (nav on delta):', err);
                    // If list fetch fails, still update the count
                    notificationCount = newCount;
                }
            } else {
                // No increase — just update count
                notificationCount = newCount;
            }

            window.__notificationLastCount = newCount;
        } catch(e) {
            // Ignore errors so polling doesn't break
        }
    };

    // Run once immediately
    updateUnread();

    // Also fetch notifications list once on init so UI shows immediately/refresh server-provided
    fetch(window.NOTIFICATION_ENDPOINTS.list, {credentials: 'same-origin'}).then(r => r.json()).then(data => { console.log('Notifications fetched (nav):', data); notifications = data; try { notificationCount = notifications.filter(n => !n.read).length; } catch(e){} }).catch(err=>{ console.error('Notifications fetch error (nav):', err); });

    // Avoid starting multiple intervals if the view is rendered multiple times
    if (!window.__notificationPollStarted) {
        window.__notificationPollStarted = true;
        setInterval(updateUnread, 5000);
    }
    } catch(e) { console.error('Notifications init error (nav):', e); }
})();">

    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="<?php echo e(route('dashboard')); ?>">
                        <?php if (isset($component)) { $__componentOriginal8892e718f3d0d7a916180885c6f012e7 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8892e718f3d0d7a916180885c6f012e7 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.application-logo','data' => ['class' => 'block h-9 w-auto fill-current text-gray-800 dark:text-gray-200']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('application-logo'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'block h-9 w-auto fill-current text-gray-800 dark:text-gray-200']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8892e718f3d0d7a916180885c6f012e7)): ?>
<?php $attributes = $__attributesOriginal8892e718f3d0d7a916180885c6f012e7; ?>
<?php unset($__attributesOriginal8892e718f3d0d7a916180885c6f012e7); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8892e718f3d0d7a916180885c6f012e7)): ?>
<?php $component = $__componentOriginal8892e718f3d0d7a916180885c6f012e7; ?>
<?php unset($__componentOriginal8892e718f3d0d7a916180885c6f012e7); ?>
<?php endif; ?>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <?php if (isset($component)) { $__componentOriginalc295f12dca9d42f28a259237a5724830 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc295f12dca9d42f28a259237a5724830 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.nav-link','data' => ['href' => route('dashboard'),'active' => request()->routeIs('dashboard')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('nav-link'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('dashboard')),'active' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request()->routeIs('dashboard'))]); ?>
                        <?php echo e(__('Dashboard')); ?>

                     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc295f12dca9d42f28a259237a5724830)): ?>
<?php $attributes = $__attributesOriginalc295f12dca9d42f28a259237a5724830; ?>
<?php unset($__attributesOriginalc295f12dca9d42f28a259237a5724830); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc295f12dca9d42f28a259237a5724830)): ?>
<?php $component = $__componentOriginalc295f12dca9d42f28a259237a5724830; ?>
<?php unset($__componentOriginalc295f12dca9d42f28a259237a5724830); ?>
<?php endif; ?>
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6 gap-4">
                <?php
                    $langUrlFa = \Mcamara\LaravelLocalization\Facades\LaravelLocalization::getLocalizedURL('fa', null, [], true);
                    $langUrlEn = \Mcamara\LaravelLocalization\Facades\LaravelLocalization::getLocalizedURL('en', null, [], true);
                ?>
                <!-- Language switcher -->
                <div class="flex items-center gap-2">
                    <a href="<?php echo e($langUrlFa); ?>" class="px-3 py-1 rounded-md border border-gray-200 dark:border-gray-700 text-sm" aria-label="فارسی">🇮🇷 فارسی</a>
                    <a href="<?php echo e($langUrlEn); ?>" class="px-3 py-1 rounded-md border border-gray-200 dark:border-gray-700 text-sm" aria-label="English">🇺🇸 English</a>

                    <!-- Toggle language button (submits POST to set-locale) -->
                    <form method="POST" action="<?php echo e(route('set-locale')); ?>" class="inline-block">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="locale" value="<?php echo e(app()->getLocale() === 'fa' ? 'en' : 'fa'); ?>">
                        <button type="submit" class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-600 text-white hover:bg-indigo-700 dark:bg-indigo-700 dark:hover:bg-indigo-600 transition" aria-label="Toggle language">
                            <?php if(app()->getLocale() === 'fa'): ?>
                                🇺🇸 English
                            <?php else: ?>
                                🇮🇷 فارسی
                            <?php endif; ?>
                        </button>
                    </form>
                </div>
                <!-- Notifications Bell -->
                <?php if(auth()->guard()->check()): ?>
                    <?php
                        if (auth()->user()->isMerchant()) {
                            $settingsRoute = route('merchant.settings');
                        } elseif (auth()->user()->isUser()) {
                            $settingsRoute = route('user.settings');
                        } else {
                            $settingsRoute = route('profile.edit');
                        }
                    ?>
                <div class="relative">
                    <button @click="
                        notificationOpen = !notificationOpen;
                        if (notificationOpen) {
                            fetch(window.NOTIFICATION_ENDPOINTS.list, {credentials: 'same-origin'})
                                .then(r => r.json())
                                .then(data => {
                                    console.log('Notifications fetched (nav):', data);
                                    notifications = data;
                                    // Update badge count based on unread notifications returned
                                    try { notificationCount = notifications.filter(n => !n.read).length; } catch(e){}
                                }).catch(err=>{ console.error('Notifications fetch error (nav):', err); });
                        }
                    " class="relative inline-flex items-center justify-center p-2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 focus:outline-none transition">
                        <i class="fas fa-bell text-2xl"></i>
                        <span x-show="notificationCount > 0" class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-500 rounded-full" x-text="notificationCount"></span>
                    </button>

                    <div x-show="notificationOpen" @click.away="notificationOpen = false" class="absolute top-full mt-2 w-[min(24rem,calc(100vw-2rem))] max-w-[calc(100vw-2rem)] bg-white dark:bg-gray-800 rounded-lg shadow-xl z-50 max-h-[80vh] overflow-y-auto border border-gray-200 dark:border-gray-700" style="left: <?php echo e(app()->getLocale() === 'fa' ? '0' : 'auto'); ?>; right: <?php echo e(app()->getLocale() === 'fa' ? 'auto' : '0'); ?>; direction: <?php echo e(app()->getLocale() === 'fa' ? 'rtl' : 'ltr'); ?>;">
                        <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex flex-wrap items-center justify-between gap-2">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100"><?php echo e(__('Notifications')); ?></h3>
                            <button @click="
                                fetch(window.NOTIFICATION_ENDPOINTS.markAll, {method: 'POST', credentials: 'same-origin', headers: {'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>'}})
                                    .then(() => {
                                        notificationOpen = false;
                                        // refresh unread count and list
                                        fetch(window.NOTIFICATION_ENDPOINTS.unread, {credentials: 'same-origin'}).then(r => r.json()).then(data => notificationCount = data.count);
                                        notifications = [];
                                    }).catch(()=>{});
                                                        " class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 font-semibold"><?php echo e(__('Mark all as read')); ?></button>
                        </div>

                        <template x-if="notifications.length === 0">
                            <div class="p-8 text-center">
                                <i class="fas fa-inbox text-3xl text-gray-300 dark:text-gray-600 mb-3"></i>
                                <p class="text-gray-500 dark:text-gray-400"><?php echo e(__('No notifications')); ?></p>
                            </div>
                        </template>

                        <div class="divide-y divide-gray-200 dark:divide-gray-700">
                            <template x-for="notification in notifications" :key="notification.id">
                                <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition" :class="{'bg-indigo-50 dark:bg-gray-700': !notification.read}">
                                    <div class="flex items-start gap-3">
                                        <div class="mt-1">
                                            <i :class="'fas ' + notification.icon" class="text-xl" :style="'color: ' + (notification.type === 'success' ? '#10b981' : notification.type === 'error' ? '#ef4444' : notification.type === 'warning' ? '#f59e0b' : '#3b82f6')"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="font-semibold text-gray-800 dark:text-gray-100 break-words whitespace-normal" x-text="notification.title"></p>
                                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 break-words whitespace-normal" x-text="notification.message"></p>
                                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-2" x-text="notification.created_at"></p>
                                        </div>
                                        <button @click="
                                            fetch(window.NOTIFICATION_ENDPOINTS.base + '/' + notification.id + '/delete', {method: 'POST', credentials: 'same-origin', headers: {'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>'}})
                                                .then(() => {
                                                    notifications = notifications.filter(n => n.id !== notification.id);
                                                    fetch(window.NOTIFICATION_ENDPOINTS.unread, {credentials: 'same-origin'}).then(r => r.json()).then(data => notificationCount = data.count);
                                                }).catch(()=>{});
                                        " class="text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 transition flex-shrink-0">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <button @click="toggleTheme()" class="inline-flex items-center justify-center p-2 rounded-md border border-gray-200 dark:border-gray-700 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition" aria-label="Toggle theme">
                    <i :class="theme ? 'fas fa-sun text-yellow-500' : 'fas fa-moon text-indigo-500'"></i>
                </button>

                <?php if(auth()->guard()->check()): ?>
                <?php if (isset($component)) { $__componentOriginaldf8083d4a852c446488d8d384bbc7cbe = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaldf8083d4a852c446488d8d384bbc7cbe = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dropdown','data' => ['align' => 'right','width' => '48']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dropdown'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['align' => 'right','width' => '48']); ?>
                     <?php $__env->slot('trigger', null, []); ?> 
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-200 focus:outline-none transition ease-in-out duration-150">
                                                    <?php if(auth()->user()->avatar): ?>
                                                        <img src="<?php echo e(asset('storage/' . auth()->user()->avatar)); ?>" alt="avatar" class="w-8 h-8 rounded-full object-cover me-2">
                                                    <?php else: ?>
                                                        <div class="w-8 h-8 bg-indigo-600 dark:bg-indigo-700 rounded-full flex items-center justify-center text-white text-xs font-bold me-2"><?php echo e(substr(Auth::user()->name, 0, 1)); ?></div>
                                                    <?php endif; ?>
                                                    <div><?php echo e(Auth::user()->name); ?></div>

                                                    <div class="ms-1">
                                                        <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                        </svg>
                                                    </div>
                                                </button>
                     <?php $__env->endSlot(); ?>

                     <?php $__env->slot('content', null, []); ?> 
                        <?php if (isset($component)) { $__componentOriginal68cb1971a2b92c9735f83359058f7108 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal68cb1971a2b92c9735f83359058f7108 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dropdown-link','data' => ['href' => ''.e($settingsRoute).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dropdown-link'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => ''.e($settingsRoute).'']); ?>
                            <?php echo e(__('Profile')); ?>

                         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal68cb1971a2b92c9735f83359058f7108)): ?>
<?php $attributes = $__attributesOriginal68cb1971a2b92c9735f83359058f7108; ?>
<?php unset($__attributesOriginal68cb1971a2b92c9735f83359058f7108); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal68cb1971a2b92c9735f83359058f7108)): ?>
<?php $component = $__componentOriginal68cb1971a2b92c9735f83359058f7108; ?>
<?php unset($__componentOriginal68cb1971a2b92c9735f83359058f7108); ?>
<?php endif; ?>

                        <!-- Authentication -->
                        <form method="POST" action="<?php echo e(route('logout')); ?>">
                            <?php echo csrf_field(); ?>

                            <?php if (isset($component)) { $__componentOriginal68cb1971a2b92c9735f83359058f7108 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal68cb1971a2b92c9735f83359058f7108 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dropdown-link','data' => ['href' => route('logout'),'onclick' => 'event.preventDefault();
                                                this.closest(\'form\').submit();']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dropdown-link'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('logout')),'onclick' => 'event.preventDefault();
                                                this.closest(\'form\').submit();']); ?>
                                <?php echo e(__('Log Out')); ?>

                             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal68cb1971a2b92c9735f83359058f7108)): ?>
<?php $attributes = $__attributesOriginal68cb1971a2b92c9735f83359058f7108; ?>
<?php unset($__attributesOriginal68cb1971a2b92c9735f83359058f7108); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal68cb1971a2b92c9735f83359058f7108)): ?>
<?php $component = $__componentOriginal68cb1971a2b92c9735f83359058f7108; ?>
<?php unset($__componentOriginal68cb1971a2b92c9735f83359058f7108); ?>
<?php endif; ?>
                        </form>
                     <?php $__env->endSlot(); ?>
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginaldf8083d4a852c446488d8d384bbc7cbe)): ?>
<?php $attributes = $__attributesOriginaldf8083d4a852c446488d8d384bbc7cbe; ?>
<?php unset($__attributesOriginaldf8083d4a852c446488d8d384bbc7cbe); ?>
<?php endif; ?>
<?php if (isset($__componentOriginaldf8083d4a852c446488d8d384bbc7cbe)): ?>
<?php $component = $__componentOriginaldf8083d4a852c446488d8d384bbc7cbe; ?>
<?php unset($__componentOriginaldf8083d4a852c446488d8d384bbc7cbe); ?>
<?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = !open" aria-controls="mobile-menu" :aria-expanded="open.toString()" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-700 focus:text-gray-500 dark:focus:text-gray-400 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': !open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': !open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Drawer Overlay -->
    <div x-show="open" x-cloak class="fixed inset-0 z-40 bg-black/40 sm:hidden transition-opacity duration-200" @click="open = false"></div>

    <!-- Mobile Drawer -->
    <aside x-show="open" x-cloak :class="open ? 'translate-x-0' : 'translate-x-full'" @click.outside="open = false" class="fixed inset-y-0 right-0 z-50 w-80 max-w-xs transform overflow-y-auto bg-white dark:bg-gray-900 shadow-2xl transition-transform duration-300 sm:hidden">
        <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
            <a href="<?php echo e(route('dashboard')); ?>" class="flex items-center gap-3">
                <?php if (isset($component)) { $__componentOriginal8892e718f3d0d7a916180885c6f012e7 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8892e718f3d0d7a916180885c6f012e7 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.application-logo','data' => ['class' => 'block h-9 w-auto fill-current text-gray-800 dark:text-gray-200']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('application-logo'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'block h-9 w-auto fill-current text-gray-800 dark:text-gray-200']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8892e718f3d0d7a916180885c6f012e7)): ?>
<?php $attributes = $__attributesOriginal8892e718f3d0d7a916180885c6f012e7; ?>
<?php unset($__attributesOriginal8892e718f3d0d7a916180885c6f012e7); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8892e718f3d0d7a916180885c6f012e7)): ?>
<?php $component = $__componentOriginal8892e718f3d0d7a916180885c6f012e7; ?>
<?php unset($__componentOriginal8892e718f3d0d7a916180885c6f012e7); ?>
<?php endif; ?>
                <span class="text-base font-semibold text-gray-900 dark:text-gray-100"><?php echo e(config('app.name', 'CryptoPay')); ?></span>
            </a>
            <button @click="open = false" class="inline-flex items-center justify-center p-2 rounded-md text-gray-500 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none transition">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="p-4 space-y-3">
            <?php if (isset($component)) { $__componentOriginald69b52d99510f1e7cd3d80070b28ca18 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald69b52d99510f1e7cd3d80070b28ca18 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.responsive-nav-link','data' => ['href' => route('dashboard'),'active' => request()->routeIs('dashboard')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('responsive-nav-link'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('dashboard')),'active' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request()->routeIs('dashboard'))]); ?>
                <?php echo e(__('Dashboard')); ?>

             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald69b52d99510f1e7cd3d80070b28ca18)): ?>
<?php $attributes = $__attributesOriginald69b52d99510f1e7cd3d80070b28ca18; ?>
<?php unset($__attributesOriginald69b52d99510f1e7cd3d80070b28ca18); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald69b52d99510f1e7cd3d80070b28ca18)): ?>
<?php $component = $__componentOriginald69b52d99510f1e7cd3d80070b28ca18; ?>
<?php unset($__componentOriginald69b52d99510f1e7cd3d80070b28ca18); ?>
<?php endif; ?>
        </div>

        <div class="border-t border-gray-200 dark:border-gray-700 p-4 space-y-3">
            <!-- Mobile language toggle (visible in drawer) -->
            <div class="flex items-center gap-2">
                <form method="POST" action="<?php echo e(route('set-locale')); ?>" class="inline-block w-full">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="locale" value="<?php echo e(app()->getLocale() === 'fa' ? 'en' : 'fa'); ?>">
                    <button type="submit" class="w-full text-center px-3 py-2 rounded-md border border-gray-200 dark:border-gray-700 text-sm bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        <?php if(app()->getLocale() === 'fa'): ?>
                            🇺🇸 English
                        <?php else: ?>
                            🇮🇷 فارسی
                        <?php endif; ?>
                    </button>
                </form>
            </div>
            <?php if(auth()->guard()->check()): ?>
                <div class="space-y-1">
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100"><?php echo e(Auth::user()->name); ?></p>
                    <p class="text-xs text-gray-500 dark:text-gray-400"><?php echo e(Auth::user()->email); ?></p>
                </div>

                <?php if (isset($component)) { $__componentOriginald69b52d99510f1e7cd3d80070b28ca18 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald69b52d99510f1e7cd3d80070b28ca18 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.responsive-nav-link','data' => ['href' => ''.e($settingsRoute).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('responsive-nav-link'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => ''.e($settingsRoute).'']); ?>
                    <?php echo e(__('Profile')); ?>

                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald69b52d99510f1e7cd3d80070b28ca18)): ?>
<?php $attributes = $__attributesOriginald69b52d99510f1e7cd3d80070b28ca18; ?>
<?php unset($__attributesOriginald69b52d99510f1e7cd3d80070b28ca18); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald69b52d99510f1e7cd3d80070b28ca18)): ?>
<?php $component = $__componentOriginald69b52d99510f1e7cd3d80070b28ca18; ?>
<?php unset($__componentOriginald69b52d99510f1e7cd3d80070b28ca18); ?>
<?php endif; ?>

                <form method="POST" action="<?php echo e(route('logout')); ?>">
                    <?php echo csrf_field(); ?>
                    <?php if (isset($component)) { $__componentOriginald69b52d99510f1e7cd3d80070b28ca18 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald69b52d99510f1e7cd3d80070b28ca18 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.responsive-nav-link','data' => ['href' => route('logout'),'onclick' => 'event.preventDefault(); this.closest(\'form\').submit();']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('responsive-nav-link'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('logout')),'onclick' => 'event.preventDefault(); this.closest(\'form\').submit();']); ?>
                        <?php echo e(__('Log Out')); ?>

                     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald69b52d99510f1e7cd3d80070b28ca18)): ?>
<?php $attributes = $__attributesOriginald69b52d99510f1e7cd3d80070b28ca18; ?>
<?php unset($__attributesOriginald69b52d99510f1e7cd3d80070b28ca18); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald69b52d99510f1e7cd3d80070b28ca18)): ?>
<?php $component = $__componentOriginald69b52d99510f1e7cd3d80070b28ca18; ?>
<?php unset($__componentOriginald69b52d99510f1e7cd3d80070b28ca18); ?>
<?php endif; ?>
                </form>
            <?php else: ?>
                <?php if(Route::has('login')): ?>
                    <div class="space-y-2">
                        <a href="<?php echo e(route('login')); ?>" class="block w-full text-center rounded-lg bg-indigo-600 px-4 py-3 text-sm font-semibold text-white hover:bg-indigo-700 transition"><?php echo e(__('Login')); ?></a>
                        <?php if(Route::has('register')): ?>
                            <a href="<?php echo e(route('register')); ?>" class="block w-full text-center rounded-lg border border-gray-200 px-4 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800 transition"><?php echo e(__('Register')); ?></a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <button @click="toggleTheme()" class="w-full inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 transition">
                <i :class="theme ? 'fas fa-sun text-yellow-500' : 'fas fa-moon text-indigo-600'"></i>
                <span x-text="theme ? '<?php echo e(__('Light mode')); ?>' : '<?php echo e(__('Dark mode')); ?>'"></span>
            </button>
        </div>
    </aside>
</nav>
<?php /**PATH E:\smart-cryptopay\resources\views/layouts/navigation.blade.php ENDPATH**/ ?>