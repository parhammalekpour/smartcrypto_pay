@extends('layouts.dashboard')

@section('title', __('wallets.page_title') . ' - CryptoPay')
@section('page-title', __('wallets.page_title'))
@section('page-subtitle', __('wallets.page_subtitle'))

@section('content')

<!-- Live Prices + Add Wallet -->
<div class="mb-6 grid grid-cols-1 lg:grid-cols-[1.8fr_minmax(320px,360px)] gap-4 items-stretch">
    <div class="rounded-[28px] bg-slate-950 border border-slate-800 p-5 shadow-xl h-full">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <p class="text-sm text-slate-400">{{ __('wallets.live_prices_subtitle') }}</p>
                <h2 class="text-2xl font-semibold text-white">{{ __('wallets.live_prices_title') }}</h2>
            </div>
            <span class="inline-flex items-center gap-2 rounded-full bg-indigo-500/15 text-indigo-200 px-3 py-1 text-xs uppercase tracking-[0.14em]">
                <i class="fas fa-chart-line text-xs"></i>
                Binance
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

        <p class="mt-4 text-xs text-slate-500" data-live-price-updated>{{ __('wallets.last_updated') }} -</p>
    </div>

    <div class="flex flex-col justify-between rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm lg:max-w-[360px] h-full">
        <div>
            <div class="flex items-center justify-between gap-4 mb-4">
                <div>
                    <p class="text-sm text-slate-500">{{ __('wallets.quick_actions') }}</p>
                    <h3 class="text-lg font-semibold text-slate-900">{{ __('wallets.add_wallet') }}</h3>
                </div>
                <i class="fas fa-wallet text-indigo-600 text-xl"></i>
            </div>
            <p class="text-sm text-slate-600 leading-6">{{ __('wallets.add_wallet_description') }}</p>
        </div>
        <button onclick="openAddWalletModal()" class="mt-6 w-full bg-indigo-600 text-white px-5 py-3 rounded-2xl font-semibold hover:bg-indigo-700 transition">
            <i class="fas fa-plus mr-2"></i>
            {{ __('wallets.add_wallet') }}
        </button>
    </div>
</div>

