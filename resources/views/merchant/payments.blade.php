@extends('layouts.dashboard')

@section('title', __('merchant.payments.page_title') . ' - CryptoPay')
@section('page-title', __('merchant.payments.page_title'))
@section('page-subtitle', __('merchant.payments.page_subtitle'))

@section('content')

<!-- Create Payment Request Form -->
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-200">
        <div class="bg-indigo-100 p-2 rounded-lg">
            <i class="fas fa-plus-circle text-indigo-600"></i>
        </div>
        <h3 class="text-lg font-semibold text-gray-800">{{ __('merchant.payments.new_request') }}</h3>
    </div>

    @if(!auth()->user()->kyc_verified)
        <div class="mb-4 p-4 rounded-lg bg-yellow-50 border border-yellow-200 text-yellow-700 text-sm">
            {{ __('merchant.payments.kyc_notice') }}
        </div>
    @endif

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">
            ✅ {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
            @foreach($errors->all() as $error)
                ❌ {{ $error }}<br>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('payments.store') }}" class="space-y-4">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('merchant.payments.invoice_number') }}</label>
                <input type="text" name="invoice_number" required placeholder="INV-001"
                    value="{{ old('invoice_number', $nextInvoiceNumber ?? '') }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm text-gray-900">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('merchant.payments.recipient_username') }}</label>
                <select name="recipient_username" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm text-gray-900">
                    <option value="">{{ __('merchant.payments.select_customer') }}</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->user->name ?? $customer->name }}">{{ $customer->user->name ?? $customer->name }} — {{ $customer->email ?? '—' }} {{ $customer->phone ?? '' }}</option>
                    @endforeach
                </select>
                @if($customers->count())
                    <p class="text-xs text-gray-500 mt-2">{{ __('merchant.payments.customer_hint') }}</p>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('merchant.payments.amount') }}</label>
                <input type="number" step="0.00000001" name="amount" required placeholder="0.00"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm text-gray-900">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('merchant.payments.currency') }}</label>
                <select name="currency" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm text-gray-900">
                    <option value="">{{ __('merchant.payments.select_currency') }}</option>
                    <option value="BTC">Bitcoin (BTC)</option>
                    <option value="ETH">Ethereum (ETH)</option>
                    <option value="USDT">Tether (USDT)</option>
                </select>
            </div>
        </div>

        <button type="submit" class="w-full bg-indigo-600 text-white py-2 rounded-lg font-semibold hover:bg-indigo-700 transition" @if(!auth()->user()->kyc_verified) disabled @endif>
            <i class="fas fa-arrow-right ml-2"></i>{{ __('merchant.payments.create_request') }}
        </button>
    </form>
</div>

<!-- Filters & Search -->
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <form method="GET" action="{{ route('merchant.payments') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('merchant.payments.search') }}</label>
            <input type="text" name="search" placeholder="{{ __('merchant.payments.search_placeholder') }}"
                value="{{ request('search') }}"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-gray-900">
        </div>
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('merchant.payments.status_label') }}</label>
            <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-gray-900">
                <option value="">{{ __('merchant.payments.all') }}</option>
                <option value="pending" @if(request('status') === 'pending') selected @endif>{{ __('merchant.payments.status_pending') }}</option>
                <option value="paid" @if(request('status') === 'paid') selected @endif>{{ __('merchant.payments.status_paid') }}</option>
                <option value="cancelled" @if(request('status') === 'cancelled') selected @endif>{{ __('merchant.payments.status_cancelled') }}</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('merchant.payments.currency') }}</label>
            <select name="currency" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-gray-900">
                <option value="">{{ __('merchant.payments.all') }}</option>
                <option value="BTC" @if(request('currency') === 'BTC') selected @endif>Bitcoin</option>
                <option value="ETH" @if(request('currency') === 'ETH') selected @endif>Ethereum</option>
                <option value="USDT" @if(request('currency') === 'USDT') selected @endif>Tether</option>
            </select>
        </div>
        <div class="flex items-end">
            <button type="submit" class="w-full bg-indigo-600 text-white py-2 rounded-lg font-semibold hover:bg-indigo-700 transition">
                <i class="fas fa-search ml-2"></i>{{ __('merchant.payments.search') }}
            </button>
        </div>
    </form>
</div>

