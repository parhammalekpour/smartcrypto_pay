<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'fa' ? 'rtl' : 'ltr' }}" class="h-full bg-[#071123]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CryptoPay | کدهای پشتیبان 2FA</title>
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
                        <h1 class="mt-3 text-3xl font-bold text-white">کدهای پشتیبان 2FA</h1>
                    </div>
                    <span class="rounded-full border border-emerald-400/30 bg-emerald-500/10 px-3 py-1 text-xs font-semibold text-emerald-300">فعال</span>
                </div>

                <p class="mt-5 text-sm leading-7 text-slate-300">احراز هویت دو مرحله‌ای برای حساب شما فعال است. این کدها را در مکانی امن ذخیره کنید؛ هر کد فقط یک‌بار قابل استفاده است و در صورت گم شدن دستگاه احراز هویت، می‌توانید با آن وارد حساب شوید.</p>

                <div class="mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($codes as $code)
                        <div class="rounded-[1.2rem] border border-dashed border-sky-400/30 bg-slate-950/70 px-4 py-3 font-mono text-sm text-slate-100 shadow-inner">
                            {{ $code }}
                        </div>
                    @endforeach
                </div>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route($settingsRoute) }}" class="inline-flex items-center justify-center rounded-[1.5rem] bg-sky-600 px-5 py-4 text-sm font-semibold text-white shadow-lg shadow-sky-500/20 transition hover:opacity-95">
                        بازگشت به تنظیمات
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

