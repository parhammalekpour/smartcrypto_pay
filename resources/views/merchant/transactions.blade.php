@extends('layouts.dashboard')

@section('title', __('merchant.transactions.page_title') . ' - CryptoPay')
@section('page-title', __('merchant.transactions.page_title'))
@section('page-subtitle', __('merchant.transactions.page_subtitle'))

@section('content')

<!-- Stats -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow p-6">
        <p class="text-gray-500 text-sm mb-2">{{ __('merchant.transactions.total_transactions') }}</p>
        <p class="text-3xl font-bold text-gray-800">{{ $totalCount }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <p class="text-gray-500 text-sm mb-2">{{ __('merchant.transactions.completed') }}</p>
        <p class="text-3xl font-bold text-green-600">{{ $completedCount }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <p class="text-gray-500 text-sm mb-2">{{ __('merchant.transactions.pending') }}</p>
        <p class="text-3xl font-bold text-yellow-600">{{ $pendingCount }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <p class="text-gray-500 text-sm mb-2">{{ __('merchant.transactions.failed') }}</p>
        <p class="text-3xl font-bold text-red-600">{{ $failedCount }}</p>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <form method="GET" action="{{ route('merchant.transactions') }}" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('merchant.transactions.search') }}</label>
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="{{ __('merchant.transactions.search_placeholder') }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-gray-900">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('merchant.transactions.type') }}</label>
                <select name="type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <option value="">{{ __('merchant.transactions.all') }}</option>
                    <option value="deposit" {{ request('type') === 'deposit' ? 'selected' : '' }}>{{ __('merchant.transactions.deposit') }}</option>
                    <option value="transfer" {{ request('type') === 'transfer' ? 'selected' : '' }}>{{ __('merchant.transactions.transfer') }}</option>
                    <option value="withdrawal" {{ request('type') === 'withdrawal' ? 'selected' : '' }}>{{ __('merchant.transactions.withdrawal') }}</option>
                    <option value="payment" {{ request('type') === 'payment' ? 'selected' : '' }}>{{ __('merchant.transactions.payment') }}</option>
                    <option value="invoice" {{ request('type') === 'invoice' ? 'selected' : '' }}>{{ __('merchant.transactions.invoice') }}</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('merchant.transactions.status_label') }}</label>
                <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <option value="">{{ __('merchant.transactions.all') }}</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>{{ __('merchant.transactions.completed') }}</option>
                    <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>{{ __('merchant.transactions.paid') }}</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>{{ __('merchant.transactions.pending') }}</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>{{ __('merchant.transactions.failed') }}</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>{{ __('merchant.transactions.cancelled') }}</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 bg-indigo-600 text-white py-2 rounded-lg font-semibold hover:bg-indigo-700 transition">
                    <i class="fas fa-search ml-2"></i>{{ __('merchant.transactions.search') }}
                </button>
                <a href="{{ route('merchant.transactions') }}" class="flex-1 bg-gray-200 text-gray-800 py-2 rounded-lg font-semibold hover:bg-gray-300 transition text-center">
                    <i class="fas fa-times ml-2"></i>{{ __('merchant.transactions.clear') }}
                </a>
            </div>
        </div>
    </form>
</div>

