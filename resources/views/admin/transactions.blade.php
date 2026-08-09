@extends('layouts.admin')

@section('title', __('admin.transactions.page_title'))

@section('content')
<div class="rounded-2xl bg-white p-5 shadow-sm">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-lg font-semibold">{{ __('admin.transactions.manage_transactions') }}</h2>
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('admin.transactions.search_placeholder') }}" class="rounded-lg border border-slate-300 px-3 py-2">
            <select name="status" class="rounded-lg border border-slate-300 px-3 py-2">
                <option value="">{{ __('admin.transactions.all_statuses') }}</option>
                @foreach($statuses as $status)
                    <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ __($status) }}</option>
                @endforeach
            </select>
            <button type="submit" class="rounded-lg bg-indigo-600 px-3 py-2 text-sm text-white">{{ __('admin.transactions.filter') }}</button>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b text-right text-slate-500">
                    <th class="py-2">{{ __('admin.transactions.id') }}</th>
                    <th class="py-2">{{ __('admin.transactions.amount') }}</th>
                    <th class="py-2">{{ __('admin.transactions.type') }}</th>
                    <th class="py-2">{{ __('admin.transactions.status') }}</th>
                    <th class="py-2">{{ __('admin.transactions.sender') }}</th>
                    <th class="py-2">{{ __('admin.transactions.recipient') }}</th>
                    <th class="py-2">{{ __('admin.transactions.description') }}</th>
                    <th class="py-2">{{ __('admin.transactions.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $transaction)
                    <tr class="border-b">
                        <td class="py-3">{{ $transaction->id }}</td>
                        <td class="py-3">{{ $transaction->amount }}</td>
                        <td class="py-3">{{ $transaction->type }}</td>
                        <td class="py-3">{{ $transaction->status }}</td>
                        <td class="py-3">
                            {{ $transaction->sender->name ?? $transaction->sender_wallet_address ?? '—' }}
                        </td>
                        <td class="py-3">{{ $transaction->recipient->name ?? '—' }}</td>
                        <td class="py-3">
                            {{ $transaction->description }}
                            @if($transaction->type === 'deposit' && $transaction->deposit)
                                <div class="mt-2 text-xs text-slate-500 space-y-1">
                                    @if($transaction->sender_wallet_address)
                                        <div>{{ __('admin.transactions.sender_wallet') }}: <code dir="ltr" class="text-slate-700">{{ $transaction->sender_wallet_address }}</code></div>
                                    @endif
                                    @if($transaction->wallet?->wallet_address)
                                        <div>{{ __('admin.transactions.receiver_wallet') }}: <code dir="ltr" class="text-slate-700">{{ $transaction->wallet->wallet_address }}</code></div>
                                    @endif
                                    <div>{{ __('admin.transactions.transaction_id') }}: <code dir="ltr" class="text-slate-700">{{ $transaction->reference }}</code></div>
                                    <div>{{ __('admin.transactions.confirmations') }}: <strong class="text-slate-700">{{ $transaction->deposit->confirmations ?? 0 }}</strong></div>
                                    <div>{{ __('admin.transactions.deposit_status') }}: <strong class="text-slate-700">{{ $transaction->deposit->status }}</strong></div>
                                </div>
                            @endif
                        </td>
                        <td class="py-3">
                            @if($transaction->status !== 'cancelled')
                                <form method="POST" action="{{ route('admin.transactions.cancel', ['transaction' => $transaction->id]) }}">
                                    @csrf
                                    <button type="submit" class="rounded-lg bg-red-600 px-3 py-2 text-sm text-white">{{ __('admin.transactions.cancel_transaction') }}</button>
                                </form>
                            @else
                                <span class="text-sm text-slate-500">{{ __('admin.transactions.cancelled') }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="py-4 text-center text-slate-500">{{ __('admin.transactions.no_transactions') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $transactions->links() }}
    </div>
</div>
@endsection
