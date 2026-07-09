@extends('layouts.dashboard')

@section('title', 'تنظیمات')
@section('page-title', 'تنظیمات')
@section('page-subtitle', 'مدیریت تنظیمات حساب و امنیت')

@section('content')
<div class="max-w-6xl">
    <!-- Error Messages -->
    @if ($errors->any())
        <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 dark:border-red-800 rounded">
            <ul class="text-red-700 dark:text-red-200 text-sm font-medium">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Success Messages -->
    @if (session('success'))
        <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500 dark:border-green-800 rounded">
            <p class="text-green-700 dark:text-green-200 font-medium">{{ session('success') }}</p>
        </div>
    @endif

    <!-- Settings Container -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden transition-colors duration-300">
        <!-- Tab Navigation -->
        <div class="flex border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
            <button onclick="switchTab('general')" id="tab-general" class="tab-btn flex-1 px-4 py-4 text-center font-semibold text-gray-700 dark:text-gray-300 border-b-4 border-indigo-600 dark:border-indigo-500 hover:bg-gray-100 dark:hover:bg-gray-800 transition active-tab">
                <i class="fas fa-user ml-2"></i>عمومی
            </button>
            <button onclick="switchTab('security')" id="tab-security" class="tab-btn flex-1 px-4 py-4 text-center font-semibold text-gray-600 dark:text-gray-400 border-b-4 border-transparent hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                <i class="fas fa-lock ml-2"></i>امنیت
            </button>
            <button onclick="switchTab('notifications')" id="tab-notifications" class="tab-btn flex-1 px-4 py-4 text-center font-semibold text-gray-600 dark:text-gray-400 border-b-4 border-transparent hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                <i class="fas fa-bell ml-2"></i>اطلاع رسانی
            </button>
            <button onclick="switchTab('privacy')" id="tab-privacy" class="tab-btn flex-1 px-4 py-4 text-center font-semibold text-gray-600 dark:text-gray-400 border-b-4 border-transparent hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                <i class="fas fa-shield-alt ml-2"></i>حریم خصوصی
            </button>
        </div>

        <!-- Tab Content -->
        <div class="p-8">
            <!-- General Tab -->
            <div id="general" class="tab-content">
                <form action="{{ route('settings.update') }}" method="POST" class="space-y-6">
                    @csrf
                    @method('PATCH')
                    
                    <!-- Profile Section -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">اطلاعات پروفایل</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">نام کامل</label>
                                <input type="text" name="name" value="{{ old('name', auth()->user()->name) }}" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent transition-colors duration-300" required>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">ایمیل</label>
                                <input type="email" name="email" value="{{ old('email', auth()->user()->email) }}" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent transition-colors duration-300" required>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">شماره تلفن</label>
                                <input type="tel" name="phone" value="{{ old('phone', auth()->user()->phone) }}" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent transition-colors duration-300" placeholder="اختیاری">
                            </div>
                        </div>
                    </div>

                    <!-- Display Settings -->
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">تنظیمات نمایش</h3>
                        <div class="space-y-4">
                            <!-- Show Balance -->
                            <label class="flex items-center p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition">
                                <input type="checkbox" name="show_balance" value="1" @if(auth()->user()->show_balance) checked @endif class="w-5 h-5 text-indigo-600 dark:text-indigo-500 rounded focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400">
                                <div class="mr-3">
                                    <p class="font-semibold text-gray-800 dark:text-gray-100">نمایش موجودی</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">موجودی کل را در صفحه اصلی نمایش دهید</p>
                                </div>
                            </label>

                            <!-- Show Transactions -->
                            <label class="flex items-center p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition">
                                <input type="checkbox" name="show_transactions" value="1" @if(auth()->user()->show_transactions) checked @endif class="w-5 h-5 text-indigo-600 dark:text-indigo-500 rounded focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400">
                                <div class="mr-3">
                                    <p class="font-semibold text-gray-800 dark:text-gray-100">نمایش تاریخچه</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">آخرین تراکنش ها را نمایش دهید</p>
                                </div>
                            </label>

                            <!-- Dark Mode -->
                            <label class="flex items-center p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition" id="darkModeLabel">
                                <input type="checkbox" name="dark_mode" value="1" @if(auth()->user()->dark_mode) checked @endif id="darkModeToggle" class="w-5 h-5 text-indigo-600 dark:text-indigo-500 rounded focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400">
                                <div class="mr-3">
                                    <p class="font-semibold text-gray-800 dark:text-gray-100">حالت تاریک</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">رابط تاریک را فعال کنید</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="flex justify-end pt-6 border-t border-gray-200 dark:border-gray-700">
                        <button type="submit" class="px-8 py-3 bg-indigo-600 dark:bg-indigo-700 text-white font-semibold rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 transition-colors duration-300">
                            <i class="fas fa-save ml-2"></i>ذخیره تغییرات
                        </button>
                    </div>
                </form>
            </div>

            <!-- Security Tab -->
            <div id="security" class="tab-content hidden">
                <form action="{{ route('password.update') }}" method="POST" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">تغییر رمز عبور</h3>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">رمز عبور فعلی</label>
                        <input type="password" name="current_password" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent transition-colors duration-300" required>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">رمز عبور جدید</label>
                            <input type="password" name="password" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent transition-colors duration-300" required>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">تایید رمز عبور</label>
                            <input type="password" name="password_confirmation" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent transition-colors duration-300" required>
                        </div>
                    </div>

                    <div class="flex justify-end pt-6 border-t border-gray-200 dark:border-gray-700">
                        <button type="submit" class="px-8 py-3 bg-indigo-600 dark:bg-indigo-700 text-white font-semibold rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 transition-colors duration-300">
                            <i class="fas fa-key ml-2"></i>تغییر رمز عبور
                        </button>
                    </div>
                </form>
            </div>

            <!-- Notifications Tab -->
            <div id="notifications" class="tab-content hidden">
                <form action="{{ route('settings.update') }}" method="POST" class="space-y-6">
                    @csrf
                    @method('PATCH')

                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">تنظیمات اطلاع رسانی</h3>

                    <!-- Enable Notifications -->
                    <label class="flex items-center p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition">
                        <input type="checkbox" name="notifications_enabled" value="1" @if(auth()->user()->notifications_enabled) checked @endif class="w-5 h-5 text-indigo-600 dark:text-indigo-500 rounded focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400">
                        <div class="mr-3">
                            <p class="font-semibold text-gray-800 dark:text-gray-100">فعال‌سازی اطلاع‌رسانی</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">دریافت اطلاعات از طریق ایمیل</p>
                        </div>
                    </label>

                    <!-- Notification Email -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">ایمیل برای اطلاع رسانی</label>
                        <input type="email" name="notifications_email" value="{{ old('notifications_email', auth()->user()->notifications_email) }}" class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent transition-colors duration-300" placeholder="اختیاری">
                    </div>

                    <!-- Notification Types -->
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                        <h4 class="font-semibold text-gray-800 dark:text-gray-100 mb-4">نوع اطلاع رسانی ها</h4>
                        <div class="space-y-3">
                            <label class="flex items-center p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition">
                                <input type="checkbox" name="notify_updates" class="w-5 h-5 text-indigo-600 dark:text-indigo-500 rounded focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400">
                                <div class="mr-3">
                                    <p class="font-semibold text-gray-800 dark:text-gray-100">اپدیت ها</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">اطلاع از بروزرسانی های سایت</p>
                                </div>
                            </label>

                            <label class="flex items-center p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition">
                                <input type="checkbox" name="notify_transactions" class="w-5 h-5 text-indigo-600 dark:text-indigo-500 rounded focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400">
                                <div class="mr-3">
                                    <p class="font-semibold text-gray-800 dark:text-gray-100">تراکنش ها</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">اطلاع از ارسال و دریافت</p>
                                </div>
                            </label>

                            <label class="flex items-center p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition">
                                <input type="checkbox" name="notify_security" class="w-5 h-5 text-indigo-600 dark:text-indigo-500 rounded focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400">
                                <div class="mr-3">
                                    <p class="font-semibold text-gray-800 dark:text-gray-100">امنیت</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">اطلاع رسانی امنیتی</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="flex justify-end pt-6 border-t border-gray-200 dark:border-gray-700">
                        <button type="submit" class="px-8 py-3 bg-indigo-600 dark:bg-indigo-700 text-white font-semibold rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 transition-colors duration-300">
                            <i class="fas fa-save ml-2"></i>ذخیره تنظیمات
                        </button>
                    </div>
                </form>
            </div>

            <!-- Privacy Tab -->
            <div id="privacy" class="tab-content hidden">
                <form action="{{ route('settings.update') }}" method="POST" class="space-y-6">
                    @csrf
                    @method('PATCH')

                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">حریم خصوصی و امنیت</h3>

                    <!-- Two-Factor Authentication -->
                    <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-700 transition-colors duration-300">
                        <div class="flex items-start justify-between mb-2">
                            <div>
                                <h4 class="font-semibold text-gray-800 dark:text-gray-100">احراز هویت دو مرحله ای</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">افزایش امنیت حساب با فعال کردن تایید دو مرحله ای</p>
                            </div>
                        </div>
                        <label class="flex items-center cursor-pointer mt-3">
                            <input type="checkbox" name="notifications_2fa" value="1" @if(auth()->user()->notifications_2fa) checked @endif class="w-5 h-5 text-indigo-600 dark:text-indigo-500 rounded focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400">
                            <span class="mr-3 font-semibold text-gray-700 dark:text-gray-300">فعال‌سازی</span>
                        </label>
                    </div>

                    <!-- Email Verification -->
                    <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-700 transition-colors duration-300">
                        <h4 class="font-semibold text-gray-800 dark:text-gray-100 mb-2">تایید ایمیل</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                            @if(auth()->user()->email_verified_at)
                                <i class="fas fa-check-circle text-green-600 dark:text-green-400 ml-1"></i>ایمیل شما تایید شده است
                            @else
                                <i class="fas fa-exclamation-circle text-yellow-600 dark:text-yellow-400 ml-1"></i>ایمیل شما هنوز تایید نشده است
                            @endif
                        </p>
                        @if(!auth()->user()->email_verified_at)
                            <form action="{{ route('verification.send') }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="px-4 py-2 bg-indigo-600 dark:bg-indigo-700 text-white font-semibold rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 transition-colors duration-300 text-sm">
                                    ارسال لینک تایید
                                </button>
                            </form>
                        @endif
                    </div>

                    <!-- Session Management -->
                    <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-700 transition-colors duration-300">
                        <h4 class="font-semibold text-gray-800 dark:text-gray-100 mb-2">مدیریت جلسات</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">از تمام دستگاه ها خارج شوید</p>
                        <form action="{{ route('logout-all-devices') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="px-4 py-2 bg-red-600 dark:bg-red-700 text-white font-semibold rounded-lg hover:bg-red-700 dark:hover:bg-red-600 transition-colors duration-300 text-sm">
                                خروج از همه دستگاه ها
                            </button>
                        </form>
                    </div>

                    <div class="flex justify-end pt-6 border-t border-gray-200 dark:border-gray-700">
                        <button type="submit" class="px-8 py-3 bg-indigo-600 dark:bg-indigo-700 text-white font-semibold rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 transition-colors duration-300">
                            <i class="fas fa-save ml-2"></i>ذخیره تنظیمات
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function switchTab(tab) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    
    // Remove active styling from all buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('border-indigo-600', 'text-gray-800', 'dark:text-gray-100');
        btn.classList.add('border-transparent', 'text-gray-600', 'dark:text-gray-400');
    });
    
    // Show selected tab
    document.getElementById(tab).classList.remove('hidden');
    
    // Add active styling to clicked button
    const btn = document.getElementById('tab-' + tab);
    btn.classList.remove('border-transparent', 'text-gray-600', 'dark:text-gray-400');
    btn.classList.add('border-indigo-600', 'text-gray-800', 'dark:text-gray-100');
}

// Initialize dark mode functionality
document.addEventListener('DOMContentLoaded', function() {
    const darkModeToggle = document.getElementById('darkModeToggle');
    const settingsForm = document.querySelector('form[action*="settings.update"]');
    
    if (darkModeToggle) {
        // Handle real-time toggle (immediate visual change)
        darkModeToggle.addEventListener('change', function() {
            if (this.checked) {
                document.documentElement.classList.add('dark');
                localStorage.setItem('darkMode', 'true');
            } else {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('darkMode', 'false');
            }
        });
        
        // Set initial state
        const htmlElement = document.documentElement;
        darkModeToggle.checked = htmlElement.classList.contains('dark');
    }
    
    // Handle form submission
    if (settingsForm) {
        settingsForm.addEventListener('submit', function(e) {
            // Save dark mode state to localStorage before form submits
            const darkModeInput = document.getElementById('darkModeToggle');
            if (darkModeInput) {
                if (darkModeInput.checked) {
                    localStorage.setItem('darkMode', 'true');
                } else {
                    localStorage.setItem('darkMode', 'false');
                }
            }
            
            // Let the form submit normally
            // The next page load will use localStorage value
        });
    }
});
</script>
@endsection
