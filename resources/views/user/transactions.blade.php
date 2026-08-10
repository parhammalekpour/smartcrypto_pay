@extends('layouts.dashboard')

@section('title', __('transactions.page_title') . ' - CryptoPay')
@section('page-title', __('transactions.page_title'))
@section('page-subtitle', __('transactions.page_subtitle'))

@section('content')
@php $isRtl = app()->getLocale() === 'fa'; @endphp

<div class="transactions-shell max-w-7xl mx-auto px-4 py-6" x-data="{ refreshing: false }" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
    <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
        <div class="space-y-3">
            <h1 class="text-3xl font-semibold text-slate-900">{{ __('transactions.page_title') }}</h1>
            <p class="max-w-2xl text-sm text-slate-500">Track and manage your crypto transactions.</p>
        </div>

        <button type="button" @click="refreshing = true; window.location.reload();" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
            <span x-show="!refreshing">Refresh Transactions</span>
            <span x-show="refreshing" class="inline-flex items-center gap-2">
                <svg class="h-4 w-4 animate-spin text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-80" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                Refreshing...
            </span>
        </button>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-3xl border border-slate-200/60 bg-slate-950/90 p-5 shadow-sm shadow-slate-900/10">
            <p class="text-sm uppercase tracking-[0.2em] text-slate-400">Total Transactions</p>
            <p class="mt-4 text-3xl font-semibold text-white">{{ $stats['total'] ?? 0 }}</p>
        </div>
        <div class="rounded-3xl border border-slate-200/60 bg-emerald-950/90 p-5 shadow-sm shadow-emerald-900/10">
            <p class="text-sm uppercase tracking-[0.2em] text-emerald-300">Completed</p>
            <p class="mt-4 text-3xl font-semibold text-white">{{ $stats['completed'] ?? 0 }}</p>
        </div>
        <div class="rounded-3xl border border-slate-200/60 bg-amber-950/90 p-5 shadow-sm shadow-amber-900/10">
            <p class="text-sm uppercase tracking-[0.2em] text-amber-300">Pending</p>
            <p class="mt-4 text-3xl font-semibold text-white">{{ $stats['pending'] ?? 0 }}</p>
        </div>
        <div class="rounded-3xl border border-slate-200/60 bg-rose-950/90 p-5 shadow-sm shadow-rose-900/10">
            <p class="text-sm uppercase tracking-[0.2em] text-rose-300">Failed</p>
            <p class="mt-4 text-3xl font-semibold text-white">{{ $stats['failed'] ?? 0 }}</p>
        </div>
    </div>

    <div class="mt-8 rounded-[28px] border border-slate-200/60 bg-slate-950/90 p-6 shadow-xl shadow-slate-900/10">
        <div class="grid gap-4 lg:grid-cols-4">
            <div>
                <label class="block text-sm font-semibold text-slate-300">Type</label>
                <select name="type" onchange="this.form.submit()" form="transaction-filters" class="mt-2 w-full rounded-2xl border border-slate-700/70 bg-slate-900 px-4 py-3 text-sm text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                    <option value="" class="bg-slate-950">All</option>
                    <option value="transfer" @if(request('type') === 'transfer') selected @endif>Transfer</option>
                    <option value="deposit" @if(request('type') === 'deposit') selected @endif>Deposit</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-300">Asset</label>
                <select name="currency" onchange="this.form.submit()" form="transaction-filters" class="mt-2 w-full rounded-2xl border border-slate-700/70 bg-slate-900 px-4 py-3 text-sm text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                    <option value="" class="bg-slate-950">All</option>
                    <option value="BTC" @if(request('currency') === 'BTC') selected @endif>Bitcoin (BTC)</option>
                    <option value="ETH" @if(request('currency') === 'ETH') selected @endif>Ethereum (ETH)</option>
                    <option value="USDT" @if(request('currency') === 'USDT') selected @endif>Tether (USDT)</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-300">Amount</label>
                <select name="amount_range" onchange="this.form.submit()" form="transaction-filters" class="mt-2 w-full rounded-2xl border border-slate-700/70 bg-slate-900 px-4 py-3 text-sm text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30">
                    <option value="" class="bg-slate-950">All</option>
                    <option value="0-0.1" @if(request('amount_range') === '0-0.1') selected @endif>Under 0.1</option>
                    <option value="0.1-1" @if(request('amount_range') === '0.1-1') selected @endif>0.1 to 1</option>
                    <option value="1-10" @if(request('amount_range') === '1-10') selected @endif>1 to 10</option>
                    <option value="10+" @if(request('amount_range') === '10+') selected @endif>Over 10</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-300">Search</label>
                <input type="text" name="search" form="transaction-filters" value="{{ request('search') }}" placeholder="Recipient, wallet, or reference" class="mt-2 w-full rounded-2xl border border-slate-700/70 bg-slate-900 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30" />
            </div>
        </div>
        <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="text-sm text-slate-400">Use filters to refine the transaction timeline.</div>
            <div class="flex flex-wrap gap-3">
                <button type="submit" form="transaction-filters" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-indigo-500">Search</button>
                <a href="{{ route('user.transactions') }}" class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-700/80 bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">Clear</a>
            </div>
        </div>
    </div>

    <form id="transaction-filters" method="GET" action="{{ route('user.transactions') }}" class="hidden"></form>

    <div class="mt-8">
        @if(request('type') || request('currency') || request('search') || request('amount_range'))
            <div class="rounded-3xl border border-slate-700/40 bg-slate-900/80 p-4 text-sm text-slate-300">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <span class="font-semibold text-white">Filtered results</span>
                        @if(request('type'))<span class="ml-2">Type: {{ request('type') === 'transfer' ? 'Transfer' : 'Deposit' }}</span>@endif
                        @if(request('currency'))<span class="ml-2">Asset: {{ request('currency') }}</span>@endif
                        @if(request('search'))<span class="ml-2">Search: {{ request('search') }}</span>@endif
                    </div>
                    <a href="{{ route('user.transactions') }}" class="text-indigo-300 hover:text-indigo-200">Clear filters</a>
                </div>
            </div>
        @endif
    </div>

    <div class="mt-8 rounded-[32px] border border-slate-200/60 bg-slate-950/90 p-6 shadow-xl shadow-slate-900/10">
        @if($transactions && $transactions->count() > 0)
            <div class="hidden lg:block">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-700 text-left text-sm text-slate-300">
                        <thead class="border-b border-slate-700/80 text-slate-400">
                            <tr>
                                <th class="px-4 py-3">Transaction</th>
                                <th class="px-4 py-3">Type</th>
                                <th class="px-4 py-3">Asset</th>
                                <th class="px-4 py-3 text-right">Amount</th>
                                <th class="px-4 py-3">Wallet</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Date</th>
                                <th class="px-4 py-3">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-700 border-b border-slate-700/80">
                            @foreach($transactions as $transaction)
                                @php
                                    $transactionType = match ($transaction->type) {
                                        'deposit' => 'Deposit',
                                        'withdraw' => 'Withdraw',
                                        'transfer' => 'Transfer',
                                        'payment' => 'Payment',
                                        default => ucfirst($transaction->type ?? ''),
                                    };

                                    $currency = $transaction->wallet?->currency ?? $transaction->currency ?? 'ETH';
                                    $statusLabel = match ($transaction->status ?? '') {
                                        'processing' => 'Processing',
                                        'pending' => 'Pending',
                                        'confirmed' => 'Confirmed',
                                        'completed' => 'Completed',
                                        'failed' => 'Failed',
                                        'cancelled' => 'Cancelled',
                                        default => ucfirst($transaction->status ?? 'Unknown'),
                                    };
                                    $statusClass = match ($transaction->status ?? '') {
                                        'processing' => 'bg-amber-100 text-amber-700 border-amber-200',
                                        'pending' => 'bg-amber-100 text-amber-700 border-amber-200',
                                        'confirmed' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                        'completed' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                        'failed' => 'bg-rose-100 text-rose-700 border-rose-200',
                                        'cancelled' => 'bg-slate-200 text-slate-700 border-slate-300',
                                        default => 'bg-slate-100 text-slate-700 border-slate-200',
                                    };
                                    $walletLabel = $transaction->wallet?->wallet_address ?: $transaction->receiver_wallet_address ?: $transaction->sender_wallet_address;
                                    $walletShort = $walletLabel ? substr($walletLabel, 0, 8) . '...' . substr($walletLabel, -6) : 'Unknown';
                                    $txHash = $transaction->tx_hash;
                                    $hashLabel = $txHash ? (strlen($txHash) > 18 ? substr($txHash, 0, 10) . '...' . substr($txHash, -8) : $txHash) : 'Waiting for broadcast';
                                @endphp
                                <tr id="transaction-row-{{ $transaction->id }}" data-transaction-id="{{ $transaction->id }}" data-transaction-status="{{ $transaction->status }}" data-tx-hash="{{ $transaction->tx_hash }}">
                                    <td class="px-4 py-4 align-top">
                                        <div class="font-semibold text-white">#TX-{{ str_pad($transaction->id, 4, '0', STR_PAD_LEFT) }}</div>
                                        <div class="mt-1 text-xs text-slate-500">{{ $txHash ? 'Hash:' : 'Reference:' }} <span class="font-mono text-slate-300" data-hash-cell>{{ $hashLabel }}</span></div>
                                    </td>
                                    <td class="px-4 py-4 align-top">
                                        <span class="inline-flex items-center gap-2 rounded-full bg-slate-900 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-slate-300">
                                            @if($transaction->type === 'deposit')<i class="fas fa-arrow-down text-emerald-400"></i>@elseif($transaction->type === 'withdraw')<i class="fas fa-arrow-up text-rose-400"></i>@elseif($transaction->type === 'transfer')<i class="fas fa-exchange-alt text-amber-400"></i>@else<i class="fas fa-circle text-slate-400"></i>@endif
                                            {{ $transactionType }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 align-top">
                                        <span class="inline-flex items-center gap-2 rounded-full bg-slate-900 px-3 py-1 text-sm font-semibold text-white">
                                            {{ $currency }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 align-top text-right">
                                        <div class="font-semibold text-white">{{ $transaction->type === 'deposit' ? '+' : '-' }}{{ number_format((float)$transaction->amount, 8, '.', '') }}</div>
                                        <div class="text-xs text-slate-500">{{ $currency }}</div>
                                    </td>
                                    <td class="px-4 py-4 align-top">
                                        <div class="font-medium text-white">{{ $walletShort }}</div>
                                    </td>
                                    <td class="px-4 py-4 align-top">
                                        <span class="status-badge inline-flex rounded-full border px-3 py-2 text-xs font-semibold {{ $statusClass }}">{{ $statusLabel }}</span>
                                    </td>
                                    <td class="px-4 py-4 align-top text-slate-400">{{ $transaction->created_at->format('Y/m/d H:i') }}</td>
                                    <td class="px-4 py-4 align-top">
                                        <a href="{{ route('user.transaction.show', ['transaction' => $transaction->id]) }}" class="inline-flex items-center rounded-2xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">View</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="space-y-4 lg:hidden">
                @foreach($transactions as $transaction)
                    @php
                        $transactionType = match ($transaction->type) {
                            'deposit' => 'Deposit',
                            'withdraw' => 'Withdraw',
                            'transfer' => 'Transfer',
                            'payment' => 'Payment',
                            default => ucfirst($transaction->type ?? ''),
                        };
                        $currency = $transaction->wallet?->currency ?? $transaction->currency ?? 'ETH';
                        $statusLabel = match ($transaction->status ?? '') {
                            'processing' => 'Processing',
                            'pending' => 'Pending',
                            'confirmed' => 'Confirmed',
                            'completed' => 'Completed',
                            'failed' => 'Failed',
                            'cancelled' => 'Cancelled',
                            default => ucfirst($transaction->status ?? 'Unknown'),
                        };
                        $statusClass = match ($transaction->status ?? '') {
                            'processing' => 'bg-amber-100 text-amber-700 border-amber-200',
                            'pending' => 'bg-amber-100 text-amber-700 border-amber-200',
                            'confirmed' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                            'completed' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                            'failed' => 'bg-rose-100 text-rose-700 border-rose-200',
                            'cancelled' => 'bg-slate-200 text-slate-700 border-slate-300',
                            default => 'bg-slate-100 text-slate-700 border-slate-200',
                        };
                        $walletLabel = $transaction->wallet?->wallet_address ?: $transaction->receiver_wallet_address ?: $transaction->sender_wallet_address;
                        $walletShort = $walletLabel ? substr($walletLabel, 0, 8) . '...' . substr($walletLabel, -6) : 'Unknown';
                        $txHash = $transaction->tx_hash;
                        $hashLabel = $txHash ? (strlen($txHash) > 18 ? substr($txHash, 0, 10) . '...' . substr($txHash, -8) : $txHash) : 'Waiting for broadcast';
                    @endphp
                    <div id="transaction-row-{{ $transaction->id }}" class="rounded-[28px] border border-slate-700/70 bg-slate-900/80 p-5 shadow-sm shadow-slate-950/10" data-transaction-id="{{ $transaction->id }}" data-transaction-status="{{ $transaction->status }}" data-tx-hash="{{ $transaction->tx_hash }}">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-medium text-slate-400">#TX-{{ str_pad($transaction->id, 4, '0', STR_PAD_LEFT) }}</p>
                                <p class="mt-2 text-xl font-semibold text-white">{{ $transaction->type === 'deposit' ? '+' : '-' }}{{ number_format((float)$transaction->amount, 8, '.', '') }} {{ $currency }}</p>
                            </div>
                            <span class="status-badge inline-flex rounded-full border px-3 py-2 text-xs font-semibold {{ $statusClass }}">{{ $statusLabel }}</span>
                        </div>
                        <div class="mt-4 grid gap-3">
                            <div class="rounded-3xl bg-slate-950/80 px-4 py-3">
                                <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Type</p>
                                <p class="mt-2 text-sm font-semibold text-white">{{ $transactionType }}</p>
                            </div>
                            <div class="rounded-3xl bg-slate-950/80 px-4 py-3">
                                <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Wallet</p>
                                <p class="mt-2 text-sm font-mono text-slate-200">{{ $walletShort }}</p>
                            </div>
                            <div class="rounded-3xl bg-slate-950/80 px-4 py-3">
                                <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Date</p>
                                <p class="mt-2 text-sm text-slate-200">{{ $transaction->created_at->format('Y/m/d H:i') }}</p>
                            </div>
                            <div class="rounded-3xl bg-slate-950/80 px-4 py-3">
                                <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Hash</p>
                                <p class="mt-2 text-sm font-mono text-slate-200 break-words" data-hash-cell>{{ $hashLabel }}</p>
                            </div>
                        </div>
                        <div class="mt-5 flex flex-wrap items-center gap-3">
                            <a href="{{ route('user.transaction.show', ['transaction' => $transaction->id]) }}" class="inline-flex items-center justify-center rounded-2xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-indigo-500">View Transaction</a>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="rounded-[28px] border border-slate-700/70 bg-slate-900/80 p-12 text-center text-slate-300">
                <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-slate-800 text-white">
                    <i class="fas fa-inbox text-2xl"></i>
                </div>
                <h2 class="mt-6 text-2xl font-semibold text-white">No transactions yet</h2>
                <p class="mt-2 text-sm text-slate-400">Your crypto transactions will appear here.</p>
                <button type="button" @click="refreshing = true; window.location.reload();" class="mt-6 inline-flex items-center justify-center rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-indigo-500">Refresh</button>
            </div>
        @endif
    </div>

    @if($transactions && $transactions->hasPages())
        <div class="mt-8 flex justify-center">
            {{ $transactions->links() }}
        </div>
    @endif
</div>

@push('scripts')
<script>
(function(){
    const apiUrlTemplate = "{{ route('api.transaction.show', ['transaction' => 'TRANSACTION_ID']) }}";
    const network = "{{ strtolower((string)env('ETHEREUM_NETWORK', 'sepolia')) }}";
    const explorerBase = network === 'mainnet' ? 'https://etherscan.io/tx/' : (network === 'sepolia' ? 'https://sepolia.etherscan.io/tx/' : 'https://etherscan.io/tx/');

    const statusLabels = {
        'processing': 'Processing',
        'pending': 'Pending',
        'confirmed': 'Confirmed',
        'completed': 'Completed',
        'failed': 'Failed',
        'cancelled': 'Cancelled'
    };

    const badgeClasses = {
        'processing': 'bg-amber-100 text-amber-700 border-amber-200',
        'pending': 'bg-amber-100 text-amber-700 border-amber-200',
        'confirmed': 'bg-emerald-100 text-emerald-700 border-emerald-200',
        'completed': 'bg-emerald-100 text-emerald-700 border-emerald-200',
        'failed': 'bg-rose-100 text-rose-700 border-rose-200',
        'cancelled': 'bg-slate-200 text-slate-700 border-slate-300',
    };

    const pollers = {};

    function isFinal(status) {
        return ['confirmed', 'completed', 'failed', 'cancelled'].includes(status);
    }

    function shortHash(hash) {
        if (!hash) return '';
        return hash.length <= 18 ? hash : hash.slice(0, 10) + '...' + hash.slice(-8);
    }

    function updateRow(row, data) {
        const existingStatus = (row.dataset.transactionStatus || '').toLowerCase();
        const incomingStatus = (data.status || '').toLowerCase();
        const finalStatuses = ['confirmed','failed','completed','cancelled'];

        // If row already shows a final status, don't overwrite it with a non-final incoming status
        if (finalStatuses.includes(existingStatus) && !finalStatuses.includes(incomingStatus)) {
            // but still update tx hash cell if present
            const hashCell = row.querySelector('[data-hash-cell]');
            if (hashCell) {
                if (data.tx_hash) {
                    hashCell.innerHTML = '<a class="font-mono text-slate-300 hover:text-white" href="' + explorerBase + data.tx_hash + '" target="_blank" rel="noopener noreferrer">' + shortHash(data.tx_hash) + '</a>';
                }
            }
            return;
        }

        row.dataset.transactionStatus = data.status || '';
        row.dataset.txHash = data.tx_hash || '';

        const badge = row.querySelector('.status-badge');
        if (badge) {
            badge.textContent = statusLabels[data.status] || badge.textContent;
            badge.className = 'status-badge inline-flex rounded-full border px-3 py-2 text-xs font-semibold ' + (badgeClasses[data.status] || 'bg-slate-100 text-slate-700 border-slate-200');
        }

        const hashCell = row.querySelector('[data-hash-cell]');
        if (hashCell) {
            if (data.tx_hash) {
                hashCell.innerHTML = '<a class="font-mono text-slate-300 hover:text-white" href="' + explorerBase + data.tx_hash + '" target="_blank" rel="noopener noreferrer">' + shortHash(data.tx_hash) + '</a>';
            } else {
                hashCell.textContent = 'Waiting for broadcast...';
            }
        }
    }

    function fetchTransaction(id) {
        const url = apiUrlTemplate.replace('TRANSACTION_ID', id);
        return fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
            .then(resp => {
                if (!resp.ok) throw new Error('Network response was not ok');
                return resp.json();
            });
    }

    function startPolling(row) {
        const id = row.dataset.transactionId;
        if (!id || pollers[id]) return;
        const status = (row.dataset.transactionStatus || '').toLowerCase();
        if (isFinal(status)) return;

        const run = () => {
            fetchTransaction(id).then(data => {
                if (!data) return;
                updateRow(row, data);
                if (isFinal(data.status)) {
                    stopPolling(id);
                }
            }).catch(() => {
                // ignore errors and retry next interval
            });
        };

        run();
        pollers[id] = setInterval(run, 3000);
    }

    function stopPolling(id) {
        if (!pollers[id]) return;
        clearInterval(pollers[id]);
        delete pollers[id];
    }

    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('[data-transaction-id]').forEach(startPolling);
    });
})();
</script>
@endpush

@endsection
