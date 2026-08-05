@extends('layouts.dashboard')

@section('title', 'داشبورد - CryptoPay')
@section('page-title', 'داشبورد اصلی')
@section('page-subtitle', 'خوش آمدید بازگشت، ' . auth()->user()->name)

@section('content')
<!-- Input Styling Override -->
<style>
    input[type="text"],
    input[type="number"],
    input[type="email"],
    input[type="password"],
    select,
    textarea {
        color: #1f2937 !important;
    }
    input[type="text"]::placeholder,
    input[type="number"]::placeholder,
    input[type="email"]::placeholder,
    input[type="password"]::placeholder,
    textarea::placeholder {
        color: #9ca3af !important;
    }
</style>

<!-- Quick Stats -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <!-- Total Revenue -->
    <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition">
        <div class="flex items-center justify-between mb-4">
            <div class="bg-indigo-100 p-3 rounded-lg">
                <i class="fas fa-dollar-sign text-indigo-600 text-lg"></i>
            </div>
            <span class="text-xs font-semibold text-green-600 bg-green-50 px-2 py-1 rounded">📈 +12%</span>
        </div>
        <p class="text-gray-500 text-sm mb-1">کل درآمد</p>
        <p class="text-2xl font-bold text-gray-800">${{ number_format($totalRevenue ?? 0, 2) }}</p>
        <p class="text-xs text-gray-400 mt-2">تمام کیف پول‌ها</p>
    </div>

    <!-- Pending Payments -->
    <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition">
        <div class="flex items-center justify-between mb-4">
            <div class="bg-yellow-100 p-3 rounded-lg">
                <i class="fas fa-hourglass-end text-yellow-600 text-lg"></i>
            </div>
            <span class="text-xs font-semibold text-yellow-600 bg-yellow-50 px-2 py-1 rounded">⏳ منتظر</span>
        </div>
        <p class="text-gray-500 text-sm mb-1">پرداخت‌های در انتظار</p>
        <p class="text-2xl font-bold text-gray-800">{{ $pendingPayments ?? 0 }}</p>
        <p class="text-xs text-gray-400 mt-2">نیاز به پرداخت</p>
    </div>

    <!-- Completed Payments -->
    <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition">
        <div class="flex items-center justify-between mb-4">
            <div class="bg-green-100 p-3 rounded-lg">
                <i class="fas fa-check-circle text-green-600 text-lg"></i>
            </div>
            <span class="text-xs font-semibold text-green-600 bg-green-50 px-2 py-1 rounded">✓ تکمیل</span>
        </div>
        <p class="text-gray-500 text-sm mb-1">پرداخت‌های تکمیل شده</p>
        <p class="text-2xl font-bold text-gray-800">{{ $completedPayments ?? 0 }}</p>
        <p class="text-xs text-gray-400 mt-2">موفقیت‌آمیز</p>
    </div>

    <!-- Active Wallets -->
    <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition">
        <div class="flex items-center justify-between mb-4">
            <div class="bg-purple-100 p-3 rounded-lg">
                <i class="fas fa-wallet text-purple-600 text-lg"></i>
            </div>
            <span class="text-xs font-semibold text-purple-600 bg-purple-50 px-2 py-1 rounded">🔐 فعال</span>
        </div>
        <p class="text-gray-500 text-sm mb-1">کیف پول‌های فعال</p>
        <p class="text-2xl font-bold text-gray-800">{{ $wallets->count() ?? 0 }}</p>
        <p class="text-xs text-gray-400 mt-2">کیف پول</p>
    </div>
</div>

<!-- Analytics Section -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Revenue Chart -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-200">
            <div class="flex items-center gap-3">
                <div class="bg-blue-100 p-2 rounded-lg">
                    <i class="fas fa-chart-line text-blue-600"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-800">روند درآمد</h3>
            </div>
            <span class="text-xs font-semibold text-green-600 bg-green-50 px-2 py-1 rounded">این ماه</span>
        </div>
        <canvas id="revenueChart" height="300"></canvas>
    </div>

    <!-- Transaction Statistics -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-200">
            <div class="flex items-center gap-3">
                <div class="bg-green-100 p-2 rounded-lg">
                    <i class="fas fa-chart-bar text-green-600"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-800">آمار تراکنش‌ها</h3>
            </div>
            <span class="text-xs font-semibold text-blue-600 bg-blue-50 px-2 py-1 rounded">شماری</span>
        </div>
        
        <div class="space-y-4">
            <div class="flex items-center justify-between p-3 bg-indigo-50 rounded-lg">
                <div>
                    <p class="text-sm font-semibold text-gray-700">کل تراکنش‌ها</p>
                    <p class="text-xs text-gray-500 mt-1">تمام موارد</p>
                </div>
                <p class="text-2xl font-bold text-indigo-600">{{ $totalTransactions ?? 0 }}</p>
            </div>
            
            <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                <div>
                    <p class="text-sm font-semibold text-gray-700">تراکنش‌های موفق</p>
                    <p class="text-xs text-gray-500 mt-1">تسویه شده</p>
                </div>
                <p class="text-2xl font-bold text-green-600">{{ $successfulTransactions ?? 0 }}</p>
            </div>
            
            <div class="flex items-center justify-between p-3 bg-yellow-50 rounded-lg">
                <div>
                    <p class="text-sm font-semibold text-gray-700">تراکنش‌های ناموفق</p>
                    <p class="text-xs text-gray-500 mt-1">ناموفق یا منتظر</p>
                </div>
                <p class="text-2xl font-bold text-yellow-600">{{ $failedTransactions ?? 0 }}</p>
            </div>
        </div>
    </div>
</div>

