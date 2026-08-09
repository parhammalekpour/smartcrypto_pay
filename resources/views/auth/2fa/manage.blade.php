<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'fa' ? 'rtl' : 'ltr' }}" class="h-full bg-[#071123]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CryptoPay | مدیریت 2FA</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            background: radial-gradient(circle at top left, rgba(59, 130, 246, 0.18), transparent 24%),
                        radial-gradient(circle at bottom right, rgba(168, 85, 247, 0.16), transparent 18%),
                        linear-gradient(180deg, #020617 0%, #071123 100%);
        }
    </style>
</head>
<body class="min-h-screen text-slate-100 antialiased">
    <div class="min-h-screen flex items-center justify-center px-4 py-10">
        <div class="w-full max-w-5xl rounded-[32px] border border-white/10 bg-slate-900/90 p-6 shadow-2xl sm:p-8 lg:p-10">
            <div class="rounded-[28px] border border-white/10 bg-white/5 p-7 sm:p-8">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs uppercase tracking-[0.28em] text-slate-400">CryptoPay</p>
                        <h1 class="mt-3 text-3xl font-bold text-white">مدیریت احراز هویت دو مرحله‌ای</h1>
                    </div>
                    @if(isset($two) && $two->enabled_at)
                        <span class="rounded-full border border-emerald-400/30 bg-emerald-500/10 px-3 py-1 text-xs font-semibold text-emerald-300">2FA فعال</span>
                    @else
                        <span class="rounded-full border border-amber-400/30 bg-amber-500/10 px-3 py-1 text-xs font-semibold text-amber-300">2FA غیرفعال</span>
                    @endif
                </div>

                <p class="mt-5 text-sm leading-7 text-slate-300">در این بخش می‌توانید وضعیت امنیت حساب خود را بررسی کنید و در صورت نیاز، احراز هویت دو مرحله‌ای را فعال یا غیرفعال نمایید.</p>

                @php
                    $isMerchantContext = auth()->check() && (auth()->user()->isMerchant() || request()->is('merchant*'));
                    $disableRoute = $isMerchantContext ? 'merchant.2fa.disable' : '2fa.disable';
                    $showRoute = $isMerchantContext ? 'merchant.2fa.show' : '2fa.show';
                @endphp

                @if(isset($two) && $two->enabled_at)
                    <div class="mt-8 rounded-[1.7rem] border border-emerald-400/20 bg-emerald-500/10 p-6">
                        <h2 class="text-xl font-semibold text-white">2FA فعال است</h2>
                        <p class="mt-3 text-sm leading-7 text-emerald-100/90">تمام تراکنش‌های حساس حساب شما با کدهای Google Authenticator محافظت می‌شوند.</p>
                        <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                            <a href="{{ auth()->user()->isMerchant() ? route('merchant.settings') : route('user.settings') }}" class="inline-flex items-center justify-center rounded-[1.3rem] bg-sky-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-sky-500/20 transition hover:opacity-95">بازگشت به تنظیمات</a>
                            <form method="POST" action="{{ route($disableRoute) }}" class="inline-flex">
                                @csrf
                                <button type="submit" class="inline-flex items-center justify-center rounded-[1.3rem] border border-red-300/40 bg-red-500/10 px-5 py-3 text-sm font-semibold text-red-200 transition hover:bg-red-500/20">غیرفعال کردن 2FA</button>
                            </form>
                        </div>
                    </div>
                @else
                    <div class="mt-8 rounded-[1.7rem] border border-amber-400/20 bg-amber-500/10 p-6">
                        <h2 class="text-xl font-semibold text-white">2FA غیرفعال است</h2>
                        <p class="mt-3 text-sm leading-7 text-amber-100/90">برای افزایش امنیت حساب، فعال‌سازی احراز هویت دو مرحله‌ای پیشنهاد می‌شود.</p>
                        <div class="mt-6">
                            <a href="{{ route($showRoute) }}" class="inline-flex items-center justify-center rounded-[1.3rem] bg-sky-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-sky-500/20 transition hover:opacity-95">فعالسازی 2FA</a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</body>
</html>
