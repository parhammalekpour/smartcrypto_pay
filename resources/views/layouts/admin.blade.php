<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'پنل مدیریت') - CryptoPay</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="min-h-screen bg-slate-100 text-slate-800">
<div class="flex min-h-screen">
    <aside class="w-72 bg-slate-900 text-white p-6 flex flex-col gap-4">
        <div class="border-b border-slate-700 pb-4">
            <h1 class="text-2xl font-bold">CryptoPay Admin</h1>
            <p class="text-sm text-slate-400 mt-1">پنل مدیریت</p>
        </div>

        <nav class="space-y-2">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 rounded-lg px-4 py-3 {{ Route::currentRouteName() === 'admin.dashboard' ? 'bg-slate-800' : 'hover:bg-slate-800' }}">
                <i class="fas fa-home"></i>
                <span>داشبورد</span>
            </a>
            <a href="{{ route('admin.users') }}" class="flex items-center gap-3 rounded-lg px-4 py-3 {{ Route::currentRouteName() === 'admin.users' ? 'bg-slate-800' : 'hover:bg-slate-800' }}">
                <i class="fas fa-users"></i>
                <span>کاربران</span>
            </a>
            <a href="{{ route('admin.kyc') }}" class="flex items-center gap-3 rounded-lg px-4 py-3 {{ Route::currentRouteName() === 'admin.kyc' ? 'bg-slate-800' : 'hover:bg-slate-800' }}">
                <i class="fas fa-id-card"></i>
                <span>مدارک KYC</span>
            </a>
            <a href="{{ route('admin.transactions') }}" class="flex items-center gap-3 rounded-lg px-4 py-3 {{ Route::currentRouteName() === 'admin.transactions' ? 'bg-slate-800' : 'hover:bg-slate-800' }}">
                <i class="fas fa-exchange-alt"></i>
                <span>تراکنش‌ها</span>
            </a>
            <a href="{{ route('admin.tickets.index') }}" class="flex items-center gap-3 rounded-lg px-4 py-3 {{ Route::currentRouteName() === 'admin.tickets.index' ? 'bg-slate-800' : 'hover:bg-slate-800' }}">
                <i class="fas fa-headset"></i>
                <span>تیکت‌ها / پشتیبانی</span>
            </a>
        </nav>

        <div class="mt-auto rounded-xl border border-slate-700 bg-slate-800/70 p-4">
            <p class="text-sm font-semibold">{{ auth()->user()->name }}</p>
            <p class="text-xs text-slate-400 mt-1">{{ auth()->user()->email }}</p>
            <form method="POST" action="{{ route('logout') }}" class="mt-4">
                @csrf
                <button type="submit" class="w-full rounded-lg bg-red-600 px-3 py-2 text-sm font-semibold text-white hover:bg-red-500">
                    خروج
                </button>
            </form>
        </div>
    </aside>

    <main class="flex-1 p-6">
        @if (session('success'))
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-700">
                {{ session('success') }}
            </div>
        @endif
        @if (session('info'))
            <div class="mb-4 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-blue-700">
                {{ session('info') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-700">
                <ul class="list-disc pr-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>
</div>
</body>
</html>
