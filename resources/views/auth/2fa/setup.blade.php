<!DOCTYPE html>
<html lang="fa" dir="rtl" class="h-full bg-[#020817]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CryptoPay | فعال‌سازی 2FA</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            background:
                radial-gradient(circle at 15% 20%, rgba(59,130,246,0.15), transparent 16%),
                radial-gradient(circle at 85% 80%, rgba(168,85,247,0.18), transparent 18%),
                linear-gradient(135deg, #020817 0%, #0b1120 35%, #111827 100%);
        }

        .glass {
            background: rgba(15, 23, 42, 0.74);
            border: 1px solid rgba(148, 163, 184, 0.14);
            box-shadow: 0 30px 90px rgba(2, 6, 23, 0.7);
            backdrop-filter: blur(18px);
        }

        .badge {
            background: linear-gradient(135deg, rgba(14,165,233,0.14), rgba(59,130,246,0.06));
            border: 1px solid rgba(125,211,252,0.22);
        }

        .qr-box {
            width: 172px;
            height: 172px;
            background: rgba(255,255,255,0.96);
            border: 1px solid rgba(148,163,184,0.25);
            box-shadow: 0 18px 40px rgba(14, 165, 233, 0.18);
        }

        .secret-box {
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(125, 211, 252, 0.2);
            box-shadow: inset 0 0 0 1px rgba(14,165,233,0.04);
        }

        .primary-btn {
            background: linear-gradient(135deg, #38bdf8 0%, #3b82f6 50%, #8b5cf6 100%);
            box-shadow: 0 16px 34px rgba(59,130,246,0.26);
        }
    </style>
</head>
<body class="min-h-screen text-slate-100 antialiased">
    <div class="min-h-screen flex items-center justify-center px-4 py-10">
        <div class="w-full max-w-4xl">
            <div class="glass overflow-hidden rounded-[32px] p-6 sm:p-8 lg:p-10">
                <div class="mb-8 flex items-center justify-between gap-4">
                    <div>
                        <div class="badge inline-flex items-center rounded-full px-3 py-1 text-[10px] font-medium uppercase tracking-[0.28em] text-sky-200">
                            CryptoPay
                        </div>
                        <h1 class="mt-5 text-3xl font-black text-white">فعال‌سازی 2FA</h1>
                    </div>
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl border border-violet-400/30 bg-violet-500/10 text-2xl shadow-lg shadow-violet-500/10">
                        🔐
                    </div>
                </div>

                <div class="rounded-[28px] border border-slate-700/80 bg-slate-900/60 p-5 sm:p-6">
                    <div class="grid gap-6 lg:grid-cols-[200px_1fr] lg:items-center">
                        <div class="flex justify-center">
                            <div class="qr-box flex items-center justify-center rounded-[20px] p-3">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=170x170&data={{ urlencode($otpUrl) }}" alt="QR Code" class="h-full w-full rounded-[12px] object-cover">
                            </div>
                        </div>

                        <div>
                            <div class="mb-5">
                                <label class="text-[10px] font-medium uppercase tracking-[0.28em] text-slate-400">کد مخفی</label>
                                <div class="secret-box mt-3 rounded-[16px] p-3 font-mono text-sm text-sky-200 break-all">
                                    {{ $secret }}
                                </div>
                            </div>

                            @php
                                $isMerchantContext = auth()->check() && (auth()->user()->isMerchant() || request()->is('merchant*'));
                                $enableRoute = $isMerchantContext ? 'merchant.2fa.enable' : '2fa.enable';
                            @endphp

                            <form method="POST" action="{{ route($enableRoute) }}" class="space-y-5">
                                @csrf
                                <input type="hidden" name="secret" value="{{ $secret }}">
                                <div>
                                    <label for="code" class="block text-sm font-semibold text-slate-200">کد یکبار مصرف</label>
                                    <input id="code" name="code" required type="text" class="mt-2 w-full rounded-[1.05rem] border border-slate-700 bg-slate-950/80 px-4 py-3.5 text-sm text-white outline-none transition focus:border-sky-500 focus:ring-4 focus:ring-sky-500/10" placeholder="۶ رقمی از اپلیکیشن auth">
                                </div>
                                <button type="submit" class="primary-btn w-full rounded-[1.05rem] px-5 py-3.5 text-sm font-bold text-white transition hover:brightness-110">
                                    فعال‌سازی 2FA
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="mt-6 rounded-[18px] border border-slate-700 bg-slate-950/40 p-4 text-sm leading-7 text-slate-300">
                        <div class="mb-2 flex items-center gap-2 text-sky-200">
                            <span class="h-2 w-2 rounded-full bg-sky-400"></span>
                            <span class="font-medium">نکات امنیتی</span>
                        </div>
                        <p>• کد پشتیبان را در محل امن نگه دارید.</p>
                        <p>• اگر دستگاه خود را عوض کردید، 2FA را دوباره تنظیم کنید.</p>
                        <p>• کدها هر ۳۰ ثانیه به‌روزرسانی می‌شوند.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
