<!DOCTYPE html>
<html lang="fa" dir="rtl" class="h-full bg-[#071123]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CryptoPay | ورود و ثبت‌نام</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            background: radial-gradient(circle at top left, rgba(59, 130, 246, 0.18), transparent 24%),
                        radial-gradient(circle at bottom right, rgba(168, 85, 247, 0.16), transparent 18%),
                        linear-gradient(180deg, #020617 0%, #071123 100%);
        }
        .outer-card {
            border-radius: 34px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(15, 23, 42, 0.95);
            box-shadow: 0 48px 120px rgba(0, 0, 0, 0.35);
        }
        .form-side {
            border-radius: 34px 0 0 34px;
            background: #ffffff;
        }
        .brand-side {
            border-radius: 0 34px 34px 0;
            background: rgba(7, 17, 35, 0.98);
        }
        .field-input {
            background: #f8fafc;
            color: #0f172a;
            border: 1px solid #d1d5db;
        }
        .field-input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.14);
        }
        .tab-group {
            display: flex;
            gap: 0.5rem;
        }
        .tab-button {
            border-radius: 9999px;
            transition: all 0.2s ease;
            flex: 1;
            min-width: 10rem;
            padding: 0.95rem 1.25rem;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .tab-active {
            background: #0f172a;
            color: #ffffff;
        }
        .tab-inactive {
            background: transparent;
            color: #64748b;
        }
        .brand-panel {
            border-radius: 28px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(15, 23, 42, 0.96);
            box-shadow: 0 22px 70px rgba(0, 0, 0, 0.24);
            min-height: 100%;
            display: flex;
            align-items: center;
        }
        .brand-panel h2,
        .brand-panel p,
        .brand-panel .feature-text {
            white-space: normal;
        }
        .brand-panel .feature-box {
            min-width: 0;
        }
        .brand-panel::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at 20% 18%, rgba(56, 189, 248, 0.16), transparent 28%),
                        radial-gradient(circle at 80% 80%, rgba(99, 102, 241, 0.12), transparent 24%);
            border-radius: 28px;
            pointer-events: none;
        }
        .auth-panel {
            min-height: 480px;
        }
        .outer-card {
            min-height: 620px;
            align-items: start;
        }
        .form-side {
            min-height: 620px;
        }
        .brand-side {
            min-width: 340px;
            display: flex;
            align-items: stretch;
            justify-content: center;
        }
        .brand-panel {
            height: 100%;
            max-width: 520px;
            width: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 2rem;
        }
        .brand-panel .feature-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
        }
        .brand-panel .feature-box {
            padding: 1.15rem 1.25rem;
        }
        .brand-panel h2 {
            line-height: 1.08;
        }
        .brand-panel p {
            white-space: normal;
        }
    </style>
