@extends('layouts.dashboard')

@section('title', 'کیف پول‌های من - CryptoPay')
@section('page-title', 'کیف پول‌های من')
@section('page-subtitle', 'مدیریت کیف پول‌های دیجیتال شما')

@section('content')

<!-- Live Prices + Actions -->
<div class="mb-6 grid grid-cols-1 lg:grid-cols-[1.8fr_minmax(320px,360px)] gap-4 items-stretch">
    <div class="rounded-[28px] bg-slate-950 border border-slate-800 p-5 shadow-xl h-full">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <p class="text-sm text-slate-400">قیمت‌های لحظه‌ای با جزئیات</p>
                <h2 class="text-2xl font-semibold text-white">BTC / ETH</h2>
            </div>
            <span class="inline-flex items-center gap-2 rounded-full bg-indigo-500/15 text-indigo-200 px-3 py-1 text-xs uppercase tracking-[0.14em]">
                <i class="fas fa-chart-line text-xs"></i>
                TradingView
            </span>
        </div>

        <div class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="rounded-3xl bg-slate-900/95 border border-slate-800 p-4">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-xs text-slate-500 uppercase">Bitcoin</p>
                        <p class="mt-3 text-3xl font-semibold text-white" data-live-btc-price>$0.00</p>
                    </div>
                    <span class="inline-flex items-center rounded-full bg-white/10 px-3 py-1 text-[11px] uppercase tracking-[0.16em] text-slate-300">BTC</span>
                </div>
                <p class="mt-4 text-sm" data-live-btc-change>---</p>
            </div>
            <div class="rounded-3xl bg-slate-900/95 border border-slate-800 p-4">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-xs text-slate-500 uppercase">Ethereum</p>
                        <p class="mt-3 text-3xl font-semibold text-white" data-live-eth-price>$0.00</p>
                    </div>
                    <span class="inline-flex items-center rounded-full bg-white/10 px-3 py-1 text-[11px] uppercase tracking-[0.16em] text-slate-300">ETH</span>
                </div>
                <p class="mt-4 text-sm" data-live-eth-change>---</p>
            </div>
        </div>

        <p class="mt-4 text-xs text-slate-500" data-live-price-updated>آخرین بروزرسانی: -</p>
    </div>

    <div class="flex flex-col justify-between rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm lg:max-w-[360px] h-full">
        <div>
            <div class="flex items-center justify-between gap-4 mb-4">
                <div>
                    <p class="text-sm text-slate-500">کنترل کیف پول‌ها</p>
                    <h3 class="text-lg font-semibold text-slate-900">افزودن کیف پول</h3>
                </div>
                <i class="fas fa-wallet text-indigo-600 text-xl"></i>
            </div>
            <p class="text-sm text-slate-600 leading-6">کیف پول جدید اضافه کنید تا موجودی و ارزش دلاری آن در کارت‌ها نمایش داده شود.</p>
        </div>
        <button onclick="openAddWalletModal()" class="mt-6 w-full bg-indigo-600 text-white px-5 py-3 rounded-2xl font-semibold hover:bg-indigo-700 transition">
            <i class="fas fa-plus mr-2"></i>
            اضافه کردن کیف پول
        </button>
    </div>
</div>

