@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">📤 Create Payment Request</h1>

    @if(session('success'))
        <div class="mb-4 px-4 py-3 rounded text-sm bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">
            ✅ {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 px-4 py-3 rounded text-sm bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200">
            @foreach($errors->all() as $error)
                ❌ {{ $error }}<br>
            @endforeach
        </div>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 mb-8 shadow">
        <form method="POST" action="{{ route('payments.store') }}">
            @csrf

            <div class="mb-4">
                <label class="block mb-2 font-medium text-gray-700 dark:text-gray-300">Invoice Number</label>
                <input type="text" name="invoice_number" required placeholder="e.g., INV-001" 
                    class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100">
            </div>

            <div class="mb-4">
                <label class="block mb-2 font-medium text-gray-700 dark:text-gray-300">Recipient Username</label>
                <input type="text" name="recipient_username" required placeholder="Enter customer username" 
                    class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block mb-2 font-medium text-gray-700 dark:text-gray-300">Amount</label>
                    <input type="number" step="0.00000001" name="amount" required placeholder="0.00" 
                        class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100">
                </div>

                <div>
                    <label class="block mb-2 font-medium text-gray-700 dark:text-gray-300">Currency</label>
                    <select name="currency" required class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 rounded bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100">
                        <option value="">Select Currency</option>
                        <option value="BTC">Bitcoin (BTC)</option>
                        <option value="ETH">Ethereum (ETH)</option>
                        <option value="USDT">Tether (USDT)</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white px-4 py-2 rounded font-semibold">
                Create Link
            </button>
        </form>
    </div>

    <h2 class="text-xl font-bold mb-4 text-gray-900 dark:text-white">📋 Payment Requests</h2>

    @if($payments->count() > 0)
        <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-lg shadow">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-700">
                        <th class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">Invoice</th>
                        <th class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">Recipient</th>
                        <th class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">Amount</th>
                        <th class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">Currency</th>
                        <th class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">Status</th>
                        <th class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">Link</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payments as $payment)
                        <tr class="border-b border-gray-100 dark:border-gray-700">
                            <td class="px-4 py-3 text-gray-800 dark:text-gray-100">{{ $payment->invoice_number }}</td>
                            <td class="px-4 py-3 text-gray-800 dark:text-gray-100">{{ $payment->recipient->name ?? 'Unknown' }}</td>
                            <td class="px-4 py-3 text-gray-800 dark:text-gray-100">{{ number_format($payment->amount, 8) }}</td>
                            <td class="px-4 py-3 text-gray-800 dark:text-gray-100">{{ $payment->currency }}</td>
                            <td class="px-4 py-3">
                                @if($payment->status === 'paid')
                                    <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold bg-green-500 dark:bg-green-600 text-white">Paid</span>
                                @else
                                    <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold bg-yellow-400 dark:bg-yellow-600 text-white">{{ ucfirst($payment->status) }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ url('/pay/' . $payment->token) }}" target="_blank" class="text-indigo-600 dark:text-indigo-400 font-semibold">View →</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="text-center py-10 text-gray-500 dark:text-gray-400">
            <p>No payment requests created yet</p>
        </div>
    @endif
</div>
@endsection