</head>
<body class="min-h-screen text-slate-100 antialiased">
    <div class="min-h-screen flex items-center justify-center px-4 py-10">
        <div class="w-full max-w-7xl">
            <div class="outer-card overflow-hidden lg:grid lg:grid-cols-[1.2fr_0.8fr] min-h-[620px]">
                <div class="form-side p-6 sm:p-8 lg:p-10">
                    <div class="space-y-6">
                        <div class="max-w-2xl">
                            <p class="text-xs uppercase tracking-[0.3em] text-slate-500">CryptoPay</p>
                            <h1 class="mt-4 text-3xl font-bold text-slate-950">ورود و ساخت حساب جدید</h1>
                        </div>
                        <div class="tab-group rounded-full bg-slate-100 p-2 shadow-sm border border-slate-200/80">
                            <button id="tab-login" type="button" class="tab-button rounded-full font-semibold tab-active w-full">ورود</button>
                            <button id="tab-register" type="button" class="tab-button rounded-full font-semibold tab-inactive w-full">ثبت‌نام</button>
                        </div>
                    </div>

                    <div class="mt-8 rounded-[2rem] border border-slate-200/80 bg-slate-100 p-8 shadow-sm sm:p-10">
                        <div id="login-panel" class="auth-panel">
                            @if(session('status'))
                                <div class="mb-4 rounded-3xl border border-emerald-400/20 bg-emerald-500/10 p-4 text-sm text-emerald-950">{{ session('status') }}</div>
                            @endif
                            <form method="POST" action="{{ route('login') }}" class="space-y-6">
                                @csrf
                                <div>
                                    <label for="email" class="block text-sm font-semibold text-slate-700">ایمیل</label>
                                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus placeholder="example@domain.com" class="field-input mt-3 w-full rounded-[1.5rem] px-4 py-4 text-sm outline-none transition" />
                                    @error('email')<p class="mt-2 text-xs text-rose-500">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="password" class="block text-sm font-semibold text-slate-700">رمز عبور</label>
                                    <input id="password" name="password" type="password" required placeholder="رمز خود را وارد کنید" class="field-input mt-3 w-full rounded-[1.5rem] px-4 py-4 text-sm outline-none transition" />
                                    @error('password')<p class="mt-2 text-xs text-rose-500">{{ $message }}</p>@enderror
                                </div>
                                <div class="flex items-center justify-between text-sm text-slate-500">
                                    <label class="inline-flex items-center gap-2">
                                        <input type="checkbox" name="remember" class="h-4 w-4 rounded border-slate-300 bg-white text-indigo-600 focus:ring-indigo-500" />
                                        مرا به خاطر بسپار
                                    </label>
                                    @if(Route::has('password.request'))
                                        <a href="{{ route('password.request') }}" class="text-sky-600 hover:text-sky-700 transition">فراموشی رمز عبور؟</a>
                                    @endif
                                </div>
                                <button type="submit" class="w-full rounded-[1.5rem] bg-sky-600 px-5 py-4 text-sm font-semibold uppercase tracking-[0.1em] text-white shadow-lg shadow-sky-500/20 transition hover:opacity-95">ورود به حساب</button>
                            </form>
                        </div>

                        <div id="register-panel" class="auth-panel hidden">
                            <form method="POST" action="{{ route('register') }}" class="space-y-6">
                                @csrf
                                <div>
                                    <label for="name" class="block text-sm font-semibold text-slate-700">نام کامل</label>
                                    <input id="name" name="name" type="text" value="{{ old('name') }}" required placeholder="نام خود را وارد کنید" class="field-input mt-3 w-full rounded-[1.5rem] px-4 py-4 text-sm outline-none transition" />
                                    @error('name')<p class="mt-2 text-xs text-rose-500">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="email" class="block text-sm font-semibold text-slate-700">ایمیل</label>
                                    <input id="register-email" name="email" type="email" value="{{ old('email') }}" required placeholder="example@domain.com" class="field-input mt-3 w-full rounded-[1.5rem] px-4 py-4 text-sm outline-none transition" />
                                    @error('email')<p class="mt-2 text-xs text-rose-500">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="role" class="block text-sm font-semibold text-slate-700">نوع حساب</label>
                                    <select id="role" name="role" required class="field-input mt-3 w-full rounded-[1.5rem] px-4 py-4 text-sm outline-none transition">
                                        <option value="user" {{ old('role', 'user') === 'user' ? 'selected' : '' }}>Personal</option>
                                        <option value="merchant" {{ old('role') === 'merchant' ? 'selected' : '' }}>Business</option>
                                    </select>
                                    @error('role')<p class="mt-2 text-xs text-rose-500">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="password" class="block text-sm font-semibold text-slate-700">رمز عبور</label>
                                    <input id="register-password" name="password" type="password" required placeholder="رمز عبور را وارد کنید" class="field-input mt-3 w-full rounded-[1.5rem] px-4 py-4 text-sm outline-none transition" />
                                    @error('password')<p class="mt-2 text-xs text-rose-500">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="password_confirmation" class="block text-sm font-semibold text-slate-700">تایید رمز عبور</label>
                                    <input id="password_confirmation" name="password_confirmation" type="password" required placeholder="رمز را دوباره وارد کنید" class="field-input mt-3 w-full rounded-[1.5rem] px-4 py-4 text-sm outline-none transition" />
                                    @error('password_confirmation')<p class="mt-2 text-xs text-rose-500">{{ $message }}</p>@enderror
                                </div>
                                <button type="submit" class="w-full rounded-[1.5rem] bg-emerald-500 px-5 py-4 text-sm font-semibold uppercase tracking-[0.1em] text-slate-950 shadow-lg shadow-emerald-500/20 transition hover:opacity-95">ثبت‌نام</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="brand-side p-8 sm:p-10 lg:p-12 flex items-center justify-center">
                    <div class="brand-panel relative overflow-hidden rounded-[28px] border border-white/10 bg-slate-950/95 shadow-2xl shadow-slate-950/20">
                        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(56,189,248,0.16),_transparent_28%),radial-gradient(circle_at_bottom_right,_rgba(99,102,241,0.12),_transparent_24%)] rounded-[28px] pointer-events-none"></div>
                        <div class="relative z-10 flex h-full flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between gap-3 text-slate-300">
                                    <span class="text-xs uppercase tracking-[0.28em]">CryptoPay</span>
                                    <span class="rounded-full border border-slate-700/80 px-3 py-1 text-[10px] uppercase tracking-[0.28em]">EN</span>
                                </div>
                                <div class="mt-10">
                                    <h2 class="text-3xl font-bold text-white">CryptoPay پلتفرم پرداخت رمزارز</h2>
                                    <p class="mt-4 text-sm leading-6 text-slate-400">پلتفرمی برای پرداخت، کیف پول و مدیریت تراکنش‌های رمزارزی.</p>
                                </div>
                            </div>
                            <div class="mt-10 space-y-4">
                                <div class="feature-box rounded-3xl border border-white/10 bg-white/5 p-5">
                                    <p class="text-sm font-semibold text-white">پرداخت رمزارز مستقیم</p>
                                    <p class="mt-2 text-sm text-slate-400">پرداخت سریع و بدون واسطه با بیت‌کوین و اتریوم.</p>
                                </div>
                                <div class="feature-box rounded-3xl border border-white/10 bg-white/5 p-5">
                                    <p class="text-sm font-semibold text-white">کیف پول امن</p>
                                    <p class="mt-2 text-sm text-slate-400">نگهداری و مدیریت کوین‌ها با چند لایه امنیتی.</p>
                                </div>
                                <div class="feature-box rounded-3xl border border-white/10 bg-white/5 p-5">
                                    <p class="text-sm font-semibold text-white">گزارش تراکنش</p>
                                    <p class="mt-2 text-sm text-slate-400">کنترل ساده و شفاف روی تاریخچه پرداخت‌ها.</p>
                                </div>
                                <div class="feature-box rounded-3xl border border-white/10 bg-white/5 p-5">
                                    <p class="text-sm font-semibold text-white">مدیریت کاربر</p>
                                    <p class="mt-2 text-sm text-slate-400">پروفایل شخصی و تجاری با تنظیمات جداگانه.</p>
                                </div>
                                <div class="feature-box rounded-3xl border border-white/10 bg-white/5 p-5">
                                    <p class="text-sm font-semibold text-white">پشتیبانی کسب‌وکار</p>
                                    <p class="mt-2 text-sm text-slate-400">امکانات ویژه برای فروشگاه‌ها و پذیرندگان آنلاین.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const activeTab = '{{ $activeTab }}';
        const loginPanel = document.getElementById('login-panel');
        const registerPanel = document.getElementById('register-panel');
        const loginTab = document.getElementById('tab-login');
        const registerTab = document.getElementById('tab-register');

        function updateTabClasses(active) {
            if (active === 'register') {
                loginTab.classList.remove('tab-active');
                loginTab.classList.add('tab-inactive');
                registerTab.classList.add('tab-active');
                registerTab.classList.remove('tab-inactive');
            } else {
                registerTab.classList.remove('tab-active');
                registerTab.classList.add('tab-inactive');
                loginTab.classList.add('tab-active');
                loginTab.classList.remove('tab-inactive');
            }
        }

        function setActive(tab) {
            if (tab === 'register') {
                loginPanel.classList.add('hidden');
                registerPanel.classList.remove('hidden');
            } else {
                registerPanel.classList.add('hidden');
                loginPanel.classList.remove('hidden');
            }
            updateTabClasses(tab);
        }

        loginTab.addEventListener('click', () => setActive('login'));
        registerTab.addEventListener('click', () => setActive('register'));

        setActive(activeTab || 'login');
    </script>
</body>
</html>