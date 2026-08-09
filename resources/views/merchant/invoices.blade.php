@extends('layouts.dashboard')

@section('title', __('merchant.invoices.page_title') . ' - CryptoPay')
@section('page-title', __('merchant.invoices.pending_invoices'))
@section('page-subtitle', __('merchant.invoices.page_subtitle'))

@section('content')

<!-- Auto-refresh script -->
<script>
    // Check for invoice updates every 3 seconds
    let lastInvoiceCount = {{ $invoices->total() }};

    setInterval(() => {
        fetch(window.location.href, { headers: { 'X-Requested-With': 'fetch' } })
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const newDoc = parser.parseFromString(html, 'text/html');
                const newCount = newDoc.querySelectorAll('tbody tr[data-invoice-item]').length;

                if (newCount < lastInvoiceCount) {
                    // Invoice was paid - reload page
                    window.location.reload();
                } else if (newCount !== lastInvoiceCount) {
                    // Invoice count changed - reload page
                    window.location.reload();
                }
            })
            .catch(error => console.log('Auto-refresh check:', error));
    }, 3000); // Check every 3 seconds
</script>

<div class="bg-white rounded-lg shadow p-6">
    <!-- Header Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-yellow-50 rounded-lg p-4 border border-yellow-200">
            <p class="text-yellow-600 text-sm font-semibold">{{ __('merchant.invoices.pending_requests') }}</p>
            <p class="text-2xl font-bold text-yellow-800 mt-2">{{ $invoices->total() }}</p>
        </div>
        <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
            <p class="text-blue-600 text-sm font-semibold">{{ __('merchant.invoices.total_pending_amount') }}</p>
            <p class="text-2xl font-bold text-blue-800 mt-2">${{ number_format($invoices->sum('amount'), 2) }}</p>
        </div>
        <div class="bg-indigo-50 rounded-lg p-4 border border-indigo-200">
            <p class="text-indigo-600 text-sm font-semibold">{{ __('merchant.invoices.required_action') }}</p>
            <p class="text-2xl font-bold text-indigo-800 mt-2">{{ $invoices->total() }}</p>
        </div>
    </div>

    <!-- Invoices Table -->
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700">{{ __('merchant.invoices.invoice_number') }}</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700">{{ __('merchant.invoices.recipient') }}</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700">{{ __('merchant.invoices.amount') }}</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700">{{ __('merchant.invoices.currency') }}</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700">{{ __('merchant.invoices.date') }}</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700">{{ __('merchant.invoices.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($invoices as $invoice)
                    <tr class="hover:bg-gray-50" data-invoice-item>
                        <td class="px-4 py-3">
                            <span class="font-semibold text-gray-800">{{ $invoice->invoice_number }}</span>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $invoice->recipient->name ?? __('merchant.invoices.unknown_recipient') }}</td>
                        <td class="px-4 py-3 text-gray-800 font-semibold">{{ \App\Support\NumberHelper::formatCryptoAmount($invoice->amount) }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                                {{ $invoice->currency }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $invoice->created_at->diffForHumans() }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ url('/pay/' . $invoice->token) }}" target="_blank" class="text-indigo-600 hover:underline font-semibold text-xs">
                                <i class="fas fa-external-link-alt ml-1"></i>{{ __('merchant.invoices.send_link') }}
                            </a>

                            @if($invoice->status === 'pending')
                                <form action="{{ route('payments.cancel', $invoice->id) }}" method="POST" class="inline-block ml-2" onsubmit="return confirm('{{ __('merchant.invoices.confirm_delete') }}');">
                                    @csrf
                                    <button type="submit" class="text-red-600 hover:underline font-semibold text-xs">
                                        <i class="fas fa-trash ml-1"></i>{{ __('merchant.invoices.delete') }}
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center">
                            <i class="fas fa-inbox text-4xl text-gray-300 mb-4"></i>
                            <p class="text-gray-500 mb-2">{{ __('merchant.invoices.no_pending_invoices') }}</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $invoices->links() }}
    </div>
</div>
@endsection
