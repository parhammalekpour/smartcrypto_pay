<!DOCTYPE html>
<html lang="fa" dir="rtl" class="h-full bg-slate-950">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CryptoPay | ورود و ثبت‌نام</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            background: radial-gradient(circle at top left, rgba(59, 130, 246, .22), transparent 25%),
                        radial-gradient(circle at bottom right, rgba(168, 85, 247, .18), transparent 20%),
                        linear-gradient(180deg, #0f172a 0%, #020617 100%);
        }
    </style>
</head>
<body class="min-h-screen text-slate-100 antialiased">
    <div class="min-h-screen flex flex-col lg:flex-row items-center justify-center px-4 py-10 gap-10">
        <div class="max-w-2xl w-full rounded-[2rem] bg-slate-900/80 border border-white/10 shadow-2xl backdrop-blur-xl p-8 lg:p-12 overflow-hidden">
            <div class="relative overflow-hidden rounded-[1.75rem] bg-slate-800/60 p-8 sm:p-10">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,#38bdf8_0%,transparent_32%),radial-gradient(circle_at_bottom_right,#a855f7_0%,transparent_30%)] opacity-70"></div>
                <div class="relative z-10">
                    <span class="inline-flex items-center gap-3 rounded-full bg-slate-100/10 px-4 py-2 text-xs uppercase tracking-[0.2em] text-slate-200/80 font-semibold">
                        CryptoPay</span>
                    <h1 class="mt-6 text-4xl sm:text-5xl font-extrabold text-white tracking-tight">ساده‌ترین تجربه ورود و ثبت‌نام به پنل کریپتو</h1>
                    <p class="mt-5 text-slate-300 text-base leading-7">صفحه ورود و ثبت‌نام کاملاً فارسی، مدرن و واکنش‌گرا با طراحی مینیمال برای وب‌سایت شما.</p>

                    <div class="mt-10 grid gap-4 sm:grid-cols-2">
                        <div class="rounded-3xl bg-white/5 p-5 border border-white/10 shadow-lg">
                            <p class="text-slate-200 font-semibold">پنل سریع</p>
                            <p class="mt-2 text-slate-400 text-sm">ورود و ثبت‌نام با کمترین کلیک انجام می‌شود.</p>
                        </div>
                        <div class="rounded-3xl bg-white/5 p-5 border border-white/10 shadow-lg">
                            <p class="text-slate-200 font-semibold">کاملاً واکنش‌گرا</p>
                            <p class="mt-2 text-slate-400 text-sm">طراحی شده برای موبایل و دسکتاپ.</p>
                        </div>
                        <div class="rounded-3xl bg-white/5 p-5 border border-white/10 shadow-lg">
                            <p class="text-slate-200 font-semibold">امن و پایدار</p>
                            <p class="mt-2 text-slate-400 text-sm">تکیه بر ساختار استاندارد لاراول.</p>
                        </div>
                        <div class="rounded-3xl bg-white/5 p-5 border border-white/10 shadow-lg">
                            <p class="text-slate-200 font-semibold">ظاهر جذاب</p>
                            <p class="mt-2 text-slate-400 text-sm">رنگ‌ها و تایپوگرافی حرفه‌ای.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="w-full max-w-xl">
            <div class="bg-slate-900/95 border border-white/10 shadow-2xl rounded-[2rem] overflow-hidden">
                <div class="flex items-center justify-between gap-4 p-5 bg-slate-950/80 border-b border-white/10">
                    <div>
                        <p class="text-xs uppercase tracking-[0.25em] text-slate-400">ورود به CryptoPay</p>
                        <h2 class="mt-2 text-2xl font-bold text-white">به حساب خود وارد شوید یا ثبت‌نام کنید</h2>
                    </div>
                    <div class="flex items-center gap-2 rounded-full bg-slate-800/80 px-3 py-2 text-sm text-slate-300">
                        <span class="inline-flex h-2 w-2 rounded-full bg-emerald-400"></span>
                        آنلاین و آماده
                    </div>
                </div>

                <div class="p-6 sm:p-8">
                    <div class="grid grid-cols-2 gap-2 mb-6">
                        <button id="tab-login" type="button" class="tab-toggler rounded-2xl py-3 text-sm font-semibold transition-colors duration-200">
                            ورود
                        </button>
                        <button id="tab-register" type="button" class="tab-toggler rounded-2xl py-3 text-sm font-semibold transition-colors duration-200">
                            ثبت‌نام
                        </button>
                    </div>

                    <div id="login-panel" class="auth-panel">
                        @if(session('status'))
                            <div class="mb-4 rounded-2xl border border-emerald-400/20 bg-emerald-500/10 p-4 text-emerald-100">
                                {{ session('status') }}
                            </div>
                        @endif
                        <form method="POST" action="{{ route('login') }}" class="space-y-5">
                            @csrf
                            <div>
                                <label class="block text-sm font-semibold text-slate-200 mb-2" for="email">ایمیل</label>
                                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus class="w-full rounded-3xl border border-slate-700 bg-slate-950/90 px-4 py-3 text-slate-100 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30" />
                                @error('email')<p class="mt-2 text-xs text-rose-300">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-200 mb-2" for="password">رمز عبور</label>
                                <input id="password" name="password" type="password" required class="w-full rounded-3xl border border-slate-700 bg-slate-950/90 px-4 py-3 text-slate-100 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30" />
                                @error('password')<p class="mt-2 text-xs text-rose-300">{{ $message }}</p>@enderror
                            </div>
                            <div class="flex items-center justify-between gap-4 text-sm text-slate-400">
                                <label class="inline-flex items-center gap-2">
                                    <input type="checkbox" name="remember" class="h-4 w-4 rounded border-slate-600 bg-slate-900 text-indigo-500 focus:ring-indigo-500" />
                                    مرا به خاطر بسپار
                                </label>
                                @if(Route::has('password.request'))
                                    <a href="{{ route('password.request') }}" class="text-indigo-300 hover:text-white transition">فراموشی رمز عبور؟</a>
                                @endif
                            </div>
                            <button type="submit" class="w-full rounded-3xl bg-gradient-to-r from-indigo-500 to-purple-500 px-6 py-3 text-sm font-semibold uppercase tracking-[0.15em] text-white shadow-xl shadow-indigo-500/20 transition hover:opacity-95">
                                ورود به حساب
                            </button>
                        </form>
                    </div>

                    <div id="register-panel" class="auth-panel hidden">
                        <form method="POST" action="{{ route('register') }}" class="space-y-5">
                            @csrf
                            <div>
                                <label class="block text-sm font-semibold text-slate-200 mb-2" for="name">نام کامل</label>
                                <input id="name" name="name" type="text" value="{{ old('name') }}" required class="w-full rounded-3xl border border-slate-700 bg-slate-950/90 px-4 py-3 text-slate-100 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30" />
                                @error('name')<p class="mt-2 text-xs text-rose-300">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-200 mb-2" for="email">ایمیل</label>
                                <input id="register-email" name="email" type="email" value="{{ old('email') }}" required class="w-full rounded-3xl border border-slate-700 bg-slate-950/90 px-4 py-3 text-slate-100 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30" />
                                @error('email')<p class="mt-2 text-xs text-rose-300">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-200 mb-2" for="password">رمز عبور</label>
                                <input id="register-password" name="password" type="password" required class="w-full rounded-3xl border border-slate-700 bg-slate-950/90 px-4 py-3 text-slate-100 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30" />
                                @error('password')<p class="mt-2 text-xs text-rose-300">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-200 mb-2" for="password_confirmation">تایید رمز عبور</label>
                                <input id="password_confirmation" name="password_confirmation" type="password" required class="w-full rounded-3xl border border-slate-700 bg-slate-950/90 px-4 py-3 text-slate-100 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/30" />
                                @error('password_confirmation')<p class="mt-2 text-xs text-rose-300">{{ $message }}</p>@enderror
                            </div>
                            <button type="submit" class="w-full rounded-3xl bg-gradient-to-r from-emerald-500 to-cyan-500 px-6 py-3 text-sm font-semibold uppercase tracking-[0.15em] text-slate-950 shadow-xl shadow-cyan-500/20 transition hover:opacity-95">
                                ساخت حساب جدید
                            </button>
                        </form>
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

        function setActive(tab) {
            if (tab === 'register') {
                loginPanel.classList.add('hidden');
                registerPanel.classList.remove('hidden');
                loginTab.classList.remove('bg-white/10', 'text-white');
                registerTab.classList.add('bg-white', 'text-slate-950');
                loginTab.classList.add('text-slate-300');
                registerTab.classList.remove('text-slate-300');
            } else {
                registerPanel.classList.add('hidden');
                loginPanel.classList.remove('hidden');
                registerTab.classList.remove('bg-white', 'text-slate-950');
                loginTab.classList.add('bg-white', 'text-slate-950');
                registerTab.classList.add('text-slate-300');
                loginTab.classList.remove('text-slate-300');
            }
        }

        loginTab.addEventListener('click', () => setActive('login'));
        registerTab.addEventListener('click', () => setActive('register'));

        setActive(activeTab);
    </script>
</body>
</html>
