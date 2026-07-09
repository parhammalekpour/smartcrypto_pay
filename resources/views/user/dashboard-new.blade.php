@extends('layouts.dashboard')

@section('title', 'خانه - CryptoPay')
@section('page-title', 'کیف پول دیجیتال شما')
@section('page-subtitle', 'سلام ' . auth()->user()->name . '، خوش آمدید')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    <!-- Quick Balance Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Balance -->
        <div class="bg-gradient-to-br from-indigo-600 to-indigo-800 rounded-lg shadow text-white p-6 hover:shadow-lg transition">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-white/20 p-3 rounded-lg">
                    <i class="fas fa-wallet text-lg"></i>
                </div>
                <span class="text-xs font-semibold bg-white/20 px-2 py-1 rounded">کل موجودی</span>
            </div>
            <p class="text-white/80 text-sm mb-1">موجودی کل</p>
            <p class="text-3xl font-bold" data-total-balance>${{ number_format($totalBalance ?? 0, 2) }}</p>
            <p class="text-xs text-white/70 mt-2">تمام کیف پول‌ها</p>
        </div>

        <!-- Received Transactions -->
        <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-green-100 p-3 rounded-lg">
                    <i class="fas fa-arrow-right text-green-600 text-lg"></i>
                </div>
                <span class="text-xs font-semibold text-green-600 bg-green-50 px-2 py-1 rounded">این ماه</span>
            </div>
            <p class="text-gray-500 text-sm mb-1">تراکنش‌های دریافت شده</p>
            <p class="text-2xl font-bold text-gray-800">{{ $receivedCount ?? 0 }}</p>
            <p class="text-xs text-gray-400 mt-2">تراکنش</p>
        </div>

        <!-- Sent Transactions -->
        <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-orange-100 p-3 rounded-lg">
                    <i class="fas fa-arrow-left text-orange-600 text-lg"></i>
                </div>
                <span class="text-xs font-semibold text-orange-600 bg-orange-50 px-2 py-1 rounded">ارسالی</span>
            </div>
            <p class="text-gray-500 text-sm mb-1">تراکنش‌های ارسالی</p>
            <p class="text-2xl font-bold text-gray-800">{{ $sentCount ?? 0 }}</p>
            <p class="text-xs text-gray-400 mt-2">تراکنش</p>
        </div>

        <!-- Active Wallets -->
        <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-purple-100 p-3 rounded-lg">
                    <i class="fas fa-coins text-purple-600 text-lg"></i>
                </div>
                <span class="text-xs font-semibold text-purple-600 bg-purple-50 px-2 py-1 rounded">فعال</span>
            </div>
            <p class="text-gray-500 text-sm mb-1">کیف پول‌های فعال</p>
            <p class="text-2xl font-bold text-gray-800">{{ $wallets->count() ?? 0 }}</p>
            <p class="text-xs text-gray-400 mt-2">کیف پول</p>
        </div>
    </div>

    <!-- Main Actions & Wallets Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Quick Actions -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-6 pb-4 border-b border-gray-200">عملیات سریع</h3>
            
            <div class="space-y-3">
                <a href="{{ route('user.send') }}" class="flex items-center justify-between p-4 bg-green-50 hover:bg-green-100 rounded-lg transition border border-green-200">
                    <div>
                        <p class="font-semibold text-gray-800">ارسال کریپتو</p>
                        <p class="text-xs text-gray-500">به کیف پول دیگری</p>
                    </div>
                    <i class="fas fa-paper-plane text-green-600 text-lg"></i>
                </a>

                <a href="{{ route('user.receive') }}" class="flex items-center justify-between p-4 bg-indigo-50 hover:bg-indigo-100 rounded-lg transition border border-indigo-200">
                    <div>
                        <p class="font-semibold text-gray-800">دریافت کریپتو</p>
                        <p class="text-xs text-gray-500">نشانی کیف پول</p>
                    </div>
                    <i class="fas fa-inbox text-indigo-600 text-lg"></i>
                </a>

                <a href="{{ route('user.wallets') }}" class="flex items-center justify-between p-4 bg-purple-50 hover:bg-purple-100 rounded-lg transition border border-purple-200">
                    <div>
                        <p class="font-semibold text-gray-800">مدیریت کیف پول‌ها</p>
                        <p class="text-xs text-gray-500">اضافه یا حذف</p>
                    </div>
                    <i class="fas fa-wallet text-purple-600 text-lg"></i>
                </a>

                <a href="{{ route('user.transactions') }}" class="flex items-center justify-between p-4 bg-blue-50 hover:bg-blue-100 rounded-lg transition border border-blue-200">
                    <div>
                        <p class="font-semibold text-gray-800">تاریخچه تراکنش</p>
                        <p class="text-xs text-gray-500">همه تراکنش‌ها</p>
                    </div>
                    <i class="fas fa-history text-blue-600 text-lg"></i>
                </a>
            </div>
        </div>

        <!-- My Wallets Summary -->
        <div class="lg:col-span-2 bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-200">
                <div class="flex items-center gap-3">
                    <div class="bg-purple-100 p-2 rounded-lg">
                        <i class="fas fa-coins text-purple-600"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800">کیف پول‌های من</h3>
                </div>
                <a href="{{ route('user.wallets') }}" class="text-indigo-600 text-sm font-semibold hover:underline">مشاهده همه →</a>
            </div>

            @if($wallets && $wallets->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($wallets as $wallet)
                        <div class="p-4 border border-gray-200 rounded-lg hover:shadow-md transition">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center">
                                        @if($wallet->currency === 'BTC')
                                            <i class="fab fa-bitcoin text-orange-500 text-xl"></i>
                                        @elseif($wallet->currency === 'ETH')
                                            <i class="fab fa-ethereum text-gray-700 text-xl"></i>
                                        @else
                                            <i class="fas fa-coins text-indigo-600 text-xl"></i>
                                        @endif
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-800">{{ $wallet->currency }}</p>
                                        <p class="text-xs text-gray-500">کیف پول</p>
                                    </div>
                                </div>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                    ✓ فعال
                                </span>
                            </div>
                            <div class="p-3 bg-gray-50 rounded text-xs text-gray-600 mb-3 font-mono truncate">
                                {{ substr($wallet->wallet_address, 0, 32) }}...
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-bold text-gray-800">{{ number_format($wallet->balance, 8) }}</span>
                                <a href="{{ route('user.wallets') }}" class="text-indigo-600 text-xs font-semibold hover:underline">جزئیات →</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <i class="fas fa-wallet text-5xl text-gray-300 mb-4"></i>
                    <p class="text-gray-500 mb-4">هنوز کیف پولی ایجاد نشده است</p>
                    <a href="{{ route('user.wallets') }}" class="inline-block bg-indigo-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-indigo-700 transition">
                        ایجاد کیف پول اول
                    </a>
                </div>
            @endif
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-200">
            <div class="flex items-center gap-3">
                <div class="bg-blue-100 p-2 rounded-lg">
                    <i class="fas fa-history text-blue-600"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-800">آخرین تراکنش‌ها</h3>
            </div>
            <a href="{{ route('user.transactions') }}" class="text-indigo-600 text-sm font-semibold hover:underline">مشاهده همه →</a>
        </div>

        @if($transactions && $transactions->count() > 0)
            <div class="space-y-3">
                @foreach($transactions->take(6) as $transaction)
                    <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 @if($transaction->type === 'transfer') bg-orange-100 @else bg-green-100 @endif rounded-full flex items-center justify-center">
                                <i class="fas @if($transaction->type === 'transfer') fa-arrow-left text-orange-600 @else fa-arrow-right text-green-600 @endif text-lg"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-800">
                                    @if($transaction->type === 'transfer')
                                        ارسال به {{ $transaction->recipient->name ?? 'نامشخص' }}
                                    @elseif($transaction->type === 'deposit')
                                        @if($transaction->description === 'Demo Deposit')
                                            دریافت - {{ $transaction->description }}
                                        @else
                                            دریافت از {{ $transaction->sender->name ?? 'نامشخص' }}
                                        @endif
                                    @else
                                        {{ $transaction->description ?? 'تراکنش' }}
                                    @endif
                                </p>
                                <p class="text-xs text-gray-500 mt-1">{{ $transaction->wallet->currency ?? 'UNKNOWN' }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold @if($transaction->type === 'transfer') text-orange-600 @else text-green-600 @endif">
                                @if($transaction->type === 'transfer')
                                    -{{ number_format($transaction->amount, 8) }}
                                @else
                                    +{{ number_format($transaction->amount, 8) }}
                                @endif
                            </p>
                            <p class="text-xs text-gray-500 mt-1">{{ $transaction->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12">
                <i class="fas fa-inbox text-5xl text-gray-300 mb-4"></i>
                <p class="text-gray-500">هنوز تراکنشی انجام نشده است</p>
                <p class="text-sm text-gray-400 mt-2">با استفاده از گزینه‌های بالا اولین تراکنش خود را انجام دهید</p>
            </div>
        @endif
    </div>

    <!-- Pending Payments Section -->
    @if(isset($pendingPayments) && $pendingPayments->count() > 0)
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="bg-yellow-50 px-6 py-4 border-b border-yellow-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="bg-yellow-200 p-2 rounded-lg">
                        <i class="fas fa-hourglass-half text-yellow-700 text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">پرداخت‌های در انتظار</h3>
                        <p class="text-xs text-gray-600">{{ $pendingPayments->count() }} درخواست منتظر پرداخت</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-6 space-y-3">
            @foreach($pendingPayments->take(4) as $payment)
                <div class="p-4 border border-yellow-200 rounded-lg bg-yellow-50 hover:shadow-md transition">
                    <div class="flex items-center justify-between flex-wrap gap-4">
                        <div class="flex-1">
                            <p class="font-semibold text-gray-800">{{ $payment->invoice_number ?? 'پرداخت بدون فاکتور' }}</p>
                            <p class="text-sm text-gray-600 mt-1">{{ number_format($payment->amount, 8) }} {{ $payment->currency }}</p>
                            <p class="text-xs text-gray-500 mt-1">وضعیت: در انتظار پرداخت</p>
                        </div>
                        <a href="{{ url('/pay/' . $payment->token) }}" class="bg-indigo-600 text-white px-6 py-2 rounded-lg font-semibold text-sm hover:bg-indigo-700 transition whitespace-nowrap">
                            پرداخت کنید
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif
</div>

<script>
// Real-time balance update using latest crypto prices
let walletBalances = @json($wallets->map(fn($w) => ['currency' => $w->currency, 'balance' => $w->balance])->values());
let cryptoPrices = { btc: 0, eth: 0, usd: 1 };

// Fetch prices immediately on page load
async function fetchPricesAndUpdate() {
    try {
        const response = await fetch('{{ route("api.crypto-prices") }}');
        const data = await response.json();
        
        console.log('Fetched prices from API:', data);
        
        cryptoPrices.btc = parseFloat(data.btc) || 0;
        cryptoPrices.eth = parseFloat(data.eth) || 0;
        
        updateTotalBalance();
    } catch (error) {
        console.error('Failed to fetch crypto prices:', error);
    }
}

function updateTotalBalance() {
    let total = 0;
    
    walletBalances.forEach(wallet => {
        let price = 1; // Default for USD
        
        if (wallet.currency === 'BTC') {
            price = cryptoPrices.btc;
        } else if (wallet.currency === 'ETH') {
            price = cryptoPrices.eth;
        } else if (wallet.currency === 'USDT' || wallet.currency === 'USD') {
            price = 1;
        }
        
        console.log(`Wallet ${wallet.currency}: ${wallet.balance} * ${price} = ${wallet.balance * price}`);
        total += wallet.balance * price;
    });
    
    console.log('Total balance USD:', total);
    
    // Update the total balance element
    const balanceElement = document.querySelector('[data-total-balance]');
    if (balanceElement) {
        balanceElement.textContent = '$' + total.toLocaleString('en-US', { 
            minimumFractionDigits: 2, 
            maximumFractionDigits: 2 
        });
    }
}

// Fetch prices immediately
fetchPricesAndUpdate();

// Fetch prices every 10 seconds
setInterval(fetchPricesAndUpdate, 10000);
</script>

@endsection