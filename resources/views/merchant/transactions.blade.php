@extends('layouts.dashboard')

@section('title', __('merchant.transactions.page_title') . ' - CryptoPay')
@section('page-title', __('merchant.transactions.page_title'))
@section('page-subtitle', __('merchant.transactions.page_subtitle'))

@section('content')

<div class="transactions-shell max-w-[1400px] w-full mx-auto px-6 py-6" dir="{{ app()->getLocale() === 'fa' ? 'rtl' : 'ltr' }}">

<div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <!-- Total -->
    <div class="h-28 rounded-2xl border border-slate-700/40 bg-slate-900/60 p-4 shadow-sm">
        <div class="h-full flex flex-col items-center justify-center text-center">
            <p class="text-xs uppercase tracking-widest text-slate-400">{{ __('merchant.transactions.total_transactions') }}</p>
            <p class="mt-3 text-2xl lg:text-3xl font-semibold text-white">{{ $totalCount }}</p>
        </div>
    </div>

    <!-- Completed -->
    <div class="h-28 rounded-2xl border border-emerald-700/30 bg-emerald-900/10 p-4 shadow-sm">
        <div class="h-full flex flex-col items-center justify-center text-center">
            <p class="text-xs uppercase tracking-widest text-emerald-300">{{ __('merchant.transactions.completed') }}</p>
            <p class="mt-3 text-2xl lg:text-3xl font-semibold text-white">{{ $completedCount }}</p>
        </div>
    </div>

    <!-- Pending -->
    <div class="h-28 rounded-2xl border border-amber-700/30 bg-amber-900/5 p-4 shadow-sm">
        <div class="h-full flex flex-col items-center justify-center text-center">
            <p class="text-xs uppercase tracking-widest text-amber-300">{{ __('merchant.transactions.pending') }}</p>
            <p class="mt-3 text-2xl lg:text-3xl font-semibold text-white">{{ $pendingCount }}</p>
        </div>
    </div>

    <!-- Failed -->
    <div class="h-28 rounded-2xl border border-rose-700/30 bg-rose-900/5 p-4 shadow-sm">
        <div class="h-full flex flex-col items-center justify-center text-center">
            <p class="text-xs uppercase tracking-widest text-rose-300">{{ __('merchant.transactions.failed') }}</p>
            <p class="mt-3 text-2xl lg:text-3xl font-semibold text-white">{{ $failedCount }}</p>
        </div>
    </div>
</div>

