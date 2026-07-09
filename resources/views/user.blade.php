@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="text-white">
        <h1 class="text-3xl font-bold mb-8">User Panel</h1>
        
        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-gradient-to-br from-purple-500 to-purple-700 p-6 rounded-lg shadow-lg">
                <div class="text-sm font-semibold text-purple-200 mb-2">Total Balance</div>
                <div class="text-2xl font-bold">$12,450.50</div>
            </div>
            <div class="bg-gradient-to-br from-blue-500 to-blue-700 p-6 rounded-lg shadow-lg">
                <div class="text-sm font-semibold text-blue-200 mb-2">Total Transactions</div>
                <div class="text-2xl font-bold">124</div>
            </div>
            <div class="bg-gradient-to-br from-green-500 to-green-700 p-6 rounded-lg shadow-lg">
                <div class="text-sm font-semibold text-green-200 mb-2">This Month</div>
                <div class="text-2xl font-bold">$2,340.25</div>
            </div>
        </div>

        <!-- Wallets Section -->
        <div class="bg-gray-800 rounded-lg p-6 mb-8 shadow-lg">
            <h2 class="text-xl font-bold mb-6 text-white">Wallets</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-gray-700 p-4 rounded-lg">
                    <div class="text-sm text-gray-300 mb-2">BTC Wallet</div>
                    <div class="text-lg font-bold text-yellow-400">0.125 BTC</div>
                </div>
                <div class="bg-gray-700 p-4 rounded-lg">
                    <div class="text-sm text-gray-300 mb-2">ETH Wallet</div>
                    <div class="text-lg font-bold text-blue-400">2.5 ETH</div>
                </div>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="bg-gray-800 rounded-lg p-6 shadow-lg">
            <h2 class="text-xl font-bold mb-6 text-white">Recent Transactions</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-gray-700">
                            <th class="pb-3 text-gray-300">Date</th>
                            <th class="pb-3 text-gray-300">Type</th>
                            <th class="pb-3 text-gray-300">Amount</th>
                            <th class="pb-3 text-gray-300">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-b border-gray-700 hover:bg-gray-700">
                            <td class="py-3">2024-01-15</td>
                            <td class="py-3">Deposit</td>
                            <td class="py-3 text-green-400">+$500</td>
                            <td class="py-3"><span class="bg-green-500 text-white px-3 py-1 rounded text-sm">Complete</span></td>
                        </tr>
                        <tr class="border-b border-gray-700 hover:bg-gray-700">
                            <td class="py-3">2024-01-14</td>
                            <td class="py-3">Withdrawal</td>
                            <td class="py-3 text-red-400">-$250</td>
                            <td class="py-3"><span class="bg-green-500 text-white px-3 py-1 rounded text-sm">Complete</span></td>
                        </tr>
                        <tr class="border-b border-gray-700 hover:bg-gray-700">
                            <td class="py-3">2024-01-13</td>
                            <td class="py-3">Transfer</td>
                            <td class="py-3 text-blue-400">+$1000</td>
                            <td class="py-3"><span class="bg-yellow-500 text-white px-3 py-1 rounded text-sm">Pending</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