<!-- Transactions & Payments Table -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="p-6 border-b border-gray-200 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <h3 class="text-lg font-semibold text-gray-800">{{ __('merchant.transactions.transactions_and_invoices') }}</h3>
            <a href="{{ route('merchant.transactions.export') }}?{{ request()->getQueryString() }}" class="text-sm bg-gray-100 px-3 py-1 rounded-lg text-gray-700 hover:bg-gray-200">
                <i class="fas fa-file-csv ml-2"></i>{{ __('merchant.transactions.export_csv') }}
            </a>
        </div>
        <span class="text-sm text-gray-600">{{ __('merchant.transactions.showing_items', ['count' => ($transactions->count() + $paymentRequests->count()), 'total' => $transactions->total() + $paymentRequests->count()]) }}</span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-right font-semibold text-gray-700 text-sm">{{ __('merchant.transactions.id') }}</th>
                    <th class="px-6 py-3 text-right font-semibold text-gray-700 text-sm">{{ __('merchant.transactions.type') }}</th>
                    <th class="px-6 py-3 text-right font-semibold text-gray-700 text-sm">{{ __('merchant.transactions.customer_source') }}</th>
                    <th class="px-6 py-3 text-right font-semibold text-gray-700 text-sm">{{ __('merchant.transactions.description') }}</th>
                    <th class="px-6 py-3 text-right font-semibold text-gray-700 text-sm">{{ __('merchant.transactions.amount') }}</th>
                    <th class="px-6 py-3 text-right font-semibold text-gray-700 text-sm">{{ __('merchant.transactions.status_label') }}</th>
                    <th class="px-6 py-3 text-right font-semibold text-gray-700 text-sm">{{ __('merchant.transactions.date') }}</th>
                    <th class="px-6 py-3 text-right font-semibold text-gray-700 text-sm">{{ __('merchant.transactions.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <!-- Transactions -->
                @forelse($transactions as $transaction)
                    <tr class="hover:bg-gray-50 cursor-pointer" onclick="viewTransactionDetail('{{ $transaction->id }}', 'transaction')">
                        <td class="px-6 py-4" dir="ltr">
                            <code class="bg-gray-100 px-2 py-1 rounded text-xs">{{ substr($transaction->reference ?? 'TRX-' . $transaction->id, 0, 20) }}</code>
                        </td>
                        <td class="px-6 py-4">
                            @if($transaction->type === 'deposit')
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                    <i class="fas fa-arrow-down ml-1"></i>{{ __('merchant.transactions.deposit') }}
                                </span>
                            @elseif($transaction->type === 'transfer')
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                                    <i class="fas fa-arrow-right ml-1"></i>{{ __('merchant.transactions.transfer') }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                                    <i class="fas fa-arrow-up ml-1"></i>{{ __('merchant.transactions.withdrawal') }}
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($transaction->type === 'deposit')
                                <span class="text-gray-700 font-semibold">{{ $transaction->sender->name ?? __('merchant.transactions.system') }}</span>
                            @else
                                <span class="text-gray-600">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-gray-600 text-sm">
                            {{ $transaction->description ?? '-' }}
                            @if($transaction->type === 'deposit')
                                <div class="mt-2 text-xs text-gray-500 space-y-1">
                                    @if($transaction->sender_wallet_address)
                                        <div>{{ __('merchant.transactions.sender') }}: <code dir="ltr" class="text-gray-700">{{ $transaction->sender_wallet_address }}</code></div>
                                    @endif
                                    @if($transaction->wallet?->wallet_address)
                                        <div>{{ __('merchant.transactions.recipient') }}: <code dir="ltr" class="text-gray-700">{{ $transaction->wallet->wallet_address }}</code></div>
                                    @endif
                                    <div>{{ __('merchant.transactions.transaction_id') }}: <code dir="ltr" class="text-gray-700">{{ $transaction->reference }}</code></div>
                                    @if($transaction->deposit)
                                        <div>{{ __('merchant.transactions.confirmations') }}: <strong class="text-gray-700">{{ $transaction->deposit->confirmations ?? 0 }}</strong></div>
                                        <div>{{ __('merchant.transactions.deposit_status') }}: <strong class="text-gray-700">{{ $transaction->deposit->status }}</strong></div>
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 font-semibold {{ $transaction->type === 'deposit' ? 'text-green-600' : 'text-red-600' }}">
                            {{ $transaction->type === 'deposit' ? '+' : '-' }}{{ \App\Support\NumberHelper::formatCryptoAmount($transaction->amount) }} {{ $transaction->currency }}
                        </td>
                        <td class="px-6 py-4">
                            @if($transaction->status === 'completed')
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                    ✓ {{ __('merchant.transactions.completed') }}
                                </span>
                            @elseif($transaction->status === 'pending')
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">
                                    ⏱ {{ __('merchant.transactions.pending') }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                                    ✗ {{ __('merchant.transactions.failed') }}
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-gray-600 text-sm">{{ $transaction->created_at->format('Y/m/d H:i') }}</td>
                        <td class="px-6 py-4">
                            <button onclick="event.stopPropagation(); viewTransactionDetail('{{ $transaction->id }}', 'transaction')"
                                class="text-indigo-600 hover:text-indigo-800 font-semibold">
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                @endforelse

                <!-- Payment Requests -->
                @forelse($paymentRequests as $payment)
                    <tr class="hover:bg-gray-50 cursor-pointer" onclick="viewTransactionDetail('{{ $payment->id }}', 'payment')">
                        <td class="px-6 py-4" dir="ltr">
                            <code class="bg-gray-100 px-2 py-1 rounded text-xs">{{ substr($payment->invoice_number ?? 'INV-' . $payment->id, 0, 20) }}</code>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-800">
                                <i class="fas fa-file-invoice ml-1"></i>{{ __('merchant.transactions.invoice') }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-gray-700 font-semibold">{{ $payment->recipient->name ?? __('merchant.transactions.unknown') }}</span>
                        </td>
                        <td class="px-6 py-4 text-gray-600 text-sm">{{ __('merchant.transactions.invoice_number_prefix') }} #{{ $payment->invoice_number }}</td>
                        <td class="px-6 py-4 font-semibold text-blue-600">{{ \App\Support\NumberHelper::formatCryptoAmount($payment->amount) }} {{ $payment->currency }}</td>
                        <td class="px-6 py-4">
                            @if($payment->status === 'paid')
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                    ✓ {{ __('merchant.transactions.paid') }}
                                </span>
                            @elseif($payment->status === 'pending')
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">
                                    ⏱ {{ __('merchant.transactions.pending') }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                                    ✗ {{ __('merchant.transactions.cancelled') }}
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-gray-600 text-sm">{{ $payment->created_at->format('Y/m/d H:i') }}</td>
                        <td class="px-6 py-4">
                            <button onclick="event.stopPropagation(); viewTransactionDetail('{{ $payment->id }}', 'payment')"
                                class="text-indigo-600 hover:text-indigo-800 font-semibold">
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                @endforelse

                @if($transactions->count() === 0 && $paymentRequests->count() === 0)
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-inbox text-4xl text-gray-300 mb-4 block"></i>
                            {{ __('merchant.transactions.no_transactions') }}
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($transactions->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $transactions->links() }}
        </div>
    @endif
</div>

<!-- Transaction Detail Modal -->
<div id="detailModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg p-8 w-full max-w-2xl mx-4 max-h-screen overflow-y-auto">
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

@push('scripts')
<script>
    let currentDetail = null;

    function viewTransactionDetail(id, type) {
        currentDetail = { id, type };
        const modal = document.getElementById('detailModal');
        const content = document.getElementById('modalContent');

        // Get the row data
        const row = event.target.closest('tr');
        const cells = row.querySelectorAll('td');

        let html = `
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-gray-500 text-sm">{{ __('merchant.transactions.id') }}</p>
                        <p class="font-semibold text-gray-800">${cells[0].textContent.trim()}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">{{ __('merchant.transactions.type') }}</p>
                        <p class="font-semibold text-gray-800">${cells[1].textContent.trim()}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">{{ __('merchant.transactions.description') }}</p>
                        <p class="font-semibold text-gray-800">${cells[2].textContent.trim()}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">{{ __('merchant.transactions.amount') }}</p>
                        <p class="font-semibold text-gray-800">${cells[3].textContent.trim()}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">{{ __('merchant.transactions.status_label') }}</p>
                        <p class="font-semibold text-gray-800">${cells[4].textContent.trim()}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">{{ __('merchant.transactions.date') }}</p>
                        <p class="font-semibold text-gray-800">${cells[5].textContent.trim()}</p>
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
        // If it's a payment (invoice) open invoice download, otherwise transaction download
        if (currentDetail.type === 'payment') {
            window.location = '/merchant/invoices/' + encodeURIComponent(currentDetail.id) + '/download';
        } else {
            window.location = '/merchant/transactions/' + encodeURIComponent(currentDetail.id) + '/download';
        }
    }
</script>
@endpush

@endsection
