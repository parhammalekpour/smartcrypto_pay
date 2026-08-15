@extends('layouts.dashboard')

@section('title', __('transactions.page_title') . ' - CryptoPay')
@section('page-title', __('transactions.page_title'))
@section('page-subtitle', __('transactions.page_subtitle'))

@section('content')
@php $isRtl = app()->getLocale() === 'fa'; @endphp
@php
    $network = strtolower((string)env('ETHEREUM_NETWORK', 'sepolia'));
    $explorerBase = $network === 'mainnet' ? 'https://etherscan.io/tx/' : ($network === 'sepolia' ? 'https://sepolia.etherscan.io/tx/' : 'https://etherscan.io/tx/');
@endphp

<div class="transactions-shell max-w-[1400px] w-full mx-auto px-6 py-6" x-data="{ refreshing: false }" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">


    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <!-- Total -->
        <div class="h-28 rounded-2xl border border-slate-700/40 bg-gradient-to-b from-slate-900/60 to-slate-950/40 p-4 shadow-sm">
            <div class="h-full flex flex-col items-center justify-center text-center">
                <p class="text-xs uppercase tracking-widest text-slate-400">Total Transactions</p>
                <p class="mt-3 text-2xl lg:text-3xl font-semibold text-white">{{ $stats['total'] ?? 0 }}</p>
            </div>
        </div>

        <!-- Completed -->
        <div class="h-28 rounded-2xl border border-emerald-700/30 bg-gradient-to-b from-emerald-900/10 to-slate-950/30 p-4 shadow-sm">
            <div class="h-full flex flex-col items-center justify-center text-center">
                <p class="text-xs uppercase tracking-widest text-emerald-300">Completed</p>
                <p class="mt-3 text-2xl lg:text-3xl font-semibold text-white">{{ $stats['completed'] ?? 0 }}</p>
            </div>
        </div>

        <!-- Pending -->
        <div class="h-28 rounded-2xl border border-amber-700/30 bg-gradient-to-b from-amber-900/5 to-slate-950/30 p-4 shadow-sm">
            <div class="h-full flex flex-col items-center justify-center text-center">
                <p class="text-xs uppercase tracking-widest text-amber-300">Pending</p>
                <p class="mt-3 text-2xl lg:text-3xl font-semibold text-white">{{ $stats['pending'] ?? 0 }}</p>
            </div>
        </div>

        <!-- Failed -->
        <div class="h-28 rounded-2xl border border-rose-700/30 bg-gradient-to-b from-rose-900/5 to-slate-950/30 p-4 shadow-sm">
            <div class="h-full flex flex-col items-center justify-center text-center">
                <p class="text-xs uppercase tracking-widest text-rose-300">Failed</p>
                <p class="mt-3 text-2xl lg:text-3xl font-semibold text-white">{{ $stats['failed'] ?? 0 }}</p>
            </div>
        </div>
    </div>

    <div class="mt-8 rounded-2xl border border-slate-700/40 bg-slate-900/40 backdrop-blur-sm p-5 shadow-sm">
        <form id="transaction-filters" method="GET" action="{{ route('user.transactions') }}" class="grid gap-3 lg:grid-cols-5 items-center">
            <div class="flex flex-col lg:flex-row lg:items-center lg:gap-3">
                <label class="block text-xs font-semibold text-slate-400 mb-1 lg:mb-0 lg:w-32">{{ __('transactions.type_label') }}</label>
                <select name="type" onchange="this.form.submit()" class="mt-2 lg:mt-0 w-full rounded-lg border border-slate-700/50 bg-slate-900 px-3 h-11 text-sm text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                    <option value="">{{ __('transactions.all') }}</option>
                    <option value="transfer" @if(request('type') === 'transfer') selected @endif>{{ __('transactions.type_transfer') }}</option>
                    <option value="deposit" @if(request('type') === 'deposit') selected @endif>{{ __('transactions.type_deposit') }}</option>
                </select>
            </div>
            <div class="flex flex-col lg:flex-row lg:items-center lg:gap-3">
                <label class="block text-xs font-semibold text-slate-400 mb-1 lg:mb-0 lg:w-32">{{ __('transactions.currency_label') }}</label>
                <select name="currency" onchange="this.form.submit()" class="mt-2 lg:mt-0 w-full rounded-lg border border-slate-700/50 bg-slate-900 px-3 h-11 text-sm text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                    <option value="">{{ __('transactions.all') }}</option>
                    <option value="BTC" @if(request('currency') === 'BTC') selected @endif>Bitcoin (BTC)</option>
                    <option value="ETH" @if(request('currency') === 'ETH') selected @endif>Ethereum (ETH)</option>
                    <option value="USDT" @if(request('currency') === 'USDT') selected @endif>Tether (USDT)</option>
                </select>
            </div>
            <div class="flex flex-col lg:flex-row lg:items-center lg:gap-3">
                <label class="block text-xs font-semibold text-slate-400 mb-1 lg:mb-0 lg:w-32">{{ __('transactions.amount_label') }}</label>
                <select name="amount_range" onchange="this.form.submit()" class="mt-2 lg:mt-0 w-full rounded-lg border border-slate-700/50 bg-slate-900 px-3 h-11 text-sm text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                    <option value="">{{ __('transactions.all') }}</option>
                    <option value="0-0.1" @if(request('amount_range') === '0-0.1') selected @endif>{{ __('transactions.amount_range_under_0_1') }}</option>
                    <option value="0.1-1" @if(request('amount_range') === '0.1-1') selected @endif>{{ __('transactions.amount_range_0_1_to_1') }}</option>
                    <option value="1-10" @if(request('amount_range') === '1-10') selected @endif>{{ __('transactions.amount_range_1_to_10') }}</option>
                    <option value="10+" @if(request('amount_range') === '10+') selected @endif>{{ __('transactions.amount_range_over_10') }}</option>
                </select>
            </div>
            <div class="flex flex-col lg:flex-row lg:items-center lg:gap-3">
                <label class="block text-xs font-semibold text-slate-400 mb-1 lg:mb-0 lg:w-32">{{ __('transactions.search_label') }}</label>
                <div class="mt-2 lg:mt-0 relative w-full">
                    <span class="absolute inset-y-0 left-3 flex items-center text-slate-500">
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"/></svg>
                    </span>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('transactions.search_placeholder') }}" class="w-full rounded-lg border border-slate-700/50 bg-slate-900 pl-10 pr-3 h-11 text-sm text-white placeholder:text-slate-500 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" />
                </div>
            </div>

            <div class="lg:col-span-1 flex items-center justify-end gap-3 mt-2">
                <button type="submit" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 h-11 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition">
                    {{ __('transactions.search_button') }}
                </button>
                <a href="{{ route('user.transactions') }}" class="inline-flex items-center justify-center rounded-md border border-slate-700/50 bg-transparent px-4 h-11 text-sm font-semibold text-slate-300 hover:bg-slate-900/60 transition">{{ __('transactions.clear_button') }}</a>
            </div>
        </form>
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
                                <tr id="transaction-row-{{ $transaction->id }}" data-transaction-id="{{ $transaction->id }}" data-transaction-status="{{ $transaction->status }}" data-tx-hash="{{ $transaction->tx_hash }}" data-updated-at="{{ $transaction->updated_at?->toDateTimeString() }}" class="hover:bg-slate-900/30 transform-gpu hover:scale-[1.01] transition-all duration-200">
                                    <td class="px-4 py-4 align-top">
                                        <div class="flex items-start gap-3">
                                            <div class="min-w-0">
                                                <div class="text-sm font-semibold text-white">#TX-{{ str_pad($transaction->id, 4, '0', STR_PAD_LEFT) }}</div>
                                                <div class="mt-1 text-xs text-slate-400 flex items-center gap-2">
                                                    <span class="font-mono text-slate-300 truncate" style="max-width:14rem;" data-hash-cell>
                                                        @if($txHash)
                                                            <a class="hover:text-white font-mono text-slate-300 truncate" href="{{ ($txHash && $explorerBase) ? ($explorerBase . $txHash) : '#' }}" target="_blank" rel="noopener noreferrer">{{ $hashLabel }}</a>
                                                        @else
                                                            {{ $hashLabel }}
                                                        @endif
                                                    </span>
                                                    @if($txHash)
                                                        <button onclick="event.stopPropagation(); navigator.clipboard.writeText('{{ $txHash }}').then(()=>{ /* visual feedback handled by browser */ }).catch(()=>{});" title="{{ __('transactions.copy_hash') }}" class="ml-2 inline-flex items-center justify-center rounded-md px-2 py-1 bg-slate-800/50 hover:bg-slate-800/70 text-slate-300 transition">
                                                            <svg class="h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 16h8M8 12h8M8 8h8"/></svg>
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-4 py-4 align-top">
                                        <span class="inline-flex items-center gap-2 rounded-lg px-3 py-1 text-xs font-semibold uppercase tracking-[0.12em]"
                                              style="background-color: rgba(255,255,255,0.02);">
                                            @if($transaction->type === 'deposit')
                                                <svg class="h-4 w-4 text-emerald-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4"/></svg>
                                            @elseif($transaction->type === 'withdraw')
                                                <svg class="h-4 w-4 text-rose-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 20V4m-8 8h16"/></svg>
                                            @else
                                                <svg class="h-4 w-4 text-amber-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 7h16M4 12h16M4 17h16"/></svg>
                                            @endif
                                            <span class="text-slate-300">{{ $transactionType }}</span>
                                        </span>
                                    </td>

                                    <td class="px-4 py-4 align-top">
                                        <span class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-3 py-1 text-sm font-semibold text-white">
                                            {{ $currency }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-4 align-top text-right">
                                        @php $isDeposit = ($transaction->type === 'deposit'); @endphp
                                    <div class="text-lg font-semibold {{ $isDeposit ? 'text-emerald-400' : 'text-rose-400' }}">{{ $isDeposit ? '+' : '-' }}{{ number_format((float)$transaction->amount, 8, '.', '') }}</div>
                                        <div class="text-xs text-slate-400">{{ $currency }}</div>
                                    </td>

                                    <td class="px-4 py-4 align-top">
                                        <div class="flex items-center gap-2">
                                            <div class="font-mono text-sm text-slate-300">{{ $walletShort }}</div>
                                            @if($walletLabel)
                                                <button onclick="event.stopPropagation(); navigator.clipboard.writeText('{{ $walletLabel }}').then(()=>{ this && (this.innerText='Copied!') }).catch(()=>{});" title="Copy address" class="text-slate-400 hover:text-white text-xs">📋</button>
                                            @endif
                                        </div>
                                    </td>

                                    <td class="px-4 py-4 align-top">
                                        <span class="status-badge inline-flex items-center gap-3 rounded-full px-3 py-1 text-xs font-semibold shadow-sm {{ $statusClass }}" style="backdrop-filter: blur(2px);">
                                            {{-- Icon is visual only; keep status text mapping as before --}}
                                            @if((strtolower($transaction->status ?? '') === 'processing') || (strtolower($transaction->status ?? '') === 'pending'))
                                                <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-amber-600/20 flex-none">
                                                    <svg class="h-4 w-4 text-amber-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6l4 2"/></svg>
                                                </span>
                                            @elseif(in_array(strtolower($transaction->status ?? ''), ['confirmed','completed']))
                                                <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-emerald-600/20 flex-none">
                                                    <svg class="h-4 w-4 text-emerald-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7"/></svg>
                                                </span>
                                            @elseif(strtolower($transaction->status ?? '') === 'failed')
                                                <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-rose-600/20 flex-none">
                                                    <svg class="h-4 w-4 text-rose-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                                </span>
                                            @else
                                                <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-slate-400/20 flex-none">
                                                    <svg class="h-4 w-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="3"/></svg>
                                                </span>
                                            @endif
                                            <span>{{ $statusLabel }}</span>
                                        </span>
                                    </td>

                                    <td class="px-4 py-4 align-top text-slate-400">
                                        <div class="text-sm">{{ $transaction->created_at->format('d M Y') }}</div>
                                        <div class="text-xs mt-1">{{ $transaction->created_at->format('H:i') }}</div>
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
                    <div id="transaction-row-{{ $transaction->id }}" class="rounded-xl border border-slate-700/60 bg-gradient-to-br from-slate-900/60 via-slate-800/50 to-slate-900/80 p-4 shadow-2xl" data-transaction-id="{{ $transaction->id }}" data-transaction-status="{{ $transaction->status }}" data-tx-hash="{{ $transaction->tx_hash }}" data-updated-at="{{ $transaction->updated_at?->toDateTimeString() }}">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-slate-400">#TX-{{ str_pad($transaction->id, 4, '0', STR_PAD_LEFT) }}</p>
                                <div class="mt-2 flex items-baseline gap-3">
                                    <p class="text-lg font-semibold text-white">{{ $transaction->type === 'deposit' ? '+' : '-' }}{{ number_format((float)$transaction->amount, 8, '.', '') }}</p>
                                    <span class="text-sm text-slate-400">{{ $currency }}</span>
                                </div>
                            </div>

                            <span class="status-badge inline-flex items-center gap-3 rounded-full border px-3 py-1 text-xs font-semibold {{ $statusClass }}">
                                @if((strtolower($transaction->status ?? '') === 'processing') || (strtolower($transaction->status ?? '') === 'pending'))
                                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-amber-600/20 flex-none">
                                        <svg class="h-4 w-4 text-amber-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6l4 2"/></svg>
                                    </span>
                                @elseif(in_array(strtolower($transaction->status ?? ''), ['confirmed','completed']))
                                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-emerald-600/20 flex-none">
                                        <svg class="h-4 w-4 text-emerald-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7"/></svg>
                                    </span>
                                @elseif(strtolower($transaction->status ?? '') === 'failed')
                                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-rose-600/20 flex-none">
                                        <svg class="h-4 w-4 text-rose-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </span>
                                @else
                                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-slate-400/20 flex-none">
                                        <svg class="h-4 w-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="3"/></svg>
                                    </span>
                                @endif
                                <span>{{ $statusLabel }}</span>
                            </span>
                        </div>

                        <div class="mt-3 grid grid-cols-2 gap-3 text-sm">
                            <div class="rounded-lg bg-slate-950/60 px-3 py-2">
                                <p class="text-xs uppercase tracking-widest text-slate-400">Type</p>
                                <p class="mt-1 text-sm font-semibold text-white">{{ $transactionType }}</p>
                            </div>
                            <div class="rounded-lg bg-slate-950/60 px-3 py-2">
                                <p class="text-xs uppercase tracking-widest text-slate-400">Wallet</p>
                                <div class="mt-1 flex items-center justify-between">
                                    <div class="font-mono text-sm text-slate-200 truncate">{{ $walletShort }}</div>
                                    @if($walletLabel)
                                        <button onclick="event.stopPropagation(); navigator.clipboard.writeText('{{ $walletLabel }}').then(()=>{ this && (this.innerText='Copied!') }).catch(()=>{});" title="Copy address" class="text-slate-400 text-xs">📋</button>
                                    @endif
                                </div>
                            </div>
                            <div class="rounded-lg bg-slate-950/60 px-3 py-2">
                                <p class="text-xs uppercase tracking-widest text-slate-400">Date</p>
                                <p class="mt-1 text-sm text-slate-200">{{ $transaction->created_at->format('d M Y • H:i') }}</p>
                            </div>
                            <div class="rounded-lg bg-slate-950/60 px-3 py-2">
                                <p class="text-xs uppercase tracking-widest text-slate-400">Hash</p>
                                <p class="mt-1 text-sm font-mono text-slate-200 break-words" data-hash-cell>{{ $hashLabel }}</p>
                            </div>
                        </div>

                    </div>
                @endforeach
            </div>
        @else
            <div class="rounded-[28px] border border-slate-700/70 bg-slate-900/80 p-12 text-center text-slate-300">
                <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-slate-800 text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 13l2 6h14l2-6M16 7V3H8v4M3 13h18"/></svg>
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
        'processing': '{{ __('transactions.processing') }}',
        'pending': '{{ __('common.pending') }}',
        'confirmed': '{{ __('common.confirmed') }}',
        'completed': '{{ __('common.completed') }}',
        'failed': '{{ __('common.failed') }}',
        'cancelled': '{{ __('common.cancelled') }}'
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

    const statusRank = {
        'processing': 1,
        'pending': 2,
        'confirmed': 3,
        'completed': 4,
        'failed': 100,
        'cancelled': 100
    };

    function isFinal(status) {
        return ['confirmed', 'completed', 'failed', 'cancelled'].includes(status);
    }

    function parseDate(value) {
        if (!value) return null;
        const t = Date.parse(value);
        return isNaN(t) ? null : new Date(t);
    }

    function shortHash(hash) {
        if (!hash) return '';
        return hash.length <= 18 ? hash : hash.slice(0, 10) + '...' + hash.slice(-8);
    }

    function shouldApplyUpdate(row, incoming) {
        const existingStatus = (row.dataset.transactionStatus || '').toLowerCase();
        const existingUpdatedAt = parseDate(row.dataset.updatedAt || row.dataset.updated_at || '');
        const incomingStatus = (incoming.status || '').toLowerCase();
        const incomingUpdatedAt = parseDate(incoming.updated_at || '');

        const existingFinal = isFinal(existingStatus);
        const incomingFinal = isFinal(incomingStatus);

        // Never overwrite a final client state with a non-final incoming state
        if (existingFinal && !incomingFinal) return false;

        // If no timestamps available, fall back to status rank
        if (!existingUpdatedAt || !incomingUpdatedAt) {
            return (statusRank[incomingStatus] || 0) >= (statusRank[existingStatus] || 0);
        }

        if (incomingUpdatedAt > existingUpdatedAt) return true;
        if (incomingUpdatedAt.getTime() === existingUpdatedAt.getTime()) {
            return (statusRank[incomingStatus] || 0) >= (statusRank[existingStatus] || 0);
        }
        // incoming is older, allow only if its rank is strictly higher and current is not final
        if (incomingUpdatedAt < existingUpdatedAt) {
            return (!existingFinal) && ((statusRank[incomingStatus] || 0) > (statusRank[existingStatus] || 0));
        }
        return false;
    }

    function updateRow(row, data) {
        const existingStatus = (row.dataset.transactionStatus || '').toLowerCase();
        const incomingStatus = (data.status || '').toLowerCase();

        // Update hash independently when present
        const hashCell = row.querySelector('[data-hash-cell]');
        if (hashCell) {
            if (data.tx_hash) {
                hashCell.innerHTML = '<a class="font-mono text-slate-300 hover:text-white" href="' + explorerBase + data.tx_hash + '" target="_blank" rel="noopener noreferrer">' + shortHash(data.tx_hash) + '</a>';
            } else if (!isFinal(existingStatus)) {
                hashCell.textContent = '{{ __('merchant.transactions.waiting_for_broadcast') ?? "Waiting for broadcast..." }}';
            }
        }

        if (!shouldApplyUpdate(row, data)) return;

        // apply updates
        row.dataset.transactionStatus = data.status || '';
        row.dataset.updatedAt = data.updated_at || row.dataset.updatedAt || '';
        if (data.tx_hash) row.dataset.txHash = data.tx_hash || '';

        const badge = row.querySelector('.status-badge');
        if (badge) {
            badge.textContent = statusLabels[data.status] || badge.textContent;
            badge.className = 'status-badge inline-flex rounded-full border px-3 py-2 text-xs font-semibold ' + (badgeClasses[data.status] || 'bg-slate-100 text-slate-700 border-slate-200');
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