<div class="mt-8 rounded-2xl border border-slate-700/40 bg-slate-900/40 backdrop-blur-sm p-5 shadow-sm">
    <form id="transaction-filters" method="GET" action="{{ route('merchant.transactions') }}" class="grid gap-3 lg:grid-cols-5 items-center">
        <div>
            <label class="block text-xs font-semibold text-slate-400 lg:w-32">{{ __('merchant.transactions.type') }}</label>
            <select name="type" onchange="this.form.submit()" class="mt-2 w-full rounded-lg border border-slate-700/50 bg-slate-900 px-3 h-11 text-sm text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                <option value="">{{ __('merchant.transactions.all') }}</option>
                <option value="deposit" @if(request('type') === 'deposit') selected @endif>{{ __('merchant.transactions.deposit') }}</option>
                <option value="transfer" @if(request('type') === 'transfer') selected @endif>{{ __('merchant.transactions.transfer') }}</option>
                <option value="withdraw" @if(request('type') === 'withdraw') selected @endif>{{ __('merchant.transactions.withdrawal') }}</option>
                <option value="payment" @if(request('type') === 'payment') selected @endif>{{ __('merchant.transactions.payment') }}</option>
                <option value="invoice" @if(request('type') === 'invoice') selected @endif>{{ __('merchant.transactions.invoice') }}</option>
            </select>
        </div>

        <div class="flex flex-col lg:flex-row lg:items-center lg:gap-3">
            <label class="block text-xs font-medium text-slate-400 mb-1 lg:mb-0 lg:w-32">{{ __('merchant.transactions.currency') }}</label>
            <select name="currency" onchange="this.form.submit()" class="mt-2 lg:mt-0 w-full rounded-lg border border-slate-700/50 bg-slate-900 px-3 h-11 text-sm text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                <option value="">{{ __('merchant.transactions.all') }}</option>
                @foreach($availableCurrencies ?? [] as $c)
                    <option value="{{ $c }}" @if(request('currency') === $c) selected @endif>{{ $c }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex flex-col lg:flex-row lg:items-center lg:gap-3">
            <label class="block text-xs font-medium text-slate-400 mb-1 lg:mb-0 lg:w-32">{{ __('merchant.transactions.amount') }}</label>
            <select name="amount_range" onchange="this.form.submit()" class="mt-2 lg:mt-0 w-full rounded-lg border border-slate-700/50 bg-slate-900 px-3 h-11 text-sm text-white focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                <option value="">{{ __('merchant.transactions.all') }}</option>
                <option value="0-0.1" @if(request('amount_range') === '0-0.1') selected @endif>{{ __('transactions.amount_range_under_0_1') }}</option>
                <option value="0.1-1" @if(request('amount_range') === '0.1-1') selected @endif>{{ __('transactions.amount_range_0_1_to_1') }}</option>
                <option value="1-10" @if(request('amount_range') === '1-10') selected @endif>{{ __('transactions.amount_range_1_to_10') }}</option>
                <option value="10+" @if(request('amount_range') === '10+') selected @endif>{{ __('transactions.amount_range_over_10') }}</option>
            </select>
        </div>

        <div class="lg:col-span-1 flex flex-col lg:flex-row lg:items-center lg:gap-3">
            <label class="block text-xs font-medium text-slate-400 mb-1 lg:mb-0 lg:w-32">{{ __('merchant.transactions.search') }}</label>
            <div class="mt-2 lg:mt-0 relative w-full">
                <span class="absolute inset-y-0 left-3 flex items-center text-slate-500">
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"/></svg>
                </span>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('merchant.transactions.search_placeholder') }}" class="w-full rounded-lg border border-slate-700/50 bg-slate-900 pl-10 pr-3 h-11 text-sm text-white placeholder:text-slate-500 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20" />
            </div>
        </div>

        <div class="lg:col-span-1 flex items-center justify-end gap-3 mt-2 lg:mt-0">
            <a href="{{ route('merchant.transactions') }}" class="inline-flex items-center justify-center rounded-md border border-slate-700/50 bg-transparent px-4 h-11 text-sm font-semibold text-slate-300 hover:bg-slate-900/60 transition">Clear</a>
            <button type="submit" class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 h-11 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition">Apply</button>
        </div>
    </form>
</div>

<form id="transaction-filters" method="GET" action="{{ route('merchant.transactions') }}" class="hidden"></form>

