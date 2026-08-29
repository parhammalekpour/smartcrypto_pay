@extends('layouts.dashboard')

@section('title', __('merchant.settlements.page_title') . ' - CryptoPay')
@section('page-title', __('merchant.settlements.settled_settlements'))
@section('page-subtitle', __('merchant.settlements.page_subtitle'))

@section('content')

<!-- Auto-refresh script -->
<script>
    // Check for settlement updates every 3 seconds
    let lastSettlementCount = {{ $settlements->total() }};

    setInterval(() => {
        fetch(window.location.href, { headers: { 'X-Requested-With': 'fetch' } })
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const newDoc = parser.parseFromString(html, 'text/html');
                const newCount = newDoc.querySelectorAll('tbody tr[data-settlement-item]').length;

                if (newCount > lastSettlementCount) {
                    // New settlement received - reload page
                    window.location.reload();
                } else if (newCount !== lastSettlementCount) {
                    // Settlement count changed - reload page
                    window.location.reload();
                }
            })
            .catch(error => console.log('Auto-refresh check:', error));
    }, 3000); // Check every 3 seconds
</script>

<div class="bg-white rounded-lg shadow p-6">
    <!-- Header Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-green-50 rounded-lg p-4 border border-green-200">
            <p class="text-green-600 text-sm font-semibold">{{ __('merchant.settlements.settled_requests') }}</p>
            <p class="text-2xl font-bold text-green-800 mt-2">{{ $settlements->total() }}</p>
        </div>
        <div class="bg-emerald-50 rounded-lg p-4 border border-emerald-200">
            <p class="text-emerald-600 text-sm font-semibold">{{ __('merchant.settlements.total_settled_amount') }}</p>
            <p class="text-2xl font-bold text-emerald-800 mt-2">{{ \App\Support\NumberHelper::formatCryptoAmount($totalSettledAmount ?? 0) }}</p>
        </div>
        <div class="bg-teal-50 rounded-lg p-4 border border-teal-200">
            <p class="text-teal-600 text-sm font-semibold">{{ __('merchant.settlements.average_payment') }}</p>
            <p class="text-2xl font-bold text-teal-800 mt-2">{{ \App\Support\NumberHelper::formatCryptoAmount($averagePayment ?? 0) }}</p>
        </div>
    </div>

    <!-- Settlements Table -->
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700">{{ __('merchant.settlements.invoice_number') }}</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700">{{ __('merchant.settlements.recipient') }}</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700">{{ __('merchant.settlements.amount') }}</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700">{{ __('merchant.settlements.currency') }}</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700">{{ __('merchant.settlements.status') }}</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700">{{ __('merchant.settlements.settlement_date') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($settlements as $settlement)
                    <tr class="hover:bg-gray-50" data-settlement-item>
                        <td class="px-4 py-3">
                            <span class="font-semibold text-gray-800">{{ $settlement->invoice_number }}</span>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $settlement->recipient->name ?? __('merchant.settlements.unknown_recipient') }}</td>
                        <td class="px-4 py-3 text-gray-800 font-semibold">{{ \App\Support\NumberHelper::formatCryptoAmount($settlement->amount) }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                                {{ $settlement->currency }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                <i class="fas fa-check-circle"></i>{{ __('merchant.settlements.settled') }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $settlement->updated_at->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center">
                            <i class="fas fa-inbox text-4xl text-gray-300 mb-4"></i>
                            <p class="text-gray-500 mb-2">{{ __('merchant.settlements.no_settled_requests') }}</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $settlements->links() }}
    </div>
</div>
@endsection
