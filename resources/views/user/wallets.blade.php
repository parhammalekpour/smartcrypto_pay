@extends('layouts.dashboard')

@section('title', 'کیف پول‌های من - CryptoPay')
@section('page-title', 'کیف پول‌های من')
@section('page-subtitle', 'مدیریت کیف پول‌های دیجیتال شما')

@section('content')

<!-- Add Wallet Button -->
<div class="mb-6 flex justify-end">
    <button onclick="openAddWalletModal()" class="bg-indigo-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-indigo-700 transition flex items-center gap-2">
        <i class="fas fa-plus"></i>اضافه کردن کیف پول جدید
    </button>
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
                    <p class="text-xs text-gray-400 mt-1 usd-price" data-currency="{{ $wallet->currency }}" data-balance="{{ $wallet->balance }}">≈ $0.00</p>
                </div>

                <!-- Address -->
                <div class="mb-4">
                    <p class="text-gray-500 text-xs mb-1">نشانی کیف پول</p>
                    <div class="p-2 bg-gray-50 rounded border border-gray-200">
                        <p class="text-xs text-gray-700 font-mono break-all">{{ $wallet->wallet_address }}</p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="grid grid-cols-3 gap-2">
                    <button class="p-2 bg-indigo-50 text-indigo-600 rounded-lg text-sm font-semibold hover:bg-indigo-100 transition flex items-center justify-center gap-1" onclick="copyToClipboard('{{ $wallet->wallet_address }}')">
                        <i class="fas fa-copy"></i>کپی
                    </button>
                    <a href="{{ route('user.send') }}" class="p-2 bg-green-50 text-green-600 rounded-lg text-sm font-semibold hover:bg-green-100 transition flex items-center justify-center gap-1">
                        <i class="fas fa-paper-plane"></i>ارسال
                    </a>
                    <form action="/wallet/demo-deposit/{{ $wallet->id }}" method="POST" style="display:contents;">
                        @csrf
                        <button type="submit" class="p-2 bg-yellow-50 text-yellow-600 rounded-lg text-sm font-semibold hover:bg-yellow-100 transition flex items-center justify-center gap-1">
                            <i class="fas fa-gift"></i>
                            <span class="hidden sm:inline">دمو</span>
                        </button>
                    </form>
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

    // Fetch prices from API
    async function fetchAndDisplayPrices() {
        try {
            const response = await fetch('{{ route("api.crypto-prices") }}');
            const data = await response.json();
            
            cryptoPrices.btc = parseFloat(data.btc) || 0;
            cryptoPrices.eth = parseFloat(data.eth) || 0;
            
            updateWalletPrices();
        } catch (error) {
            console.error('Failed to fetch crypto prices:', error);
        }
    }

    function updateWalletPrices() {
        const priceElements = document.querySelectorAll('.usd-price');
        
        priceElements.forEach(element => {
            const currency = element.getAttribute('data-currency');
            const balance = parseFloat(element.getAttribute('data-balance'));
            
            let price = 1;
            if (currency === 'BTC') {
                price = cryptoPrices.btc;
            } else if (currency === 'ETH') {
                price = cryptoPrices.eth;
            } else if (currency === 'USDT' || currency === 'USD') {
                price = 1;
            }
            
            const usdValue = balance * price;
            element.textContent = '≈ $' + usdValue.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        });
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

    // Fetch prices every 10 seconds
    setInterval(fetchAndDisplayPrices, 10000);
</script>
@endpush

@endsection