<!-- Transactions & Payments Table -->
<div class="rounded-lg overflow-hidden bg-transparent">
    <div class="p-4 border-b border-slate-700/40 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <h3 class="text-lg font-semibold text-white">{{ __('merchant.transactions.transactions_and_invoices') }}</h3>
            <a href="{{ route('merchant.transactions.export') }}{{ request()->getQueryString() ? ('?' . request()->getQueryString()) : '' }}" class="inline-flex items-center gap-2 rounded-md border border-slate-700/50 px-3 py-1 text-sm font-semibold text-slate-200 hover:bg-slate-900/60 transition">
                <i class="fas fa-file-csv ml-2"></i>{{ __('merchant.transactions.export_csv') }}
            </a>
        </div>
        <span class="text-sm text-slate-400">{{ __('merchant.transactions.showing_items', ['count' => ($transactions->count() + $paymentRequests->count()), 'total' => $transactions->total() + $paymentRequests->count()]) }}</span>
    </div>

    @php
        $network = strtolower((string)env('ETHEREUM_NETWORK', 'sepolia'));
        $explorerBase = $network === 'mainnet' ? 'https://etherscan.io/tx/' : ($network === 'sepolia' ? 'https://sepolia.etherscan.io/tx/' : 'https://etherscan.io/tx/');
    @endphp

    <div class="mt-8 rounded-[32px] border border-slate-200/60 bg-slate-950/90 p-6 shadow-xl shadow-slate-900/10">
    @if(($transactions->count() ?? 0) + ($paymentRequests->count() ?? 0) > 0)
        <div class="hidden lg:block">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-700 text-left text-sm text-slate-300">
                    <thead class="border-b border-slate-700/80 text-slate-400">
                        <tr>
                            <th class="px-4 py-3">{{ __('merchant.transactions.transaction') ?? 'Transaction' }}</th>
                            <th class="px-4 py-3">{{ __('merchant.transactions.type') ?? 'Type' }}</th>
                            <th class="px-4 py-3">{{ __('merchant.transactions.currency') ?? 'Asset' }}</th>
                            <th class="px-4 py-3 text-right">{{ __('merchant.transactions.amount') ?? 'Amount' }}</th>
                            <th class="px-4 py-3">{{ __('merchant.transactions.wallet') ?? 'Wallet' }}</th>
                            <th class="px-4 py-3">{{ __('merchant.transactions.status_label') ?? 'Status' }}</th>
                            <th class="px-4 py-3">{{ __('merchant.transactions.date') ?? 'Date' }}</th>
                            <th class="px-4 py-3">{{ __('merchant.transactions.actions') ?? 'Action' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700 border-b border-slate-700/80">
                        @foreach($transactions as $transaction)
                            @php
                                $transactionType = ucfirst($transaction->type ?? '');
                                $currency = $transaction->wallet?->currency ?? $transaction->currency ?? 'ETH';
                                $statusLabel = ucfirst($transaction->status ?? '');
                                $statusClass = in_array($transaction->status, ['processing','pending']) ? 'bg-amber-100 text-amber-700 border-amber-200' : (in_array($transaction->status, ['confirmed','completed']) ? 'bg-emerald-100 text-emerald-700 border-emerald-200' : (in_array($transaction->status, ['failed']) ? 'bg-rose-100 text-rose-700 border-rose-200' : 'bg-slate-200 text-slate-700 border-slate-300'));
                                $walletLabel = $transaction->wallet?->wallet_address ?: $transaction->receiver_wallet_address ?: $transaction->sender_wallet_address;
                                $walletShort = $walletLabel ? substr($walletLabel, 0, 8) . '...' . substr($walletLabel, -6) : 'Unknown';
                                $txHash = $transaction->tx_hash;
                                $hashLabel = $txHash ? (strlen($txHash) > 18 ? substr($txHash, 0, 10) . '...' . substr($txHash, -8) : $txHash) : __('merchant.transactions.waiting_for_broadcast');
                            @endphp
                            <tr id="merchant-transaction-row-{{ $transaction->id }}" data-transaction-id="{{ $transaction->id }}" data-transaction-status="{{ $transaction->status }}" data-tx-hash="{{ $transaction->tx_hash }}" data-updated-at="{{ $transaction->updated_at?->toDateTimeString() }}" class="hover:bg-slate-900/40 transition-colors duration-150">
                                <td class="px-4 py-4 align-top">
                                    <div class="text-sm font-semibold text-white">#TRX-{{ str_pad($transaction->id, 4, '0', STR_PAD_LEFT) }}</div>
                                    <div class="mt-1 text-xs text-slate-400 flex items-center gap-2">
                                        <span class="font-mono text-slate-300 truncate" style="max-width:14rem;" data-hash-cell>
                                            @if($txHash)
                                            <a class="hover:text-white font-mono text-slate-300 truncate" href="{{ $explorerBase ?? '#' }}{{ $txHash }}" target="_blank" rel="noopener noreferrer">{{ $hashLabel }}</a>
                                            @else
                                                {{ $hashLabel }}
                                            @endif
                                        </span>
                                        @if($txHash)
                                        <button onclick="event.stopPropagation(); navigator.clipboard.writeText('{{ $txHash }}').then(()=>{ /* feedback handled by browser */ }).catch(()=>{});" title="{{ __('transactions.copy_hash') }}" class="ml-2 inline-flex items-center justify-center rounded-md px-2 py-1 bg-slate-800/50 hover:bg-slate-800/70 text-slate-300 transition">
                                            <svg class="h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 16h8M8 12h8M8 8h8"/></svg>
                                        </button>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-4 align-top">
                                    <span class="inline-flex items-center gap-2 rounded-lg px-3 py-1 text-xs font-semibold uppercase tracking-[0.12em]" style="background-color: rgba(255,255,255,0.02);">
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
                                    <span class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-3 py-1 text-sm font-semibold text-white">{{ $currency }}</span>
                                </td>
                                <td class="px-4 py-4 align-top text-right">
                                    @php $isDeposit = ($transaction->type === 'deposit'); @endphp
                                    <div class="text-lg font-semibold {{ $isDeposit ? 'text-emerald-400' : 'text-rose-400' }}">{{ $isDeposit ? '+' : '-' }}{{ number_format((float)$transaction->amount, 8, '.', '') }}</div>
                                    <div class="text-xs text-slate-400">{{ $currency }}</div>
                                </td>
                                <td class="px-4 py-4 align-top">
                                    <div class="font-medium text-white">{{ $walletShort }}</div>
                                </td>
                                <td class="px-4 py-4 align-top">
                                    <span class="status-badge inline-flex items-center gap-2 rounded-full border px-2 py-1 text-xs font-semibold {{ $statusClass }}">
                                        @if((strtolower($transaction->status ?? '') === 'processing') || (strtolower($transaction->status ?? '') === 'pending'))
                                            <svg class="h-3 w-3 text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6l4 2"/></svg>
                                        @elseif(in_array(strtolower($transaction->status ?? ''), ['confirmed','completed']))
                                            <svg class="h-3 w-3 text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7"/></svg>
                                        @elseif(strtolower($transaction->status ?? '') === 'failed')
                                            <svg class="h-3 w-3 text-rose-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                        @else
                                            <svg class="h-3 w-3 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="3"/></svg>
                                        @endif
                                        <span>{{ $statusLabel }}</span>
                                    </span>
                                </td>
                                <td class="px-4 py-4 align-top text-slate-400">{{ $transaction->created_at->format('d M Y') }}<div class="text-xs mt-1">{{ $transaction->created_at->format('H:i') }}</div></td>
                                <td class="px-4 py-4 align-top">
                                    <button onclick="event.stopPropagation(); viewTransactionDetail('{{ $transaction->id }}', 'transaction', event)" class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-indigo-500">
                                            <svg class="h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7S3.732 16.057 2.458 12z"/></svg>
                                            <span>{{ __('transactions.view_button') }}</span>
                                        </button>
                                </td>
                            </tr>
                        @endforeach

                        @foreach($paymentRequests as $payment)
                            @php
                                $currency = $payment->currency ?? 'ETH';
                                $walletShort = 'Invoice';
                                $statusLabel = ucfirst($payment->status ?? '');
                                $statusClass = in_array($payment->status, ['paid','completed']) ? 'bg-emerald-100 text-emerald-700 border-emerald-200' : (in_array($payment->status, ['pending','processing']) ? 'bg-amber-100 text-amber-700 border-amber-200' : 'bg-slate-200 text-slate-700 border-slate-300');
                                $hashLabel = __('merchant.transactions.invoice') . ' #' . ($payment->invoice_number ?? $payment->id);
                            @endphp
                            <tr id="merchant-transaction-row-inv-{{ $payment->id }}" data-transaction-id="{{ $payment->id }}" data-transaction-status="{{ $payment->status }}" data-updated-at="{{ $payment->updated_at?->toDateTimeString() }}" class="hover:bg-slate-900/40 transition-colors duration-150">
                                    <td class="px-4 py-4 align-top">
                                        <div class="text-sm font-semibold text-white">INV-{{ str_pad($payment->id, 4, '0', STR_PAD_LEFT) }}</div>
                                        <div class="mt-1 text-xs text-slate-400">Invoice <span class="font-mono text-slate-300">#{{ $payment->invoice_number ?? $payment->id }}</span></div>
                                    </td>
                                    <td class="px-4 py-4 align-top">
                                        <span class="inline-flex items-center gap-2 rounded-lg px-3 py-1 text-xs font-semibold uppercase tracking-[0.12em]" style="background-color: rgba(255,255,255,0.02);">
                                            <svg class="h-4 w-4 text-purple-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l2-2 4 4"/></svg>
                                            {{ __('merchant.transactions.invoice') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 align-top">
                                        <span class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-3 py-1 text-sm font-semibold text-white">{{ $currency }}</span>
                                    </td>
                                    <td class="px-4 py-4 align-top text-right">
                                        <div class="text-lg font-semibold text-white">{{ number_format((float)$payment->amount, 8, '.', '') }}</div>
                                        <div class="text-xs text-slate-400">{{ $currency }}</div>
                                    </td>
                                    <td class="px-4 py-4 align-top">
                                        <div class="font-medium text-white">{{ $payment->recipient->name ?? '-' }}</div>
                                    </td>
                                    <td class="px-4 py-4 align-top">
                                        <span class="status-badge inline-flex items-center gap-2 rounded-full border px-3 py-2 text-xs font-semibold {{ $statusClass }}">{{ $statusLabel }}</span>
                                    </td>
                                    <td class="px-4 py-4 align-top text-slate-400">{{ $payment->created_at->format('d M Y') }}<div class="text-xs mt-1">{{ $payment->created_at->format('H:i') }}</div></td>
                                    <td class="px-4 py-4 align-top">
                                        <button onclick="event.stopPropagation(); viewTransactionDetail('{{ $payment->id }}', 'payment', event)" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:translate-x-0.5">View <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5l7 7-7 7"/></svg></button>
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
                    $transactionType = ucfirst($transaction->type ?? '');
                    $currency = $transaction->wallet?->currency ?? $transaction->currency ?? 'ETH';
                    $statusLabel = ucfirst($transaction->status ?? '');
                    $statusClass = in_array($transaction->status, ['processing','pending']) ? 'bg-amber-100 text-amber-700 border-amber-200' : (in_array($transaction->status, ['confirmed','completed']) ? 'bg-emerald-100 text-emerald-700 border-emerald-200' : 'bg-slate-200 text-slate-700 border-slate-300');
                    $walletLabel = $transaction->wallet?->wallet_address ?: $transaction->receiver_wallet_address ?: $transaction->sender_wallet_address;
                    $walletShort = $walletLabel ? substr($walletLabel, 0, 8) . '...' . substr($walletLabel, -6) : 'Unknown';
                    $txHash = $transaction->tx_hash;
                    $hashLabel = $txHash ? (strlen($txHash) > 18 ? substr($txHash, 0, 10) . '...' . substr($txHash, -8) : $txHash) : __('merchant.transactions.waiting_for_broadcast');
                @endphp
                <div id="merchant-transaction-row-{{ $transaction->id }}" class="rounded-[28px] border border-slate-700/70 bg-slate-900/80 p-5 shadow-sm shadow-slate-950/10" data-transaction-id="{{ $transaction->id }}" data-transaction-status="{{ $transaction->status }}" data-tx-hash="{{ $transaction->tx_hash }}" data-updated-at="{{ $transaction->updated_at?->toDateTimeString() }}">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-medium text-slate-400">#TRX-{{ str_pad($transaction->id, 4, '0', STR_PAD_LEFT) }}</p>
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
                        <button onclick="event.stopPropagation(); viewTransactionDetail('{{ $transaction->id }}', 'transaction', event)" class="inline-flex items-center justify-center rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-indigo-500">View Transaction</button>
                    </div>
                </div>
            @endforeach

            @foreach($paymentRequests as $payment)
                <div id="merchant-transaction-inv-{{ $payment->id }}" class="rounded-[28px] border border-slate-700/70 bg-slate-900/80 p-5 shadow-sm shadow-slate-950/10" data-transaction-id="inv-{{ $payment->id }}" data-transaction-status="{{ $payment->status }}">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-medium text-slate-400">INV-{{ str_pad($payment->id, 4, '0', STR_PAD_LEFT) }}</p>
                            <p class="mt-2 text-xl font-semibold text-white">{{ number_format((float)$payment->amount, 8, '.', '') }} {{ $payment->currency }}</p>
                        </div>
                        <span class="status-badge inline-flex rounded-full border px-3 py-2 text-xs font-semibold {{ in_array($payment->status, ['paid','completed']) ? 'bg-emerald-100 text-emerald-700 border-emerald-200' : 'bg-amber-100 text-amber-700 border-amber-200' }}">{{ ucfirst($payment->status) }}</span>
                    </div>
                    <div class="mt-4 grid gap-3">
                        <div class="rounded-3xl bg-slate-950/80 px-4 py-3">
                            <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Type</p>
                            <p class="mt-2 text-sm font-semibold text-white">{{ __('merchant.transactions.invoice') }}</p>
                        </div>
                        <div class="rounded-3xl bg-slate-950/80 px-4 py-3">
                            <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Recipient</p>
                            <p class="mt-2 text-sm font-mono text-slate-200">{{ $payment->recipient->name ?? '-' }}</p>
                        </div>
                        <div class="rounded-3xl bg-slate-950/80 px-4 py-3">
                            <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Date</p>
                            <p class="mt-2 text-sm text-slate-200">{{ $payment->created_at->format('Y/m/d H:i') }}</p>
                        </div>
                    </div>
                    <div class="mt-5 flex flex-wrap items-center gap-3">
                        <button onclick="event.stopPropagation(); viewTransactionDetail('{{ $payment->id }}', 'payment', event)" class="inline-flex items-center justify-center rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-indigo-500">View Invoice</button>
                    </div>
                </div>
            @endforeach
        </div>

        @if(($transactions->count() ?? 0) + ($paymentRequests->count() ?? 0) === 0)
            <div class="rounded-[28px] border border-slate-700/70 bg-slate-900/80 p-12 text-center text-slate-300">
                <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-slate-800 text-white">
                    <i class="fas fa-inbox text-2xl"></i>
                </div>
                <h2 class="mt-6 text-2xl font-semibold text-white">{{ __('merchant.transactions.no_transactions') }}</h2>
                <p class="mt-2 text-sm text-slate-400">{{ __('merchant.transactions.no_transactions_subtext') ?? 'No transactions found.' }}</p>
                <button type="button" onclick="location.reload();" class="mt-6 inline-flex items-center justify-center rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-indigo-500">Refresh</button>
            </div>
        @endif

        @if($transactions->hasPages())
            <div class="mt-8 flex justify-center">
                {{ $transactions->links() }}
            </div>
        @endif

    @else
        <div class="rounded-[28px] border border-slate-700/70 bg-slate-900/80 p-12 text-center text-slate-300">
            <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-slate-800 text-white">
                <i class="fas fa-inbox text-2xl"></i>
            </div>
            <h2 class="mt-6 text-2xl font-semibold text-white">{{ __('merchant.transactions.no_transactions') }}</h2>
            <p class="mt-2 text-sm text-slate-400">{{ __('merchant.transactions.no_transactions_subtext') ?? 'No transactions found.' }}</p>
            <button type="button" onclick="location.reload();" class="mt-6 inline-flex items-center justify-center rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-indigo-500">Refresh</button>
        </div>
    @endif

    <!-- Transaction Detail Modal -->
    <div id="detailModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-lg p-8 w-full max-w-4xl mx-4 max-h-screen overflow-y-auto">
            <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800">{{ __('merchant.transactions.transaction_details') }}</h3>
                <button onclick="closeDetailModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <div id="modalContent" class="space-y-4">
                <!-- Content will be loaded here -->
            </div>

            <div class="mt-8 flex gap-2 border-t border-gray-200 pt-4">
                <button onclick="closeDetailModal()" class="flex-1 bg-gray-200 text-gray-800 py-2 rounded-lg font-semibold hover:bg-gray-300 transition">
                    {{ __('merchant.transactions.close') }}
                </button>
                <button onclick="downloadTransaction()" class="flex-1 bg-indigo-600 text-white py-2 rounded-lg font-semibold hover:bg-indigo-700 transition">
                    <i class="fas fa-download ml-2"></i>{{ __('merchant.transactions.download') }}
                </button>
            </div>
        </div>
    </div>

    </div>


@push('scripts')
<script>
    let currentDetail = null;
    const explorerBase = "{{ (strtolower((string)env('ETHEREUM_NETWORK','sepolia')) === 'mainnet') ? 'https://etherscan.io/tx/' : (strtolower((string)env('ETHEREUM_NETWORK','sepolia')) === 'sepolia' ? 'https://sepolia.etherscan.io/tx/' : 'https://etherscan.io/tx/') }}";

    function viewTransactionDetail(id, type, event) {
        currentDetail = { id, type };
        const modal = document.getElementById('detailModal');
        const content = document.getElementById('modalContent');

        const row = document.getElementById('merchant-transaction-row-' + id) || event?.target?.closest('tr');
        if (!row) return;

        const ref = row.dataset.reference || '';
        const typeLabel = (row.querySelector('td:nth-child(3)')?.textContent || '');
        const customer = row.dataset.customer || '';
        const description = row.dataset.description || '';
        const amount = row.dataset.amount || '';
        const currency = row.dataset.currency || '';
        const txhash = row.dataset.txhash || '';
        const statusText = row.querySelector('.tx-status-cell')?.textContent || row.dataset.transactionStatus || '';
        const dateText = row.dataset.date || '';

        let txHtml = '';
        if (txhash) {
            txHtml = `<div class="flex items-center gap-2">
                        <a href="${explorerBase || '#'}${txhash}" target="_blank" rel="noopener noreferrer" class="text-sm text-gray-700 break-all">${txhash}</a>
                        <button type="button" class="copy-hash-btn text-gray-500 hover:text-gray-700 p-1" data-hash="${txhash}" title="{{ __('merchant.transactions.copy') }}"><i class="far fa-copy text-xs"></i></button>
                      </div>`;
        } else {
            txHtml = `<span class="text-gray-500">{{ __('merchant.transactions.waiting_for_broadcast') }}</span>`;
        }

        const html = `
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-gray-500 text-sm">{{ __('merchant.transactions.id') }}</p>
                        <p class="font-semibold text-gray-800">${ref}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">{{ __('merchant.transactions.type') }}</p>
                        <p class="font-semibold text-gray-800">${typeLabel}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">{{ __('merchant.transactions.customer_source') }}</p>
                        <p class="font-semibold text-gray-800">${customer}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">{{ __('merchant.transactions.description') }}</p>
                        <p class="font-semibold text-gray-800">${description}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">{{ __('merchant.transactions.amount') }}</p>
                        <p class="font-semibold text-gray-800">${amount} ${currency}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">{{ __('merchant.transactions.status_label') }}</p>
                        <p class="font-semibold text-gray-800">${statusText}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">{{ __('merchant.transactions.date') }}</p>
                        <p class="font-semibold text-gray-800">${dateText}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">{{ __('merchant.transactions.tx_hash') }}</p>
                        ${txHtml}
                    </div>
                </div>
                <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                    <p class="text-sm text-blue-800">
                        <i class="fas fa-info-circle ml-2"></i>
                        {{ __('merchant.transactions.modal_notice') }}
                    </p>
                </div>
            </div>
        `;

        content.innerHTML = html;
        modal.classList.remove('hidden');
    }

    function closeDetailModal() {
        document.getElementById('detailModal').classList.add('hidden');
        currentDetail = null;
    }

    function downloadTransaction() {
        if (!currentDetail) return;
        if (currentDetail.type === 'payment') {
            window.location = '/merchant/invoices/' + encodeURIComponent(currentDetail.id) + '/download';
        } else {
            window.location = '/merchant/transactions/' + encodeURIComponent(currentDetail.id) + '/download';
        }
    }

    // Copy hash handler (event delegation)
    document.addEventListener('click', function(e){
        const btn = e.target.closest && e.target.closest('.copy-hash-btn');
        if (!btn) return;
        const hash = btn.getAttribute('data-hash');
        if (!hash) return;
        navigator.clipboard?.writeText(hash).then(()=>{
            // minimal feedback: change title briefly
            const old = btn.title;
            btn.title = '{{ __('merchant.transactions.copied') ?? "Copied" }}';
            setTimeout(()=> btn.title = old, 1200);
        }).catch(()=>{});
    });
</script>

<script>
(function(){
    const apiUrlTemplate = "{{ route('api.transaction.show', ['transaction' => 'TRANSACTION_ID']) }}";
    const pollIntervalMs = 3000;
    const network = "{{ strtolower((string)env('ETHEREUM_NETWORK', 'sepolia')) }}";
    const explorerBaseLocal = network === 'mainnet' ? 'https://etherscan.io/tx/' : (network === 'sepolia' ? 'https://sepolia.etherscan.io/tx/' : 'https://etherscan.io/tx/');

    function shortHash(hash) {
        if (!hash) return '';
        if (hash.length <= 18) return hash;
        return hash.slice(0, 10) + '...' + hash.slice(-6);
    }

    const statusRank = {
        'processing': 1,
        'pending': 2,
        'confirmed': 3,
        'completed': 4,
        'failed': 100,
        'cancelled': 100
    };

    function parseDate(value) {
        if (!value) return null;
        const t = Date.parse(value);
        return isNaN(t) ? null : new Date(t);
    }

    function isFinal(status) {
        return ['confirmed','completed','failed','cancelled'].includes((status||'').toLowerCase());
    }

    function statusBadgeHtml(status) {
        const normalized = (status || '').toLowerCase();
        if (['completed','confirmed'].includes(normalized)) return '<span class="tx-status-cell inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">✓ {{ __('merchant.transactions.completed') }}</span>';
        if (['pending','processing'].includes(normalized)) return '<span class="tx-status-cell inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">⏱ {{ __('merchant.transactions.pending') }}</span>';
        return '<span class="tx-status-cell inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">✗ {{ __('merchant.transactions.failed') }}</span>';
    }

    const timers = {};

    function shouldApply(row, incoming) {
        const existingStatus = (row.dataset.transactionStatus || '').toLowerCase();
        const existingUpdated = parseDate(row.dataset.updatedAt || row.dataset.updated_at || '');
        const incomingStatus = (incoming.status || '').toLowerCase();
        const incomingUpdated = parseDate(incoming.updated_at || '');

        // never overwrite final with non-final
        if (isFinal(existingStatus) && !isFinal(incomingStatus)) return false;

        if (!existingUpdated || !incomingUpdated) {
            return (statusRank[incomingStatus] || 0) >= (statusRank[existingStatus] || 0);
        }

        if (incomingUpdated > existingUpdated) return true;
        if (incomingUpdated.getTime() === existingUpdated.getTime()) return (statusRank[incomingStatus] || 0) >= (statusRank[existingStatus] || 0);
        if (incomingUpdated < existingUpdated) return (!isFinal(existingStatus)) && ((statusRank[incomingStatus] || 0) > (statusRank[existingStatus] || 0));
        return false;
    }

    function applyUpdate(row, data) {
        const existingStatus = (row.dataset.transactionStatus || '').toLowerCase();
        const incomingStatus = (data.status || '').toLowerCase();

        // update hash cell independently
        if (data.tx_hash) {
            const wrapper = row.querySelector('td:nth-child(6)');
            if (wrapper) {
                wrapper.innerHTML = '<div class="flex items-center gap-2"><a href="'+explorerBaseLocal+data.tx_hash+'" target="_blank" rel="noopener noreferrer" class="tx-hash text-xs text-gray-700">'+shortHash(data.tx_hash)+'</a><button type="button" class="copy-hash-btn text-gray-500 hover:text-gray-700 p-1" data-hash="'+data.tx_hash+'" title="{{ __('merchant.transactions.copy') }}"><i class="far fa-copy text-xs"></i></button></div>';
            }
        } else {
            const wrapper = row.querySelector('td:nth-child(6)');
            if (wrapper && !isFinal(existingStatus)) wrapper.textContent = (data.status === 'processing' || data.status === 'pending') ? '{{ __('merchant.transactions.waiting_for_broadcast') }}' : '-';
        }

        if (!shouldApply(row, data)) return;

        row.dataset.transactionStatus = data.status || '';
        if (data.updated_at) row.dataset.updatedAt = data.updated_at;
        if (data.tx_hash) row.dataset.txhash = data.tx_hash || '';

        const statusCell = row.querySelector('.tx-status-cell') || row.querySelector('td:nth-child(5)');
        if (statusCell) {
            const parent = statusCell.closest('td') || statusCell.parentElement;
            if (parent) parent.innerHTML = statusBadgeHtml(data.status);
        }
    }

    function fetchAndUpdate(id) {
        const url = apiUrlTemplate.replace('TRANSACTION_ID', id);
        fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
            .then(r => { if (!r.ok) throw new Error('Network'); return r.json(); })
            .then(data => {
                const row = document.getElementById('merchant-transaction-row-' + id);
                if (!row) return;

                applyUpdate(row, data || {});

                if (isFinal(data.status)) {
                    const timersId = id;
                    if (timers[timersId]) { clearInterval(timers[timersId]); delete timers[timersId]; }
                }
            })
            .catch(()=>{});
    }

    document.addEventListener('DOMContentLoaded', function(){
        document.querySelectorAll('tr[data-transaction-id]').forEach(function(row){
            const st = (row.dataset.transactionStatus || '').toLowerCase();
            if (!isFinal(st)) {
                const id = row.dataset.transactionId || row.getAttribute('data-transaction-id');
                if (!timers[id]) { fetchAndUpdate(id); timers[id] = setInterval(()=> fetchAndUpdate(id), pollIntervalMs); }
            }
        });
    });

    // Copy button delegation handled by global listener in this file
})();
</script>
@endpush

@endsection
