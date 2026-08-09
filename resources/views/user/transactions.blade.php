@extends('layouts.dashboard')

@section('title', __('transactions.page_title') . ' - CryptoPay')
@section('page-title', __('transactions.page_title'))
@section('page-subtitle', __('transactions.page_subtitle'))

@section('content')
@php $isRtl = app()->getLocale() === 'fa'; @endphp

@push('styles')
<style>
    .transactions-shell {
        padding: 0.2rem 0 0.25rem;
    }
    .transactions-filter-card,
    .transactions-history-card,
    .transactions-item-card {
        border: 1px solid #e2e8f0;
        border-radius: 24px;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
    }
    .transactions-item-card:hover {
        box-shadow: 0 14px 34px rgba(15, 23, 42, 0.09);
    }
    .transactions-pill {
        border-radius: 999px;
        padding: 0.38rem 0.7rem;
        font-size: 0.74rem;
        font-weight: 600;
        letter-spacing: 0.03em;
    }
    .transactions-copy-btn {
        border-radius: 999px;
        border: 1px solid #dbe4f0;
        background: #f8fafc;
        color: #334155;
        transition: all 0.2s ease;
    }
    .transactions-copy-btn:hover {
        background: #eef2ff;
        color: #4338ca;
    }
    .transactions-amount {
        font-variant-numeric: tabular-nums;
    }
</style>
@endpush