<!-- Wallets Grid -->
@if($wallets && $wallets->count() > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($wallets as $wallet)
            <div class="bg-white rounded-lg shadow hover:shadow-lg transition p-6 border-t-4 
                @if($wallet->currency === 'BTC') border-t-orange-500
                @elseif($wallet->currency === 'ETH') border-t-gray-500
                @else border-t-teal-500 @endif">
                
                <!-- Header -->
                <div class="flex items-center justify-between mb-4 pb-4 border-b border-gray-200">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 
                            @if($wallet->currency === 'BTC') bg-orange-100
                            @elseif($wallet->currency === 'ETH') bg-gray-100
                            @else bg-teal-100 @endif rounded-lg flex items-center justify-center">
                            @if($wallet->currency === 'BTC')
                                <i class="fas fa-bitcoin text-orange-600 text-lg"></i>
                            @elseif($wallet->currency === 'ETH')
                                <i class="fab fa-ethereum text-gray-600 text-lg"></i>
                            @else
                                <i class="fas fa-coins text-teal-600 text-lg"></i>
                            @endif
                        </div>
                        <div>
                            <p class="font-bold text-gray-800">{{ $wallet->currency }}</p>
                            <p class="text-xs text-gray-500">{{ $wallet->currency === 'BTC' ? 'Bitcoin' : ($wallet->currency === 'ETH' ? 'Ethereum' : 'Tether') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Balance -->
                <div class="mb-4">
                    <p class="text-gray-500 text-xs mb-1">موجودی</p>
                    <p class="text-3xl font-bold text-gray-800">{{ number_format($wallet->balance, 8) }}</p>
                    <p class="text-xs text-gray-400 mt-2">
                        ارزش تقریبی: <span class="font-semibold usd-price" data-currency="{{ $wallet->currency }}" data-balance="{{ $wallet->balance }}">≈ $0.00</span>
                    </p>
                </div>

                <!-- Address -->
                <div class="mb-4">
                    <p class="text-gray-500 text-xs mb-1">نشانی کیف پول</p>
                    <div class="p-2 bg-gray-50 rounded border border-gray-200">
                        <p class="text-xs text-gray-700 font-mono break-all">{{ $wallet->wallet_address }}</p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="grid grid-cols-4 gap-2">
                    <button class="p-2 bg-indigo-50 text-indigo-600 rounded-lg text-xs font-semibold hover:bg-indigo-100 transition flex items-center justify-center gap-1 whitespace-nowrap" onclick="copyToClipboard('{{ $wallet->wallet_address }}')">
                        <i class="fas fa-copy"></i>کپی
                    </button>
                    <a href="{{ route('user.send') }}" class="p-2 bg-green-50 text-green-600 rounded-lg text-xs font-semibold hover:bg-green-100 transition flex items-center justify-center gap-1 whitespace-nowrap">
                        <i class="fas fa-paper-plane"></i>ارسال
                    </a>
                    <form action="/wallet/demo-deposit/{{ $wallet->id }}" method="POST" style="display:contents;">
                        @csrf
                        <button type="submit" class="p-2 bg-yellow-50 text-yellow-600 rounded-lg text-xs font-semibold hover:bg-yellow-100 transition flex items-center justify-center gap-1 whitespace-nowrap">
                            <i class="fas fa-gift"></i>
                            <span class="hidden sm:inline">دمو</span>
                        </button>
                    </form>

                    @if(floatval($wallet->balance) > 0)
                        <!-- Wallet has balance: instruct user to contact admin -->
                        <button type="button" onclick="alert('برای حذف این کیف پول لطفاً برای تیم پشتیبانی (ادمین) تیکت ثبت کنید.');" class="p-2 bg-red-50 text-red-600 rounded-lg text-xs font-semibold hover:bg-red-100 transition flex items-center justify-center gap-1 whitespace-nowrap" title="برای حذف این کیف پول لطفاً برای تیم پشتیبانی (ادمین) تیکت ثبت کنید.">
                            <i class="fas fa-trash"></i>حذف
                        </button>
                    @else
                        <form method="POST" action="{{ route('user.wallets.destroy', $wallet) }}" style="display:contents;" onsubmit="return confirm('آیا از حذف این کیف پول مطمئن هستید؟ این عملیات غیرقابل بازگشت است.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-2 bg-red-50 text-red-600 rounded-lg text-xs font-semibold hover:bg-red-100 transition flex items-center justify-center gap-1 whitespace-nowrap">
                                <i class="fas fa-trash"></i>حذف
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@else
    <div class="bg-white rounded-lg shadow p-12 text-center">
        <i class="fas fa-wallet text-5xl text-gray-300 mb-4"></i>
        <p class="text-gray-500 mb-4">هنوز کیف پولی ایجاد نشده است</p>
        <button onclick="openAddWalletModal()" class="bg-indigo-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-indigo-700 transition">
            ایجاد کیف پول اول
        </button>
    </div>
@endif

<!-- Add Wallet Modal -->
<div id="addWalletModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md mx-4">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">ایجاد کیف پول جدید</h3>
        
        <form method="POST" action="{{ route('user.wallets.store') }}" class="space-y-4">
            @csrf
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">انتخاب ارز</label>
                <select name="currency" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <option value="">انتخاب کنید</option>
                    <option value="BTC">Bitcoin (BTC)</option>
                    <option value="ETH">Ethereum (ETH)</option>
                    <option value="USDT">Tether (USDT)</option>
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="flex-1 bg-indigo-600 text-white py-2 rounded-lg font-semibold hover:bg-indigo-700 transition">
                    ایجاد
                </button>
                <button type="button" onclick="closeAddWalletModal()" class="flex-1 bg-gray-200 text-gray-800 py-2 rounded-lg font-semibold hover:bg-gray-300 transition">
                    انصراف
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    let cryptoPrices = { btc: 0, eth: 0, usd: 1 };
    let previousPrices = { btc: null, eth: null };

    function formatPrice(price) {
        return '$' + price.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function formatChange(current, previous) {
        if (previous === null) {
            return '---';
        }

        const diff = current - previous;
        const pct = previous === 0 ? 0 : (diff / previous) * 100;
        const arrow = diff > 0 ? '▲' : diff < 0 ? '▼' : '–';
        const sign = diff === 0 ? '–' : arrow;
        return `${sign} ${formatPrice(Math.abs(diff))} (${Math.abs(pct).toFixed(2)}%)`;
    }

    function updateLivePriceCards() {
        const btcPriceEl = document.querySelector('[data-live-btc-price]');
        const ethPriceEl = document.querySelector('[data-live-eth-price]');
        const btcChangeEl = document.querySelector('[data-live-btc-change]');
        const ethChangeEl = document.querySelector('[data-live-eth-change]');
        const updatedEl = document.querySelector('[data-live-price-updated]');

        if (btcPriceEl) {
            btcPriceEl.textContent = formatPrice(cryptoPrices.btc);
        }
        if (ethPriceEl) {
            ethPriceEl.textContent = formatPrice(cryptoPrices.eth);
        }
        if (btcChangeEl) {
            btcChangeEl.textContent = formatChange(cryptoPrices.btc, previousPrices.btc);
            btcChangeEl.classList.toggle('text-emerald-400', cryptoPrices.btc > (previousPrices.btc || 0));
            btcChangeEl.classList.toggle('text-rose-400', cryptoPrices.btc < (previousPrices.btc || 0));
        }
        if (ethChangeEl) {
            ethChangeEl.textContent = formatChange(cryptoPrices.eth, previousPrices.eth);
            ethChangeEl.classList.toggle('text-emerald-400', cryptoPrices.eth > (previousPrices.eth || 0));
            ethChangeEl.classList.toggle('text-rose-400', cryptoPrices.eth < (previousPrices.eth || 0));
        }
        if (updatedEl) {
            updatedEl.textContent = 'آخرین بروزرسانی: ' + new Date().toLocaleTimeString('fa-IR');
        }
    }

    function updateWalletPrices() {
        const priceElements = document.querySelectorAll('.usd-price');
        priceElements.forEach(element => {
            const currency = element.getAttribute('data-currency');
            const balance = parseFloat(element.getAttribute('data-balance')) || 0;
            let price = 1;

            if (currency === 'BTC') {
                price = cryptoPrices.btc;
            } else if (currency === 'ETH') {
                price = cryptoPrices.eth;
            } else if (currency === 'USDT' || currency === 'USD') {
                price = 1;
            }

            const usdValue = balance * price;
            element.textContent = '≈ ' + formatPrice(usdValue);
        });
    }

    async function fetchAndDisplayPrices() {
        try {
            const response = await fetch('{{ route("public.api.crypto-prices") }}' + '?t=' + Date.now(), { cache: 'no-store' });
            const data = await response.json();

            previousPrices.btc = cryptoPrices.btc || previousPrices.btc;
            previousPrices.eth = cryptoPrices.eth || previousPrices.eth;

            cryptoPrices.btc = parseFloat(data.btc) || 0;
            cryptoPrices.eth = parseFloat(data.eth) || 0;

            updateLivePriceCards();
            updateWalletPrices();
        } catch (error) {
            console.error('Failed to fetch crypto prices:', error);
        }
    }

    function openAddWalletModal() {
        document.getElementById('addWalletModal').classList.remove('hidden');
    }
    function closeAddWalletModal() {
        document.getElementById('addWalletModal').classList.add('hidden');
    }
    
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            alert('آدرس کپی شد!');
        }).catch(err => {
            console.error('Failed to copy:', err);
        });
    }

    // Fetch prices on page load
    fetchAndDisplayPrices();

    // Fetch prices every 5 seconds for a live-updating feel
    setInterval(fetchAndDisplayPrices, 5000);

</script>
@endpush

@endsection
