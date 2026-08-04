@php
    $twoFactor = \App\Models\TwoFactor::where('user_id', auth()->id())->first();
@endphp

<div class="space-y-4">
    @if (session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-200">
            {{ session('success') }}
        </div>
    @endif

    @if (session('backup_codes'))
        <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900 dark:border-blue-700 dark:bg-blue-900/20 dark:text-blue-100">
            <p class="font-semibold mb-2">کدهای پشتیبان جدید</p>
            <div class="flex flex-wrap gap-2">
                @foreach (session('backup_codes') as $backupCode)
                    <span class="rounded-full border border-blue-300 bg-white px-2 py-1 font-mono text-xs text-blue-900 dark:border-blue-600 dark:bg-slate-800 dark:text-blue-100">
                        {{ $backupCode }}
                    </span>
                @endforeach
            </div>
        </div>
    @endif

    <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-700 transition-colors duration-300">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h4 class="font-semibold text-gray-800 dark:text-gray-100 mb-1">احراز هویت دو مرحله‌ای</h4>
                <p class="text-sm text-gray-600 dark:text-gray-400">برای افزایش امنیت حساب، 2FA را فعال یا غیرفعال کنید.</p>
            </div>
            @if ($twoFactor && $twoFactor->enabled_at)
                <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200">فعال</span>
            @else
                <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700 dark:bg-amber-900/40 dark:text-amber-200">غیرفعال</span>
            @endif
        </div>

        <div class="mt-4 flex flex-wrap gap-3">
            @php
                $twoRoute = auth()->user()->isMerchant() ? 'merchant.2fa.show' : '2fa.show';
            @endphp
            <a href="{{ route($twoRoute) }}" class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 transition-colors duration-300">
                {{ $twoFactor && $twoFactor->enabled_at ? 'مدیریت 2FA' : 'فعال‌سازی 2FA' }}
            </a>
        </div>
    </div>

    <div class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-700 transition-colors duration-300">
        <h4 class="font-semibold text-gray-800 dark:text-gray-100 mb-2">تأیید ایمیل</h4>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">وضعیت ایمیل شما در سیستم ثبت شده است.</p>

        @if (auth()->user()->hasVerifiedEmail())
            <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200">ایمیل تأیید شده</span>
        @else
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <span class="inline-flex rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700 dark:bg-amber-900/40 dark:text-amber-200">ایمیل تأیید نشده</span>
                <a href="{{ route('verification.notice') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-300 dark:hover:text-indigo-200">
                    ارسال لینک تأیید
                </a>
            </div>
        @endif
    </div>
</div>
