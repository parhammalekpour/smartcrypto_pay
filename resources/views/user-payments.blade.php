@php
use Morilog\Jalali\Jalalian;
@endphp

@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">📭 Payment Requests</h1>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        @if($payments->count() > 0)
            <h2 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-100">Pending Payments</h2>

            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-700">
                            <th class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">Invoice</th>
                            <th class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">From Merchant</th>
                            <th class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">Amount</th>
                            <th class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">Currency</th>
                            <th class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">Status</th>
                            <th class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payments as $payment)
                            <tr class="border-b border-gray-100 dark:border-gray-700">
                                <td class="px-4 py-3 text-gray-800 dark:text-gray-100"><strong>{{ $payment->invoice_number }}</strong></td>
                                <td class="px-4 py-3 text-gray-800 dark:text-gray-100">{{ $payment->merchant->name }}</td>
                                <td class="px-4 py-3 text-gray-800 dark:text-gray-100">{{ \App\Support\NumberHelper::formatCryptoAmount($payment->amount) }}</td>
                                <td class="px-4 py-3 text-gray-800 dark:text-gray-100">{{ $payment->currency }}</td>
                                <td class="px-4 py-3">
                                    @if($payment->status === 'paid')
                                        <span class="inline-block px-3 py-1 rounded text-sm font-semibold bg-green-500 dark:bg-green-600 text-white">Paid</span>
                                    @else
                                        <span class="inline-block px-3 py-1 rounded text-sm font-semibold bg-yellow-400 dark:bg-yellow-600 text-white">{{ ucfirst($payment->status) }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if($payment->status === 'pending')
                                        <a href="{{ url('/pay/' . $payment->token) }}" class="text-indigo-600 dark:text-indigo-400 font-semibold">Pay Now →</a>
                                    @else
                                        <span class="text-gray-500 dark:text-gray-400">Completed</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-12">
                <p class="text-lg text-gray-600 dark:text-gray-400">📭 No pending payment requests</p>
                <p class="text-gray-500 dark:text-gray-400 mt-2">Merchants will send you payment requests here</p>
            </div>
        @endif
    </div>
</div>
@endsection