<div class="transactions-shell max-w-7xl" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
    <div class="transactions-filter-card p-5 md:p-6">
        <div class="flex {{ $isRtl ? 'flex-row-reverse' : '' }} items-start justify-between gap-3">
            <div>
                <h3 class="text-lg font-semibold text-slate-900">{{ __('transactions.filter_title') }}</h3>
                <p class="mt-1 text-sm text-slate-500">{{ __('transactions.filter_subtitle') }}</p>
            </div>
            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
                <i class="fas fa-filter"></i>
            </div>
        </div>

        <form method="GET" class="mt-5 space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">{{ __('transactions.type_label') }}</label>
                    <select name="type" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                        <option value="">{{ __('transactions.all') }}</option>
                        <option value="transfer" @if(request('type') === 'transfer') selected @endif>{{ __('transactions.type_transfer') }}</option>
                        <option value="deposit" @if(request('type') === 'deposit') selected @endif>{{ __('transactions.type_deposit') }}</option>
                    </select>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">{{ __('transactions.currency_label') }}</label>
                    <select name="currency" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                        <option value="">{{ __('transactions.all') }}</option>
                        <option value="BTC" @if(request('currency') === 'BTC') selected @endif>Bitcoin (BTC)</option>
                        <option value="ETH" @if(request('currency') === 'ETH') selected @endif>Ethereum (ETH)</option>
                        <option value="USDT" @if(request('currency') === 'USDT') selected @endif>Tether (USDT)</option>
                    </select>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">{{ __('transactions.amount_label') }}</label>
                    <select name="amount_range" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                        <option value="">{{ __('transactions.all') }}</option>
                        <option value="0-0.1" @if(request('amount_range') === '0-0.1') selected @endif>{{ __('transactions.amount_range_under_0_1') }}</option>
                        <option value="0.1-1" @if(request('amount_range') === '0.1-1') selected @endif>{{ __('transactions.amount_range_0_1_to_1') }}</option>
                        <option value="1-10" @if(request('amount_range') === '1-10') selected @endif>{{ __('transactions.amount_range_1_to_10') }}</option>
                        <option value="10+" @if(request('amount_range') === '10+') selected @endif>{{ __('transactions.amount_range_over_10') }}</option>
                    </select>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">{{ __('transactions.search_label') }}</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('transactions.search_placeholder') }}" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                </div>
            </div>

            <div class="flex {{ $isRtl ? 'flex-row-reverse' : '' }} flex-wrap gap-3">
                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-indigo-700">
                    <i class="fas fa-search"></i>
                    {{ __('transactions.search_button') }}
                </button>
                <a href="{{ route('user.transactions') }}" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-slate-100 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-200">
                    <i class="fas fa-rotate-left"></i>
                    {{ __('transactions.clear_button') }}
                </a>
            </div>
        </form>
    </div>

    @if(request('type') || request('currency') || request('search') || request('amount_range'))
        <div class="mt-4 rounded-[20px] border border-blue-200 bg-blue-50/80 p-4">
            <div class="flex {{ $isRtl ? 'flex-row-reverse' : '' }} items-start justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold text-blue-800">{{ __('transactions.filtered_results') }}</p>
                    <p class="mt-1 text-sm text-blue-700">
                        @if(request('type'))
                            {{ __('transactions.filter_type') }}: <strong>{{ request('type') === 'transfer' ? __('transactions.type_transfer') : __('transactions.type_deposit') }}</strong>
                        @endif
                        @if(request('currency'))
                            {{ __('transactions.filter_currency') }}: <strong>{{ request('currency') }}</strong>
                        @endif
                        @if(request('search'))
                            {{ __('transactions.filter_search') }}: <strong>{{ request('search') }}</strong>
                        @endif
                    </p>
                </div>
                <button onclick="location.href='{{ route('user.transactions') }}'" class="rounded-full p-2 text-blue-700 transition hover:bg-blue-100">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    @endif

    <div class="transactions-history-card mt-5 overflow-hidden">
        <div class="border-b border-slate-200 bg-slate-50/70 p-5 md:p-6">
            <div class="flex {{ $isRtl ? 'flex-row-reverse' : '' }} flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">{{ __('transactions.history_title') }}</h3>
                    @if($transactions && $transactions->total() > 0)
                        <p class="mt-1 text-sm text-slate-500">{{ __('transactions.history_count', ['count' => $transactions->total()]) }}</p>
                    @endif
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600">
                    {{ __('transactions.transaction_history_badge') }}
                </div>
            </div>
        </div>

        <div class="divide-y divide-slate-200">
            @if($transactions && $transactions->count() > 0)
                @foreach($transactions as $transaction)
                    @php
                        $isIncoming = $transaction->type === 'deposit';
                        $directionIcon = $isIncoming ? 'fa-arrow-down' : 'fa-arrow-up';
                        $directionColor = $isIncoming ? 'text-emerald-600 bg-emerald-50' : 'text-amber-600 bg-amber-50';
                        $amountPrefix = $isIncoming ? '+' : '-';

                        $statusKey = match ($transaction->status ?? '') {
                            'pending' => 'common.pending',
                            'confirmed', 'completed' => 'common.completed',
                            'failed' => 'common.failed',
                            'cancelled' => 'common.cancelled',
                            default => 'transactions.unknown_status',
                        };
                        $statusLabel = __($statusKey);

                        $statusBadgeClass = match ($transaction->status ?? '') {
                            'pending' => 'bg-amber-100 text-amber-700 border-amber-200',
                            'confirmed','completed' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                            'failed' => 'bg-rose-100 text-rose-700 border-rose-200',
                            'cancelled' => 'bg-slate-200 text-slate-700 border-slate-300',
                            default => 'bg-slate-100 text-slate-700 border-slate-200',
                        };

                        $senderAddress = $transaction->sender_wallet_address ?? '';
                        $receiverAddress = $transaction->wallet?->wallet_address ?? '';
                        $hashValue = $transaction->reference ?: ($transaction->paymentRequest ? 'INV-' . $transaction->paymentRequest->invoice_number : 'TRX-' . $transaction->id);
                        $shortSender = $senderAddress ? substr($senderAddress, 0, 8) . '...' . substr($senderAddress, -6) : __('transactions.not_available');
                        $shortReceiver = $receiverAddress ? substr($receiverAddress, 0, 8) . '...' . substr($receiverAddress, -6) : __('transactions.not_available');
                        $shortHash = strlen($hashValue) > 18 ? substr($hashValue, 0, 8) . '...' . substr($hashValue, -6) : $hashValue;
                    @endphp

                    <div class="transactions-item-card m-4 md:m-5 p-4 md:p-5">
                        <div class="flex {{ $isRtl ? 'flex-row-reverse' : '' }} flex-wrap items-start justify-between gap-4">
                            <div class="flex {{ $isRtl ? 'flex-row-reverse' : '' }} min-w-0 items-start gap-3">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl {{ $directionColor }}">
                                    <i class="fas {{ $directionIcon }} text-lg"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="flex {{ $isRtl ? 'flex-row-reverse' : '' }} flex-wrap items-center gap-2">
                                        <p class="text-base font-semibold text-slate-900">{{ $transaction->display_title }}</p>
                                        <span class="transactions-pill border border-slate-200 bg-slate-100 text-slate-600">
                                            {{ $isIncoming ? __('transactions.type_deposit') : __('transactions.type_transfer') }}
                                        </span>
                                    </div>
                                    <p class="mt-1 text-sm text-slate-500">
                                        {{ $transaction->created_at->format('Y/m/d H:i') }} • {{ $transaction->wallet->currency ?? __('transactions.unknown_currency') }}
                                    </p>
                                </div>
                            </div>
                            <div class="text-{{ $isRtl ? 'left' : 'right' }}">
                                <p class="transactions-amount text-xl font-semibold {{ $isIncoming ? 'text-emerald-600' : 'text-amber-600' }}">
                                    {{ $amountPrefix }}{{ \App\Support\NumberHelper::formatCryptoAmount($transaction->amount) }}
                                </p>
                                <p class="mt-1 text-sm text-slate-500">{{ $transaction->wallet->currency ?? __('transactions.unknown_currency') }}</p>
                            </div>
                        </div>

                        <div class="mt-4 grid grid-cols-1 lg:grid-cols-2 gap-4">
                            <div class="space-y-3 rounded-[20px] border border-slate-200 bg-slate-50/80 p-4">
                                <div class="flex {{ $isRtl ? 'flex-row-reverse' : '' }} items-center justify-between gap-2">
                                    <p class="text-sm font-semibold text-slate-700">{{ __('transactions.description') }}</p>
                                    <span class="transactions-pill border border-slate-200 bg-white text-slate-600">{{ $isIncoming ? __('transactions.received') : __('transactions.sent') }}</span>
                                </div>
                                <p class="text-sm leading-6 text-slate-600">{{ $transaction->display_title }}</p>
                            </div>

                            <div class="space-y-3 rounded-[20px] border border-slate-200 bg-slate-50/80 p-4">
                                <div class="flex {{ $isRtl ? 'flex-row-reverse' : '' }} items-center justify-between gap-2">
                                    <p class="text-sm font-semibold text-slate-700">{{ __('transactions.status') }}</p>
                                    <span class="transactions-pill border {{ $statusBadgeClass }}">{{ $statusLabel }}</span>
                                </div>
                                <div class="flex {{ $isRtl ? 'flex-row-reverse' : '' }} flex-wrap items-center gap-3 text-sm text-slate-600">
                                    <span class="rounded-full border border-slate-200 bg-white px-3 py-1">{{ __('transactions.confirmations') }}: {{ $transaction->status === 'confirmed' ? '1' : '—' }}</span>
                                    <span class="rounded-full border border-slate-200 bg-white px-3 py-1">{{ __('transactions.date') }}: {{ $transaction->created_at->format('Y/m/d H:i') }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3">
                            <div class="rounded-[18px] border border-slate-200 bg-white p-3">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ __('transactions.sender_address') }}</p>
                                <div class="mt-2 flex items-center gap-2">
                                    <code class="truncate text-sm font-mono text-slate-700" dir="ltr" title="{{ $senderAddress ?: __('transactions.not_available') }}">{{ $shortSender }}</code>
                                    @if($senderAddress)
                                        <button type="button" class="transactions-copy-btn h-8 w-8 shrink-0" data-copy-value="{{ $senderAddress }}" title="{{ __('transactions.copy') }}" aria-label="{{ __('transactions.copy') }}">
                                            <i class="fas fa-copy text-sm"></i>
                                        </button>
                                    @endif
                                </div>
                            </div>

                            <div class="rounded-[18px] border border-slate-200 bg-white p-3">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ __('transactions.receiver_address') }}</p>
                                <div class="mt-2 flex items-center gap-2">
                                    <code class="truncate text-sm font-mono text-slate-700" dir="ltr" title="{{ $receiverAddress ?: __('transactions.not_available') }}">{{ $shortReceiver }}</code>
                                    @if($receiverAddress)
                                        <button type="button" class="transactions-copy-btn h-8 w-8 shrink-0" data-copy-value="{{ $receiverAddress }}" title="{{ __('transactions.copy') }}" aria-label="{{ __('transactions.copy') }}">
                                            <i class="fas fa-copy text-sm"></i>
                                        </button>
                                    @endif
                                </div>
                            </div>

                            <div class="rounded-[18px] border border-slate-200 bg-white p-3">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ __('transactions.transaction_hash') }}</p>
                                <div class="mt-2 flex items-center gap-2">
                                    <code class="truncate text-sm font-mono text-slate-700" dir="ltr" title="{{ $hashValue }}">{{ $shortHash }}</code>
                                    <button type="button" class="transactions-copy-btn h-8 w-8 shrink-0" data-copy-value="{{ $hashValue }}" title="{{ __('transactions.copy') }}" aria-label="{{ __('transactions.copy') }}">
                                        <i class="fas fa-copy text-sm"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="rounded-[18px] border border-slate-200 bg-white p-3">
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ __('transactions.amount') }}</p>
                                <p class="mt-2 text-base font-semibold text-slate-900">{{ \App\Support\NumberHelper::formatCryptoAmount($transaction->amount) }} {{ $transaction->wallet->currency ?? __('transactions.unknown_currency') }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="p-12 text-center">
                    <i class="fas fa-inbox text-5xl text-slate-300 mb-4"></i>
                    <p class="text-lg font-semibold text-slate-700">{{ __('transactions.no_transactions') }}</p>
                    <p class="mt-2 text-sm text-slate-500">
                        @if(request('type') || request('currency') || request('search') || request('amount_range'))
                            {{ __('transactions.no_transactions_filtered') }}
                        @else
                            {{ __('transactions.no_transactions_yet') }}
                        @endif
                    </p>
                </div>
            @endif
        </div>
    </div>

    @if($transactions && $transactions->hasPages())
        <div class="mt-6">
            {{ $transactions->links() }}
        </div>
    @endif
</div>

@push('scripts')
<script>
    document.querySelectorAll('[data-copy-value]').forEach(function (button) {
        button.addEventListener('click', function () {
            const value = this.getAttribute('data-copy-value');
            navigator.clipboard.writeText(value).then(function () {
                const icon = this.querySelector('i');
                if (icon) {
                    icon.className = 'fas fa-check text-sm';
                }
            }.bind(this)).catch(function () {
                const icon = this.querySelector('i');
                if (icon) {
                    icon.className = 'fas fa-times text-sm';
                }
            }.bind(this));
        });
    });
</script>
@endpush

@endsection