<!-- Wallets Grid -->
@if($wallets && $wallets->count() > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
        @foreach($wallets as $wallet)
            <div class="bg-white rounded-lg shadow hover:shadow-lg transition p-6 border-t-4 data-wallet-card
                @if($wallet->currency === 'BTC') border-t-orange-500
                @elseif($wallet->currency === 'ETH') border-t-gray-500
               @else border-t-teal-500 @endif" data-wallet-id="{{ $wallet->id }}">
                
                <!-- Header -->
                <div class="flex items-center justify-between mb-4 pb-4 border-b border-gray-200">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 
                            @if($wallet->currency === 'BTC') bg-orange-100
                            @elseif($wallet->currency === 'ETH') bg-gray-100
                            @else bg-teal-100 @endif rounded-lg flex items-center justify-center">
                            @if($wallet->currency === 'BTC')
                                <i class="fab fa-bitcoin text-orange-600 text-xl"></i>
                            @elseif($wallet->currency === 'ETH')
                                <i class="fab fa-ethereum text-gray-600 text-lg"></i>
                            @else
                                <i class="fas fa-coins text-teal-600 text-lg"></i>
                            @endif
                        </div>
                        <div>
                            <p class="font-bold text-gray-800">{{ $wallet->name ?: $wallet->currency }}</p>
                            <p class="text-xs text-gray-500">{{ $wallet->currency === 'BTC' ? 'Bitcoin' : ($wallet->currency === 'ETH' ? 'Ethereum' : 'Tether') }}</p>
                        </div>
                    </div>
                    <div class="relative wallet-menu-wrapper">
                        <button type="button" class="merchant-menu-toggle inline-flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 text-gray-600 transition hover:bg-gray-200" data-wallet-name="{{ $wallet->name ?? '' }}" data-wallet-route="{{ route('merchant.wallets.rename', $wallet) }}" aria-label="{{ __('wallets.rename_wallet_title') }}" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-ellipsis-v text-gray-600"></i>
                        </button>
                        <div class="merchant-menu absolute right-0 mt-2 w-40 bg-white border border-gray-200 rounded-lg shadow-lg hidden z-10">
                            <button class="merchant-wallet-edit-trigger block w-full text-right px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" data-wallet-name="{{ $wallet->name ?? '' }}" data-wallet-route="{{ route('merchant.wallets.rename', $wallet) }}">
                                <i class="fas fa-edit ml-2"></i>{{ __('common.edit') }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Balance -->
                <div class="mb-4">
                    <p class="text-gray-500 text-xs mb-1">{{ __('wallets.balance') }}</p>
                    <p class="text-3xl font-bold text-gray-800 wallet-balance-value" data-wallet-id="{{ $wallet->id }}">{{ \App\Support\NumberHelper::formatCryptoAmount($wallet->display_balance ?? $wallet->balance) }}</p>
                    <p class="text-xs text-gray-400 mt-2">
                        {{ __('wallets.estimated_value') }}: <span class="font-semibold usd-price" data-currency="{{ $wallet->currency }}" data-balance="{{ $wallet->display_balance ?? $wallet->balance }}">≈ $0.00</span>
                    </p>
                </div>

                <!-- Address -->
                <div class="mb-4">
                    <p class="text-gray-500 text-xs mb-1">{{ __('wallets.address') }}</p>
                    <div class="p-2 bg-gray-50 rounded border border-gray-200">
                        <p class="text-xs text-gray-700 font-mono break-all" id="address-{{ $wallet->id }}">{{ $wallet->wallet_address }}</p>
                    </div>
                </div>

                <!-- Status -->
                <div class="mb-4">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-500">{{ __('wallets.status') }}</span>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                            <i class="fas fa-check-circle ml-1"></i>{{ __('common.active') }}
                        </span>
                    </div>
                </div>

                <!-- Actions -->
                <div class="grid grid-cols-4 gap-2">
                    <button class="p-2 bg-indigo-50 text-indigo-600 rounded-lg text-xs font-semibold hover:bg-indigo-100 transition flex items-center justify-center gap-1 whitespace-nowrap" onclick="copyAddress('{{ $wallet->wallet_address }}')">
                        <i class="fas fa-copy"></i>{{ __('wallets.copy') }}
                    </button>
                    <button class="p-2 bg-gray-50 text-gray-600 rounded-lg text-xs font-semibold hover:bg-gray-100 transition flex items-center justify-center gap-1 whitespace-nowrap" onclick="viewOnExplorer('{{ $wallet->wallet_address }}', '{{ $wallet->currency }}')">
                        <i class="fas fa-external-link"></i>{{ __('wallets.view') }}
                    </button>
                    <a href="{{ route('merchant.send', ['sender_wallet_id' => $wallet->id]) }}" class="p-2 bg-green-50 text-green-600 rounded-lg text-xs font-semibold hover:bg-green-100 transition flex items-center justify-center gap-1 whitespace-nowrap">
                        <i class="fas fa-paper-plane"></i>{{ __('wallets.send') }}
                    </a>

                    @if(floatval($wallet->balance) > 0)
                        <a href="{{ route('tickets.create', ['subject' => __('wallets.delete_ticket_subject', ['currency' => $wallet->currency]), 'message' => __('wallets.delete_ticket_message', ['address' => $wallet->wallet_address, 'balance' => $wallet->balance, 'currency' => $wallet->currency])]) }}" onclick="alert('{{ __('wallets.delete_wallet_balance_alert') }}');" class="p-2 bg-red-50 text-red-600 rounded-lg text-xs font-semibold hover:bg-red-100 transition flex items-center justify-center gap-1 whitespace-nowrap" title="{{ __('wallets.delete_wallet_ticket_title') }}">
                            <i class="fas fa-trash"></i>{{ __('common.delete') }}
                        </a>
                    @else
                        <form method="POST" action="{{ route('merchant.wallets.destroy', $wallet) }}" style="display:contents;" onsubmit="return confirm('{{ __('wallets.delete_wallet_confirm') }}');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-2 bg-red-50 text-red-600 rounded-lg text-xs font-semibold hover:bg-red-100 transition flex items-center justify-center gap-1 whitespace-nowrap">
                                <i class="fas fa-trash"></i>{{ __('common.delete') }}
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
        <p class="text-gray-500 mb-4">{{ __('wallets.no_wallets') }}</p>
        <button onclick="openAddWalletModal()" class="bg-indigo-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-indigo-700 transition">
            {{ __('wallets.create_first_wallet') }}
        </button>
    </div>
@endif

<!-- Add Wallet Modal -->
<div id="addWalletModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md mx-4">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('wallets.create_new_wallet') }}</h3>
        
        <form method="POST" action="{{ route('merchant.wallets.store') }}" class="space-y-4">
            @csrf
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('wallets.select_currency') }}</label>
                <select name="currency" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <option value="">{{ __('wallets.select_option') }}</option>
                    <option value="BTC">Bitcoin (BTC)</option>
                    <option value="ETH">Ethereum (ETH)</option>
                    <option value="USDT">Tether (USDT)</option>
                </select>
                @error('currency')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex gap-2">
                <button type="submit" class="flex-1 bg-indigo-600 text-white py-2 rounded-lg font-semibold hover:bg-indigo-700 transition">
                    {{ __('common.create') }}
                </button>
                <button type="button" onclick="closeAddWalletModal()" class="flex-1 bg-gray-200 text-gray-800 py-2 rounded-lg font-semibold hover:bg-gray-300 transition">
                    {{ __('common.cancel') }}
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Merchant Rename Wallet Modal -->
<div id="merchantRenameWalletModal" class="hidden fixed inset-0 bg-black/50 px-4 py-6 flex items-center justify-center z-50">
    <div class="w-full max-w-md rounded-lg border border-gray-200 bg-white p-6 shadow-2xl">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">{{ __('wallets.rename_wallet_title') }}</h3>
                <p class="mt-1 text-sm text-gray-500">{{ __('wallets.rename_wallet_description') }}</p>
            </div>
            <button type="button" onclick="closeMerchantRenameWalletModal()" class="flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 text-gray-500 hover:bg-gray-200">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="merchantWalletRenameForm" method="POST" action="" class="mt-5 space-y-4">
            @csrf
            <div>
                <label for="merchantWalletRenameInput" class="mb-2 block text-sm font-semibold text-gray-700">{{ __('wallets.rename_wallet_label') }}</label>
                <input id="merchantWalletRenameInput" name="name" type="text" maxlength="80" class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-gray-700 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200" placeholder="{{ __('wallets.rename_wallet_placeholder') }}">
            </div>

            <div class="flex gap-2 pt-2">
                <button type="submit" class="flex-1 rounded-lg bg-indigo-600 py-3 text-sm font-semibold text-white hover:bg-indigo-700 transition">
                    {{ __('wallets.rename_wallet_save') }}
                </button>
                <button type="button" onclick="closeMerchantRenameWalletModal()" class="flex-1 rounded-lg bg-gray-100 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-200 transition">
                    {{ __('common.cancel') }}
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
            updatedEl.textContent = '{{ __('wallets.last_updated') }} ' + new Date().toLocaleTimeString('{{ app()->getLocale() === 'fa' ? 'fa-IR' : 'en-US' }}');
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

    function copyAddress(address) {
        navigator.clipboard.writeText(address).then(() => {
            const toast = document.createElement('div');
            toast.className = 'fixed bottom-4 left-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
            toast.textContent = '{{ __('wallets.address_copied') }}';
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }).catch(err => {
            console.error('Failed to copy:', err);
            alert('{{ __('wallets.copy_error') }}');
        });
    }

    function viewOnExplorer(address, currency) {
        let explorerUrl = '';
        switch(currency) {
            case 'BTC':
                explorerUrl = `https://blockchair.com/bitcoin/address/${address}`;
                break;
            case 'ETH':
                explorerUrl = `https://etherscan.io/address/${address}`;
                break;
            case 'USDT':
                explorerUrl = `https://etherscan.io/address/${address}`;
                break;
            default:
                alert('{{ __('wallets.explorer_unavailable') }}');
                return;
        }
        window.open(explorerUrl, '_blank');
    }

    fetchAndDisplayPrices();
    setInterval(fetchAndDisplayPrices, 5000);

    // Merchant wallet rename modal behavior
    document.querySelectorAll('.merchant-wallet-edit-trigger').forEach(button => {
        button.addEventListener('click', function (event) {
            event.stopPropagation();
            const modal = document.getElementById('merchantRenameWalletModal');
            const form = document.getElementById('merchantWalletRenameForm');
            const input = document.getElementById('merchantWalletRenameInput');

            if (modal && form && input) {
                form.action = this.dataset.walletRoute || '';
                input.value = this.dataset.walletName || '';
                input.focus();
                modal.classList.remove('hidden');
            }
        });
    });

    // Merchant + wallet menu toggle helpers (click to open for reliable interaction)
    function closeMerchantMenus() {
        document.querySelectorAll('.merchant-menu, .wallet-menu').forEach(menu => menu.classList.add('hidden'));
        document.querySelectorAll('.merchant-menu-toggle, .wallet-menu-toggle').forEach(btn => btn.setAttribute('aria-expanded', 'false'));
    }

    document.addEventListener('click', function (event) {
        if (!event.target.closest('.merchant-menu-wrapper') && !event.target.closest('.wallet-menu-wrapper')) {
            closeMerchantMenus();
        }
    });

    document.querySelectorAll('.merchant-menu-toggle').forEach(button => {
        button.addEventListener('click', function (event) {
            event.stopPropagation();
            const wrapper = this.closest('.merchant-menu-wrapper') || this.closest('.wallet-menu-wrapper');
            const menu = wrapper ? wrapper.querySelector('.merchant-menu, .wallet-menu') : null;
            const isHidden = menu ? menu.classList.contains('hidden') : true;

            closeMerchantMenus();

            if (menu && isHidden) {
                menu.classList.remove('hidden');
                this.setAttribute('aria-expanded', 'true');
            }
        });
    });

    function closeMerchantRenameWalletModal() {
        const modal = document.getElementById('merchantRenameWalletModal');
        if (modal) modal.classList.add('hidden');
    }

</script>
@endpush

@endsection
