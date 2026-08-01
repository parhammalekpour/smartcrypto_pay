<!DOCTYPE html>
<html dir="rtl" lang="fa" id="htmlElement" style="font-family: 'Vazirmatn', Tahoma, Arial, sans-serif;">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') - CryptoPay</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/dark-mode.css') }}">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        // Initialize dark mode IMMEDIATELY before any rendering
        (function initDarkMode() {
            const htmlElement = document.documentElement;
            const darkModeFromStorage = localStorage.getItem('darkMode');
            
            // If nothing in storage, check user preference from DB
            if (darkModeFromStorage === null) {
                if ({{ auth()->user()->dark_mode ? 'true' : 'false' }}) {
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
    <style>
        * {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .text-persian {
            font-family: 'Vazirmatn', 'Segoe UI', sans-serif;
        }

        .sidebar-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 18px;
            margin-bottom: 8px;
            color: #c7d2fe;
            border-radius: 14px;
            transition: all .25s ease;
            text-decoration: none;
            font-size: 15px;
            font-weight: 500;
            white-space: nowrap;
            flex-direction: row-reverse;
        }

        .sidebar-item i {
            width: 22px;
            text-align: center;
            font-size: 16px;
        }

        .sidebar-item:hover {
            background: rgba(255,255,255,.12);
            color: #fff;
            transform: translateX(-3px);
        }

        .sidebar-item.active {
            background: rgba(255,255,255,.18);
            color: #fff;
            box-shadow: inset 0 0 0 1px rgba(255,255,255,.15);
        }

        .profile-dropdown {
            opacity: 0;
            visibility: hidden;
            transition: all .2s ease;
            position: absolute;
            right: 0;
            top: 100%;
            margin-top: .5rem;
        }

        .profile-button:hover~.profile-dropdown,
        .profile-dropdown:hover {
            opacity: 1;
            visibility: visible;
        }

        /* Fix for input text color - UNIVERSAL */
        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="tel"],
        input[type="number"],
        input[type="url"],
        input[type="search"],
        textarea,
        select {
            color: #1f2937 !important;
        }

        input[type="text"]::placeholder,
        input[type="email"]::placeholder,
        input[type="password"]::placeholder,
        input[type="tel"]::placeholder,
        input[type="number"]::placeholder,
        textarea::placeholder {
            color: #9ca3af !important;
        }

        /* Dark mode inputs */
        .dark input[type="text"],
        .dark input[type="email"],
        .dark input[type="password"],
        .dark input[type="tel"],
        .dark input[type="number"],
        .dark input[type="url"],
        .dark input[type="search"],
        .dark textarea,
        .dark select {
            color: #1f2937 !important;
            background-color: #f3f4f6 !important;
        }
    </style>
    @stack('styles')
</head>
<body class="bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 text-persian transition-colors duration-300">
    <div class="flex h-screen bg-gray-100 dark:bg-gray-800">
        <!-- Sidebar -->
        <aside class="w-72 bg-gradient-to-b from-indigo-600 to-indigo-800 dark:from-indigo-900 dark:to-indigo-950 text-white shadow-lg flex flex-col overflow-y-auto transition-colors duration-300">
            <!-- Logo Section - Fixed -->
            <div style="padding: 24px; border-bottom: 1px solid rgba(255,255,255,.15); flex-shrink: 0;">
                <div style="display: flex; align-items: center; justify-content: center; gap: 12px; flex-direction: row-reverse;">
                    <div style="width: 48px; height: 48px; background: rgba(255,255,255,.95); border-radius: 12px; display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 16px rgba(0,0,0,.2);">
                        <i class="fas fa-coins" style="font-size: 24px; color: #4f46e5;"></i>
                    </div>
                    <div>
                        <h1 style="font-size: 22px; font-weight: 700; color: white; margin: 0;">CryptoPay</h1>
                        <p style="font-size: 12px; color: rgba(255,255,255,.7); margin: 4px 0 0 0;">Digital Crypto Wallet</p>
                    </div>
                </div>
            </div>

            <!-- Menu - Scrollable -->
            <nav class="flex-1 px-2 py-4 space-y-0 overflow-y-auto">
                @if(auth()->user()->isMerchant())
                    <!-- Merchant Menu -->
                    <a href="{{ route('merchant.dashboard') }}" class="sidebar-item @if(Route::currentRouteName() === 'merchant.dashboard') active @endif">
                        داشبورد اصلی
                    </a>

                    <a href="{{ route('merchant.payments') }}" class="sidebar-item @if(Route::currentRouteName() === 'merchant.payments') active @endif">
                        درخواست‌های پرداخت
                    </a>

                    <a href="{{ route('merchant.invoices') }}" class="sidebar-item @if(Route::currentRouteName() === 'merchant.invoices') active @endif">
                        فاکتورها
                    </a>

                    <a href="{{ route('merchant.settlements') }}" class="sidebar-item @if(Route::currentRouteName() === 'merchant.settlements') active @endif">
                        تسویه‌حساب
                    </a>

                    <a href="{{ route('merchant.customers') }}" class="sidebar-item @if(in_array(Route::currentRouteName(), ['merchant.customers', 'merchant.customers.show'])) active @endif">
                        مشتریان
                    </a>

                    <a href="{{ route('merchant.wallets') }}" class="sidebar-item @if(Route::currentRouteName() === 'merchant.wallets') active @endif">
                        کیف پول‌های من
                    </a>

                    <a href="{{ route('merchant.transactions') }}" class="sidebar-item">
                        تراکنش‌ها
                    </a>

                    <a href="{{ route('merchant.settings') }}" class="sidebar-item">
                        تنظیمات عمومی
                    </a>
                    
                @else
                    <!-- User Menu -->
                    <a href="{{ route('user.dashboard') }}" class="sidebar-item @if(Route::currentRouteName() === 'user.dashboard') active @endif">
                        <i class="fas fa-home"></i>
                        خانه
                    </a>

                    <a href="{{ route('user.wallets') }}" class="sidebar-item">
                        <i class="fas fa-wallet"></i>
                        کیف پول‌های من
                    </a>

                    <a href="{{ route('user.send') }}" class="sidebar-item">
                        <i class="fas fa-paper-plane"></i>
                        ارسال ارز دیجیتال
                    </a>

                    <a href="{{ route('user.receive') }}" class="sidebar-item">
                        <i class="fas fa-inbox"></i>
                        دریافت ارز
                    </a>

                    <a href="{{ route('user.transactions') }}" class="sidebar-item">
                        <i class="fas fa-history"></i>
                        تاریخچه تراکنش‌ها
                    </a>

                    <a href="{{ route('user.pending-payments') }}" class="sidebar-item">
                        <i class="fas fa-hourglass-end"></i>
                        پرداخت‌های در انتظار
                    </a>

                    <a href="{{ route('user.settings') }}" class="sidebar-item">
                        <i class="fas fa-cog"></i>
                        تنظیمات حساب
                    </a>
                @endif
            </nav>

            <!-- User Profile - Fixed at Bottom -->
            <div class="border-t border-indigo-500 dark:border-indigo-700 p-4 flex-shrink-0">
                <div class="flex items-center gap-3 p-3 bg-indigo-700 rounded-lg">
                    <div class="w-10 h-10 bg-indigo-300 rounded-full flex items-center justify-center text-indigo-800 font-bold text-sm flex-shrink-0">
                        {{ substr(auth()->user()->name, 0, 1) }}
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-indigo-300 truncate">{{ auth()->user()->email }}</p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Bar -->
            <header class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700 transition-colors duration-300" x-data="{ notificationOpen: false, notificationCount: 0, notifications: [] }" @load="
                setInterval(() => {
                    fetch('{{ route('notifications.unread-count') }}')
                        .then(r => r.json())
                        .then(data => $data.notificationCount = data.count);
                }, 5000);
            ">
                <div class="flex items-center justify-between px-6 py-4">
                    <!-- Page Title -->
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100">@yield('page-title')</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">@yield('page-subtitle')</p>
                    </div>

                    <!-- Top Right Actions -->
                    <div class="flex items-center gap-4">
                        <!-- Notifications Bell -->
                        <div class="relative">
                            <button @click="
                                notificationOpen = !notificationOpen;
                                if (notificationOpen) {
                                    fetch('{{ route('notifications.get') }}')
                                        .then(r => r.json())
                                        .then(data => $data.notifications = data);
                                }
                            " class="relative p-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                                <i class="fas fa-bell text-lg"></i>
                                <span x-show="notificationCount > 0" x-text="notificationCount" class="absolute top-0 right-1 w-5 h-5 bg-red-500 rounded-full text-white text-xs flex items-center justify-center font-bold"></span>
                            </button>

                            <!-- Notifications Dropdown -->
                            <div x-show="notificationOpen" @click.away="notificationOpen = false" class="absolute left-0 mt-2 w-80 bg-white dark:bg-gray-800 rounded-lg shadow-xl z-50 max-h-96 overflow-y-auto">
                                <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between sticky top-0 bg-white dark:bg-gray-800">
                                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">اعلان ها</h3>
                                    <button @click="
                                        fetch('{{ route('notifications.mark-all-read') }}', {method: 'POST', headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'}})
                                            .then(() => {
                                                notificationOpen = false;
                                                fetch('{{ route('notifications.unread-count') }}').then(r => r.json()).then(data => $data.notificationCount = data.count);
                                            });
                                    " class="text-sm text-indigo-600 hover:text-indigo-700 font-semibold">علامت گذاری همه</button>
                                </div>

                                <template x-if="notifications.length === 0">
                                    <div class="p-8 text-center">
                                        <i class="fas fa-inbox text-3xl text-gray-300 dark:text-gray-600 mb-3"></i>
                                        <p class="text-gray-500 dark:text-gray-400">اعلانی وجود ندارد</p>
                                    </div>
                                </template>

                                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <template x-for="notification in notifications" :key="notification.id">
                                        <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition" :class="{'bg-indigo-50 dark:bg-gray-700': !notification.read}">
                                            <div class="flex items-start gap-3">
                                                <div class="mt-1">
                                                    <i :class="'fas ' + notification.icon" class="text-xl" :style="'color: ' + (notification.type === 'success' ? '#10b981' : notification.type === 'error' ? '#ef4444' : notification.type === 'warning' ? '#f59e0b' : '#3b82f6')"></i>
                                                </div>
                                                <div class="flex-1">
                                                    <p class="font-semibold text-gray-800 dark:text-gray-100" x-text="notification.title"></p>
                                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1" x-text="notification.message"></p>
                                                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-2" x-text="notification.created_at"></p>
                                                </div>
                                                 <button @click="
                                                    fetch(`/notifications/${notification.id}/delete`, {method: 'POST', headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'}})
                                                        .then(() => {
                                                            notifications = notifications.filter(n => n.id !== notification.id);
                                                            fetch('{{ route('notifications.unread-count') }}').then(r => r.json()).then(data => $data.notificationCount = data.count);
                                                        });
                                                " class="text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 transition flex-shrink-0">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- Profile Dropdown -->
                        <div class="relative">
                            <button class="profile-button flex items-center gap-2 p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                                <div class="w-8 h-8 bg-indigo-600 dark:bg-indigo-700 rounded-full flex items-center justify-center text-white text-xs font-bold">
                                    {{ substr(auth()->user()->name, 0, 1) }}
                                </div>
                                <i class="fas fa-chevron-down text-xs text-gray-500 dark:text-gray-400"></i>
                            </button>

                            <!-- Dropdown Menu -->
                            <div class="profile-dropdown bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 w-40 z-50">
                                <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 px-3 py-2 text-xs text-gray-700 dark:text-gray-300 hover:bg-indigo-50 dark:hover:bg-gray-700 border-b border-gray-100 dark:border-gray-700">
                                    <i class="fas fa-user text-sm"></i>پروفایل
                                </a>
                                <a href="@if(auth()->user()->isMerchant()) {{ route('merchant.settings') }}#security @else {{ route('user.settings') }}#security @endif" class="flex items-center gap-2 px-3 py-2 text-xs text-gray-700 dark:text-gray-300 hover:bg-indigo-50 dark:hover:bg-gray-700 border-b border-gray-100 dark:border-gray-700">
                                    <i class="fas fa-lock text-sm"></i>رمز عبور
                                </a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="w-full text-right flex items-center gap-2 px-3 py-2 text-xs text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20">
                                        <i class="fas fa-sign-out-alt text-sm"></i>خروج
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto">
                <div class="p-6">
                    <!-- Flash Messages -->
                    @if(session('success'))
                        <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg flex items-start gap-3">
                            <i class="fas fa-check-circle text-green-600 dark:text-green-400 text-xl mt-0.5"></i>
                            <div>
                                <p class="font-semibold text-green-800 dark:text-green-300">موفق!</p>
                                <p class="text-sm text-green-700 dark:text-green-200 mt-1">{{ session('success') }}</p>
                            </div>
                            <button onclick="this.parentElement.remove()" class="ml-auto text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-300">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg flex items-start gap-3">
                            <i class="fas fa-exclamation-circle text-red-600 dark:text-red-400 text-xl mt-0.5"></i>
                            <div>
                                <p class="font-semibold text-red-800 dark:text-red-300">خطا!</p>
                                <p class="text-sm text-red-700 dark:text-red-200 mt-1">{{ session('error') }}</p>
                            </div>
                            <button onclick="this.parentElement.remove()" class="ml-auto text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    @endif

                    @if(session('info'))
                        <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg flex items-start gap-3">
                            <i class="fas fa-info-circle text-blue-600 dark:text-blue-400 text-xl mt-0.5"></i>
                            <div>
                                <p class="font-semibold text-blue-800 dark:text-blue-300">اطلاعات</p>
                                <p class="text-sm text-blue-700 dark:text-blue-200 mt-1">{{ session('info') }}</p>
                            </div>
                            <button onclick="this.parentElement.remove()" class="ml-auto text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    @endif
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