<!-- Recent Payments -->
<div class="bg-white rounded-lg shadow p-6">
    <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-200">
        <div class="flex items-center gap-3">
            <div class="bg-indigo-100 p-2 rounded-lg">
                <i class="fas fa-list text-indigo-600"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-800">آخرین درخواست‌های پرداخت</h3>
        </div>
        <a href="{{ route('merchant.payments') }}" class="text-indigo-600 text-sm font-semibold hover:underline">مشاهده همه →</a>
    </div>

    @if($payments->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-right font-semibold text-gray-700">شماره فاکتور</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-700">گیرنده</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-700">مبلغ</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-700">وضعیت</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-700">تاریخ</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-700">عملیات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($payments->take(5) as $payment)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <span class="font-semibold text-gray-800">{{ $payment->invoice_number }}</span>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $payment->recipient->name ?? 'نامشخص' }}</td>
                            <td class="px-4 py-3 text-gray-800 font-semibold">{{ $payment->currency }} {{ number_format($payment->amount, 8) }}</td>
                            <td class="px-4 py-3">
                                @if($payment->status === 'pending')
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">
                                        <i class="fas fa-hourglass-end"></i>در انتظار
                                    </span>
                                @elseif($payment->status === 'paid')
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                        <i class="fas fa-check-circle"></i>پرداخت شده
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                                        <i class="fas fa-times-circle"></i>لغو شده
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $payment->created_at->diffForHumans() }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ url('/pay/' . $payment->token) }}" target="_blank" class="text-indigo-600 hover:underline font-semibold text-xs">
                                    مشاهده →
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="text-center py-12">
            <i class="fas fa-inbox text-4xl text-gray-300 mb-4"></i>
            <p class="text-gray-500 mb-2">هنوز درخواست پرداختی ایجاد نشده است</p>
            <a href="{{ route('merchant.payments') }}" class="text-indigo-600 font-semibold text-sm hover:underline">شروع کنید →</a>
        </div>
    @endif
</div>

<!-- Footer Stats -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
    <!-- About Us -->
    <div class="bg-gradient-to-br from-blue-50 to-cyan-50 rounded-lg shadow p-6 border border-blue-100">
        <h4 class="font-semibold text-gray-800 mb-3">درباره ما</h4>
        <p class="text-sm text-gray-600 mb-4 leading-relaxed">
            CryptoPay یک پلتفرم امن و سریع برای تراکنش‌های رمزنگاری شده است. ما به تسهیل پرداخت‌های دیجیتالی برای کسب‌وکارهای جهانی متعهد هستیم.
        </p>
        <div class="flex gap-2">
            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">🔒 ایمن</span>
            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">⚡ سریع</span>
            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-700">🌍 جهانی</span>
        </div>
    </div>

    <!-- Social Media -->
    <div class="bg-gradient-to-br from-pink-50 to-rose-50 rounded-lg shadow p-6 border border-pink-100">
        <h4 class="font-semibold text-gray-800 mb-4">شبکه‌های اجتماعی</h4>
        <div class="space-y-2">
            <a href="https://t.me/cryptopay" target="_blank" class="flex items-center gap-3 p-2 hover:bg-pink-100 rounded-lg transition">
                <i class="fab fa-telegram text-blue-500 text-lg"></i>
                <span class="text-sm font-semibold text-gray-700">تلگرام</span>
                <i class="fas fa-arrow-left text-gray-400 text-xs mr-auto"></i>
            </a>
            <a href="mailto:support@cryptopay.com" target="_blank" class="flex items-center gap-3 p-2 hover:bg-pink-100 rounded-lg transition">
                <i class="fas fa-envelope text-red-500 text-lg"></i>
                <span class="text-sm font-semibold text-gray-700">ایمیل</span>
                <i class="fas fa-arrow-left text-gray-400 text-xs mr-auto"></i>
            </a>
            <a href="https://instagram.com/cryptopay" target="_blank" class="flex items-center gap-3 p-2 hover:bg-pink-100 rounded-lg transition">
                <i class="fab fa-instagram text-pink-500 text-lg"></i>
                <span class="text-sm font-semibold text-gray-700">اینستاگرام</span>
                <i class="fas fa-arrow-left text-gray-400 text-xs mr-auto"></i>
            </a>
        </div>
    </div>

    <!-- Support -->
    <div class="bg-gradient-to-br from-indigo-50 to-purple-50 rounded-lg shadow p-6 border border-indigo-100">
        <h4 class="font-semibold text-gray-800 mb-2">نیاز به کمک دارید؟</h4>
        <p class="text-sm text-gray-600 mb-4">تیم پشتیبانی ما ۲۴/۷ آماده کمک است</p>
        <a href="{{ route('tickets.create') }}" class="w-full inline-block text-center bg-indigo-600 text-white py-2 rounded-lg font-semibold text-sm hover:bg-indigo-700 transition">
            <i class="fas fa-headset ml-2"></i>تماس با پشتیبانی
        </a>
    </div>
</div>

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // Revenue Chart
    const revenueCtx = document.getElementById('revenueChart');
    if (revenueCtx) {
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: {!! json_encode($dailyLabels) !!},
                datasets: [{
                    label: 'درآمد ($)',
                    data: {!! json_encode($dailyRevenue) !!},
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#4f46e5',
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            font: { size: 12, family: "'IRANSans', sans-serif" },
                            color: '#6b7280'
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value;
                            },
                            color: '#9ca3af',
                            font: { family: "'IRANSans', sans-serif" }
                        },
                        grid: { color: '#e5e7eb' }
                    },
                    x: {
                        ticks: {
                            color: '#9ca3af',
                            font: { family: "'IRANSans', sans-serif" }
                        },
                        grid: { display: false }
                    }
                }
            }
        });
    }
</script>

@endsection
