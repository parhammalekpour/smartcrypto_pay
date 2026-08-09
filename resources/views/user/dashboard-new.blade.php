@extends('layouts.dashboard')

@section('title', __('dashboard.home_title') . ' - CryptoPay')
@section('page-title', __('dashboard.home_title'))
@section('page-subtitle', __('dashboard.home_subtitle', ['name' => auth()->user()->name]))

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
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold bg-white/20 px-2 py-1 rounded">{{ __('dashboard.total_balance') }}</span>
                    <button type="button" class="p-2 rounded-lg bg-white/10 hover:bg-white/20 transition" data-toggle-total-balance aria-label="{{ __('dashboard.toggle_balance_visibility') }}">
                        <i class="fas fa-eye text-sm"></i>
                    </button>
                </div>
            </div>
            <p class="text-white/80 text-sm mb-1">{{ __('dashboard.total_balance') }}</p>
            <p class="text-3xl font-bold" data-total-balance data-actual-balance="{{ $totalBalance ?? 0 }}">${{ number_format($totalBalance ?? 0, 2) }}</p>
            <p class="text-xs text-white/70 mt-2">{{ __('dashboard.all_wallets') }}</p>
        </div>


        <!-- Received Transactions -->
        <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-green-100 p-3 rounded-lg">
                    <i class="fas fa-arrow-right text-green-600 text-lg"></i>
                </div>
                <span class="text-xs font-semibold text-green-600 bg-green-50 px-2 py-1 rounded">{{ __('dashboard.this_month') }}</span>
            </div>
            <p class="text-gray-500 text-sm mb-1">{{ __('dashboard.received_transactions') }}</p>
            <p class="text-2xl font-bold text-gray-800">{{ $receivedCount ?? 0 }}</p>
            <p class="text-xs text-gray-400 mt-2">{{ __('dashboard.transaction_label') }}</p>
        </div>

        <!-- Sent Transactions -->
        <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-orange-100 p-3 rounded-lg">
                    <i class="fas fa-arrow-left text-orange-600 text-lg"></i>
                </div>
                <span class="text-xs font-semibold text-orange-600 bg-orange-50 px-2 py-1 rounded">{{ __('dashboard.sent_badge') }}</span>
            </div>
            <p class="text-gray-500 text-sm mb-1">{{ __('dashboard.sent_transactions') }}</p>
            <p class="text-2xl font-bold text-gray-800">{{ $sentCount ?? 0 }}</p>
            <p class="text-xs text-gray-400 mt-2">{{ __('dashboard.transaction_label') }}</p>
        </div>

        <!-- Active Wallets -->
        <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-purple-100 p-3 rounded-lg">
                    <i class="fas fa-coins text-purple-600 text-lg"></i>
                </div>
                <span class="text-xs font-semibold text-purple-600 bg-purple-50 px-2 py-1 rounded">{{ __('dashboard.active_badge') }}</span>
            </div>
            <p class="text-gray-500 text-sm mb-1">{{ __('dashboard.active_wallets') }}</p>
            <p class="text-2xl font-bold text-gray-800">{{ $wallets->count() ?? 0 }}</p>
            <p class="text-xs text-gray-400 mt-2">{{ __('dashboard.wallets_label') }}</p>
        </div>
    </div>

    <!-- Main Actions & Wallets Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Quick Actions -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-6 pb-4 border-b border-gray-200">{{ __('dashboard.quick_actions') }}</h3>
            
            <div class="space-y-3">
                <a href="{{ route('user.send') }}" class="flex items-center justify-between p-4 bg-green-50 hover:bg-green-100 rounded-lg transition border border-green-200">
                    <div>
                        <p class="font-semibold text-gray-800">{{ __('dashboard.send_crypto') }}</p>
                        <p class="text-xs text-gray-500">{{ __('dashboard.send_crypto_desc') }}</p>
                    </div>
                    <i class="fas fa-paper-plane text-green-600 text-lg"></i>
                </a>

                <a href="{{ route('user.receive') }}" class="flex items-center justify-between p-4 bg-indigo-50 hover:bg-indigo-100 rounded-lg transition border border-indigo-200">
                    <div>
                        <p class="font-semibold text-gray-800">{{ __('dashboard.receive_crypto') }}</p>
                        <p class="text-xs text-gray-500">{{ __('dashboard.receive_crypto_desc') }}</p>
                    </div>
                    <i class="fas fa-inbox text-indigo-600 text-lg"></i>
                </a>

                <a href="{{ route('user.wallets') }}" class="flex items-center justify-between p-4 bg-purple-50 hover:bg-purple-100 rounded-lg transition border border-purple-200">
                    <div>
                        <p class="font-semibold text-gray-800">{{ __('dashboard.manage_wallets') }}</p>
                        <p class="text-xs text-gray-500">{{ __('dashboard.manage_wallets_desc') }}</p>
                    </div>
                    <i class="fas fa-wallet text-purple-600 text-lg"></i>
                </a>

                <a href="{{ route('user.transactions') }}" class="flex items-center justify-between p-4 bg-blue-50 hover:bg-blue-100 rounded-lg transition border border-blue-200">
                    <div>
                        <p class="font-semibold text-gray-800">{{ __('dashboard.transaction_history') }}</p>
                        <p class="text-xs text-gray-500">{{ __('common.view_all') }}</p>
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
                    <h3 class="text-lg font-semibold text-gray-800">{{ __('dashboard.my_wallets') }}</h3>
                </div>
                <a href="{{ route('user.wallets') }}" class="text-indigo-600 text-sm font-semibold hover:underline">{{ __('common.view_all') }} →</a>
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
                                        <p class="text-xs text-gray-500">{{ __('dashboard.wallet') }}</p>
                                    </div>
                                </div>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                    ✓ {{ __('dashboard.active_badge') }}
                                </span>
                            </div>
                            <div class="p-3 bg-gray-50 rounded text-xs text-gray-600 mb-3 font-mono truncate">
                                {{ substr($wallet->wallet_address, 0, 32) }}...
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-bold text-gray-800">{{ \App\Support\NumberHelper::formatCryptoAmount($wallet->balance) }}</span>
                                <a href="{{ route('user.wallets') }}" class="text-indigo-600 text-xs font-semibold hover:underline">{{ __('dashboard.details') }} →</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <i class="fas fa-wallet text-5xl text-gray-300 mb-4"></i>
                    <p class="text-gray-500 mb-4">{{ __('common.no_wallets') }}</p>
                    <a href="{{ route('user.wallets') }}" class="inline-block bg-indigo-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-indigo-700 transition">
                        {{ __('common.create_first_wallet') }}
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
                <h3 class="text-lg font-semibold text-gray-800">{{ __('dashboard.recent_transactions') }}</h3>
            </div>
            <a href="{{ route('user.transactions') }}" class="text-indigo-600 text-sm font-semibold hover:underline">{{ __('common.view_all') }} →</a>
        </div>

        @if($transactions && $transactions->count() > 0)
            <div class="space-y-3">
                @foreach($transactions->take(6) as $transaction)
                    <div class="p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 @if($transaction->type === 'transfer') bg-orange-100 @else bg-green-100 @endif rounded-full flex items-center justify-center">
                                    <i class="fas @if($transaction->type === 'transfer') fa-arrow-left text-orange-600 @else fa-arrow-right text-green-600 @endif text-lg"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-800">
                                        {{ $transaction->display_title }}
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">{{ $transaction->created_at->format('Y/m/d H:i') }} • {{ $transaction->wallet->currency ?? 'UNKNOWN' }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold @if($transaction->type === 'transfer') text-orange-600 @else text-green-600 @endif">
                                    @if($transaction->type === 'transfer')
                                        -{{ \App\Support\NumberHelper::formatCryptoAmount($transaction->amount) }}
                                    @else
                                        +{{ \App\Support\NumberHelper::formatCryptoAmount($transaction->amount) }}
                                    @endif
                                </p>
                                @php
                                    $transactionStatus = $transaction->status;
                                    if ($transaction->type === 'deposit' && $transaction->deposit?->status === 'confirmed') {
                                        $transactionStatus = 'completed';
                                    }
                                @endphp
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold mt-2 @if($transactionStatus === 'completed') bg-green-100 text-green-800 @elseif($transactionStatus === 'pending') bg-yellow-100 text-yellow-800 @else bg-red-100 text-red-800 @endif">
                                    @if($transactionStatus === 'completed') {{ __('common.completed') }}
                                    @elseif($transactionStatus === 'pending') {{ __('common.pending') }}
                                    @else {{ __('common.failed') }}
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12">
                <i class="fas fa-inbox text-5xl text-gray-300 mb-4"></i>
                <p class="text-gray-500">{{ __('common.no_transactions') }}</p>
                <p class="text-sm text-gray-400 mt-2">{{ __('dashboard.no_transactions_description') }}</p>
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
                        <h3 class="text-lg font-semibold text-gray-800">{{ __('dashboard.pending_payments_section') }}</h3>
                        <p class="text-xs text-gray-600">{{ $pendingPayments->count() }} {{ __('dashboard.pending_payments_desc') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-6 space-y-3">
            @foreach($pendingPayments->take(4) as $payment)
                <div class="p-4 border border-yellow-200 rounded-lg bg-yellow-50 hover:shadow-md transition">
                    <div class="flex items-center justify-between flex-wrap gap-4">
                        <div class="flex-1">
                            <p class="font-semibold text-gray-800">{{ $payment->invoice_number ?? __('dashboard.pending_payment_without_invoice') }}</p>
                            <p class="text-sm text-gray-600 mt-1">{{ \App\Support\NumberHelper::formatCryptoAmount($payment->amount) }} {{ $payment->currency }}</p>
                            <p class="text-xs text-gray-500 mt-1">{{ __('dashboard.pending_payment_status') }}</p>
                        </div>
                        <a href="{{ url('/pay/' . $payment->token) }}" class="bg-indigo-600 text-white px-6 py-2 rounded-lg font-semibold text-sm hover:bg-indigo-700 transition whitespace-nowrap">
                            {{ __('dashboard.pay_now') }}
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
let previousPrices = { btc: null, eth: null };

function formatPrice(price) {
    return '$' + price.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function formatChange(current, previous) {
    if (previous === null || previous === 0) {
        return '---';
    }
    const diff = current - previous;
    const pct = previous === 0 ? 0 : (diff / previous) * 100;
    const arrow = diff > 0 ? '▲' : diff < 0 ? '▼' : '–';
    return `${arrow} ${Math.abs(diff).toFixed(2)} (${Math.abs(pct).toFixed(2)}%)`;
}

function updateCryptoPriceCards() {
    const btcEl = document.querySelector('[data-btc-price]');
    const ethEl = document.querySelector('[data-eth-price]');
    const btcChangeEl = document.querySelector('[data-btc-change]');
    const ethChangeEl = document.querySelector('[data-eth-change]');
    const updatedEl = document.querySelector('[data-price-updated]');

    if (btcEl) {
        btcEl.textContent = formatPrice(cryptoPrices.btc);
    }
    if (ethEl) {
        ethEl.textContent = formatPrice(cryptoPrices.eth);
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
        updatedEl.textContent = '{{ __('dashboard.last_updated') }} ' + new Date().toLocaleTimeString('en-US');
    }
}

async function fetchPricesAndUpdate() {
    try {
        const response = await fetch('{{ route("public.api.crypto-prices") }}' + '?t=' + Date.now(), { cache: 'no-store' });
        const data = await response.json();

        previousPrices.btc = cryptoPrices.btc || previousPrices.btc;
        previousPrices.eth = cryptoPrices.eth || previousPrices.eth;

        cryptoPrices.btc = parseFloat(data.btc) || 0;
        cryptoPrices.eth = parseFloat(data.eth) || 0;

        updateTotalBalance();
        updateCryptoPriceCards();
    } catch (error) {
        console.error('Failed to fetch crypto prices:', error);
    }
}

const balanceToggleKey = 'dashboard-total-balance-hidden';
let isTotalBalanceHidden = false;

function getStoredBalanceHidden() {
    try {
        return localStorage.getItem(balanceToggleKey) === 'true';
    } catch (error) {
        return false;
    }
}

function setStoredBalanceHidden(value) {
    try {
        localStorage.setItem(balanceToggleKey, value ? 'true' : 'false');
    } catch (error) {
        // ignore storage errors
    }
}

isTotalBalanceHidden = getStoredBalanceHidden();

function updateBalanceToggleButton() {
    const toggleButton = document.querySelector('[data-toggle-total-balance] i');
    if (!toggleButton) {
        return;
    }

    toggleButton.classList.toggle('fa-eye', !isTotalBalanceHidden);
    toggleButton.classList.toggle('fa-eye-slash', isTotalBalanceHidden);
}

function setTotalBalanceText(amount) {
    const balanceElement = document.querySelector('[data-total-balance]');
    if (!balanceElement) {
        return;
    }

    balanceElement.dataset.actualBalance = amount;
    if (isTotalBalanceHidden) {
        balanceElement.textContent = '••••••';
    } else {
        balanceElement.textContent = formatPrice(amount);
    }
}

function toggleTotalBalanceVisibility() {
    isTotalBalanceHidden = !isTotalBalanceHidden;
    setStoredBalanceHidden(isTotalBalanceHidden);
    updateBalanceToggleButton();

    const balanceElement = document.querySelector('[data-total-balance]');
    if (!balanceElement) {
        return;
    }

    const currentAmount = parseFloat(balanceElement.dataset.actualBalance) || 0;
    setTotalBalanceText(currentAmount);
}

const totalBalanceToggleButton = document.querySelector('[data-toggle-total-balance]');
if (totalBalanceToggleButton) {
    totalBalanceToggleButton.addEventListener('click', toggleTotalBalanceVisibility);
    updateBalanceToggleButton();
}

function updateTotalBalance() {
    let total = 0;

    walletBalances.forEach(wallet => {
        let price = 1;
        if (wallet.currency === 'BTC') {
            price = cryptoPrices.btc;
        } else if (wallet.currency === 'ETH') {
            price = cryptoPrices.eth;
        } else if (wallet.currency === 'USDT' || wallet.currency === 'USD') {
            price = 1;
        }

        total += wallet.balance * price;
    });

    setTotalBalanceText(total);
}

// Apply visibility state to the initial rendered amount
const initialBalanceElement = document.querySelector('[data-total-balance]');
if (initialBalanceElement) {
    const initialActualBalance = parseFloat(initialBalanceElement.dataset.actualBalance) || 0;
    setTotalBalanceText(initialActualBalance);
}

// Fetch prices immediately
fetchPricesAndUpdate();

// Fetch prices every 5 seconds
setInterval(fetchPricesAndUpdate, 5000);
</script>

@endsection