<!-- Quick Stats -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-6">
        <p class="text-gray-500 text-sm mb-2">{{ __('merchant.payments.total_requests') }}</p>
        <p class="text-3xl font-bold text-gray-800">{{ $payments->count() ?? 0 }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <p class="text-gray-500 text-sm mb-2">{{ __('merchant.payments.status_pending') }}</p>
        <p class="text-3xl font-bold text-yellow-600">{{ $pendingCount ?? 0 }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <p class="text-gray-500 text-sm mb-2">{{ __('merchant.payments.status_paid') }}</p>
        <p class="text-3xl font-bold text-green-600">{{ $paidCount ?? 0 }}</p>
    </div>
</div>

<!-- Payments Table -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="p-6 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-800">{{ __('merchant.payments.list_title') }}</h3>
    </div>

    @if($payments && $payments->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-right font-semibold text-gray-700 text-sm">{{ __('merchant.payments.invoice_number') }}</th>
                        <th class="px-6 py-3 text-right font-semibold text-gray-700 text-sm">{{ __('merchant.payments.recipient') }}</th>
                        <th class="px-6 py-3 text-right font-semibold text-gray-700 text-sm">{{ __('merchant.payments.amount') }}</th>
                        <th class="px-6 py-3 text-right font-semibold text-gray-700 text-sm">{{ __('merchant.payments.currency') }}</th>
                        <th class="px-6 py-3 text-right font-semibold text-gray-700 text-sm">{{ __('merchant.payments.status_label') }}</th>
                        <th class="px-6 py-3 text-right font-semibold text-gray-700 text-sm">{{ __('merchant.payments.date') }}</th>
                        <th class="px-6 py-3 text-right font-semibold text-gray-700 text-sm">{{ __('merchant.payments.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($payments as $payment)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <span class="font-semibold text-gray-800">{{ $payment->invoice_number }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-gray-800">{{ $payment->recipient->name ?? __('merchant.payments.unknown_recipient') }}</span>
                            </td>
                            <td class="px-6 py-4 font-semibold text-gray-800">
                                {{ \App\Support\NumberHelper::formatCryptoAmount($payment->amount) }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold
                                    @if($payment->currency === 'BTC') bg-orange-100 text-orange-800
                                    @elseif($payment->currency === 'ETH') bg-gray-100 text-gray-800
                                    @else bg-teal-100 text-teal-800 @endif">
                                    {{ $payment->currency }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @if($payment->status === 'pending')
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">
                                        <i class="fas fa-hourglass-end"></i>{{ __('merchant.payments.status_pending') }}
                                    </span>
                                @elseif($payment->status === 'paid')
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                        <i class="fas fa-check-circle"></i>{{ __('merchant.payments.status_paid') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                                        <i class="fas fa-times-circle"></i>{{ __('merchant.payments.status_cancelled') }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-gray-600 text-sm">
                                {{ $payment->created_at->format('Y/m/d H:i') }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <a href="{{ url('/pay/' . $payment->token) }}" target="_blank"
                                        class="text-indigo-600 hover:underline text-xs font-semibold">
                                        {{ __('merchant.payments.view') }}
                                    </a>
                                    @if($payment->status === 'pending')
                                        <form method="POST" action="{{ route('payments.cancel', $payment->id) }}" class="inline" onsubmit="return confirm('{{ __('merchant.payments.confirm_cancel') }}')">
                                            @csrf
                                            <button type="submit" class="text-red-600 hover:underline text-xs font-semibold">
                                                {{ __('merchant.payments.cancel') }}
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-200">
            <div class="flex items-center justify-between">
                <p class="text-sm text-gray-600">{{ __('merchant.payments.showing_requests', ['count' => $payments->count(), 'total' => $payments->total() ?? $payments->count()]) }}</p>
                <div class="flex gap-2">
                    <button class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-semibold text-gray-900 hover:bg-gray-50">{{ __('merchant.payments.previous') }}</button>
                    <button class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-semibold text-gray-900 hover:bg-gray-50">{{ __('merchant.payments.next') }}</button>
                </div>
            </div>
        </div>
    @else
        <div class="p-12 text-center">
            <i class="fas fa-inbox text-5xl text-gray-300 mb-4"></i>
            <p class="text-gray-500 mb-3">{{ __('merchant.payments.no_requests') }}</p>
            <a href="{{ route('merchant.dashboard') }}" class="text-indigo-600 font-semibold hover:underline">
                {{ __('merchant.payments.back_to_dashboard') }}
            </a>
        </div>
    @endif
</div>

@endsection
