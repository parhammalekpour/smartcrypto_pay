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
                    class="w-full px-3 py-1.5 border border-gray-300 rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-gray-900 text-sm">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('merchant.transactions.type') }}</label>
                <select name="type" class="w-full px-3 py-1.5 border border-gray-300 rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm">
                    <option value="">{{ __('merchant.transactions.all') }}</option>
                    <option value="deposit" {{ request('type') === 'deposit' ? 'selected' : '' }}>{{ __('merchant.transactions.deposit') }}</option>
                    <option value="transfer" {{ request('type') === 'transfer' ? 'selected' : '' }}>{{ __('merchant.transactions.transfer') }}</option>
                    <option value="withdraw" {{ request('type') === 'withdraw' ? 'selected' : '' }}>{{ __('merchant.transactions.withdrawal') }}</option>
                    <option value="payment" {{ request('type') === 'payment' ? 'selected' : '' }}>{{ __('merchant.transactions.payment') }}</option>
                    <option value="invoice" {{ request('type') === 'invoice' ? 'selected' : '' }}>{{ __('merchant.transactions.invoice') }}</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('merchant.transactions.status_label') }}</label>
                <select name="status" class="w-full px-3 py-1.5 border border-gray-300 rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm">
                    <option value="">{{ __('merchant.transactions.all') }}</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>{{ __('merchant.transactions.completed') }}</option>
                    <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>{{ __('merchant.transactions.paid') }}</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>{{ __('merchant.transactions.pending') }}</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>{{ __('merchant.transactions.failed') }}</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>{{ __('merchant.transactions.cancelled') }}</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                            <button type="submit" class="flex-1 bg-indigo-600 text-white py-1.5 rounded-md text-sm font-semibold hover:bg-indigo-700 transition px-3">
                    <i class="fas fa-search ml-2"></i>{{ __('merchant.transactions.search') }}
                </button>
                            <a href="{{ route('merchant.transactions') }}" class="flex-1 bg-gray-200 text-gray-800 py-1.5 rounded-md text-sm font-semibold hover:bg-gray-300 transition text-center px-3">
                    <i class="fas fa-times ml-2"></i>{{ __('merchant.transactions.clear') }}
                </a>
            </div>
        </div>
    </form>
</div>

<!-- Transactions & Payments Table -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="p-4 border-b border-gray-200 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <h3 class="text-lg font-semibold text-gray-800">{{ __('merchant.transactions.transactions_and_invoices') }}</h3>
            <a href="{{ route('merchant.transactions.export') }}?{{ request()->getQueryString() }}" class="text-sm bg-gray-100 px-3 py-1 rounded-lg text-gray-700 hover:bg-gray-200">
                <i class="fas fa-file-csv ml-2"></i>{{ __('merchant.transactions.export_csv') }}
            </a>
        </div>
        <span class="text-sm text-gray-600">{{ __('merchant.transactions.showing_items', ['count' => ($transactions->count() + $paymentRequests->count()), 'total' => $transactions->total() + $paymentRequests->count()]) }}</span>
    </div>

    @php
        $network = strtolower((string)env('ETHEREUM_NETWORK', 'sepolia'));
        $explorerBase = $network === 'mainnet' ? 'https://etherscan.io/tx/' : ($network === 'sepolia' ? 'https://sepolia.etherscan.io/tx/' : 'https://etherscan.io/tx/');
    @endphp

    <div class="overflow-x-auto">
        <table class="w-full min-w-full text-sm">
            <colgroup>
                <col style="width:14%" /> <!-- ID -->
                <col style="width:8%" />  <!-- Currency -->
                <col style="width:12%" /> <!-- Type -->
                <col style="width:18%" /> <!-- Amount -->
                <col style="width:12%" /> <!-- Status -->
                <col style="width:18%" /> <!-- Tx Hash -->
                <col style="width:12%" /> <!-- Date -->
                <col style="width:6%" />  <!-- Action -->
            </colgroup>
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-3 py-2 text-right font-semibold text-gray-700 text-xs">{{ __('merchant.transactions.id') }}</th>
                    <th class="px-3 py-2 text-right font-semibold text-gray-700 text-xs">{{ __('merchant.transactions.currency') }}</th>
                    <th class="px-3 py-2 text-right font-semibold text-gray-700 text-xs">{{ __('merchant.transactions.type') }}</th>
                    <th class="px-3 py-2 text-right font-semibold text-gray-700 text-xs">{{ __('merchant.transactions.amount') }}</th>
                    <th class="px-3 py-2 text-right font-semibold text-gray-700 text-xs">{{ __('merchant.transactions.status_label') }}</th>
                    <th class="px-3 py-2 text-right font-semibold text-gray-700 text-xs">{{ __('merchant.transactions.tx_hash') }}</th>
                    <th class="px-3 py-2 text-right font-semibold text-gray-700 text-xs">{{ __('merchant.transactions.date') }}</th>
                    <th class="px-3 py-2 text-right font-semibold text-gray-700 text-xs">{{ __('merchant.transactions.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <!-- Transactions -->
                @forelse($transactions as $transaction)
                    <?php $refTemp = $transaction->reference ?? ('TRX-' . $transaction->id); $refTemp = preg_replace('/^(INV-)+/i', 'INV-', $refTemp); ?>
                    <tr id="merchant-transaction-row-{{ $transaction->id }}"
                        data-transaction-id="{{ $transaction->id }}"
                        data-transaction-status="{{ $transaction->status }}"
                                            data-updated-at="{{ $transaction->updated_at?->toDateTimeString() }}"
                                            data-customer="{{ e($transaction->sender->name ?? $transaction->recipient->name ?? $transaction->sender_wallet_address ?? '') }}"
                                            data-description="{{ e($transaction->description ?? '') }}"
                                            data-reference="{{ e($refTemp) }}"
                                            data-amount="{{ \App\Support\NumberHelper::formatCryptoAmount($transaction->amount) }}"
                                            data-currency="{{ e($transaction->currency) }}"
                                            data-txhash="{{ e($transaction->tx_hash) }}"
                                            data-date="{{ $transaction->created_at->format('Y/m/d H:i') }}"
                                            class="hover:bg-gray-50 cursor-pointer" onclick="viewTransactionDetail('{{ $transaction->id }}', 'transaction', event)">

                        <!-- ID -->
                        <td class="px-3 py-2" dir="ltr">
                            <code class="bg-gray-100 text-black dark:text-black px-2 py-1 rounded text-xs">{{ substr($refTemp, 0, 18) }}</code>
                        </td>

                        <!-- Currency (compact) -->
                        <td class="px-3 py-2 text-gray-700 font-semibold text-xs">{{ $transaction->currency }}</td>

                        <!-- Type (compact badge) -->
                        <td class="px-3 py-2">
                            @php
                                $typeLabel = ucfirst($transaction->type ?? '');
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $transaction->type === 'deposit' ? 'bg-green-100 text-green-800' : ($transaction->type === 'withdraw' ? 'bg-red-100 text-red-800' : ($transaction->type === 'payment' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-700')) }}">
                                @if($transaction->type === 'deposit') <i class="fas fa-arrow-down ml-1 text-[10px]"></i>@elseif($transaction->type === 'transfer') <i class="fas fa-arrow-right ml-1 text-[10px]"></i>@elseif($transaction->type === 'withdraw') <i class="fas fa-arrow-up ml-1 text-[10px]"></i>@elseif($transaction->type === 'payment') <i class="fas fa-file-invoice ml-1 text-[10px]"></i>@endif
                                <span class="ml-1">{{ __("merchant.transactions.".$transaction->type) ?: $typeLabel }}</span>
                            </span>
                        </td>

                        <!-- Amount (compact: amount + currency) -->
                        <td class="px-3 py-2 font-semibold text-xs">
                            {{ ($transaction->type === 'deposit' ? '+' : '-') }}{{ \App\Support\NumberHelper::formatCryptoAmount($transaction->amount) }} <span class="text-gray-500">{{ $transaction->currency }}</span>
                        </td>

                        <!-- Status (compact badge) -->
                        <td class="px-3 py-2">
                            @php $transactionStatus = strtolower((string)$transaction->status); @endphp
                            @if(in_array($transactionStatus, ['completed','confirmed']))
                                <span class="tx-status-cell inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">✓ {{ __('merchant.transactions.completed') }}</span>
                            @elseif(in_array($transactionStatus, ['pending','processing']))
                                <span class="tx-status-cell inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">⏱ {{ __('merchant.transactions.pending') }}</span>
                            @else
                                <span class="tx-status-cell inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">✗ {{ __('merchant.transactions.failed') }}</span>
                            @endif
                        </td>

                        <!-- Tx Hash (short, with copy and tooltip) -->
                        <td class="px-3 py-2 text-xs text-gray-700">
                            @php $txHash = $transaction->tx_hash; @endphp
                            @if(!$txHash && in_array(strtolower($transaction->status), ['processing','pending']))
                                <span class="tx-hash text-gray-500">{{ __('merchant.transactions.waiting_for_broadcast') }}</span>
                            @elseif($txHash)
                                <div class="flex items-center gap-2">
                                    <a href="{{ $explorerBase }}{{ $txHash }}" target="_blank" rel="noopener noreferrer" class="tx-hash truncate" title="{{ $txHash }}">{{ strlen($txHash) > 16 ? substr($txHash, 0, 10) . '...' . substr($txHash, -6) : $txHash }}</a>
                                    <button type="button" class="copy-hash-btn text-gray-500 hover:text-gray-700 p-1" data-hash="{{ $txHash }}" title="{{ __('merchant.transactions.copy') }}">
                                        <i class="far fa-copy text-xs"></i>
                                    </button>
                                </div>
                            @else
                                <span class="tx-hash text-gray-500">-</span>
                            @endif
                        </td>

                        <!-- Date -->
                        <td class="px-3 py-2 text-gray-600 text-xs">{{ $transaction->created_at->format('Y/m/d H:i') }}</td>

                        <!-- Action -->
                        <td class="px-3 py-2">
                            <button onclick="event.stopPropagation(); viewTransactionDetail('{{ $transaction->id }}', 'transaction', event)" class="text-indigo-600 hover:text-indigo-800 p-1">
                                <i class="fas fa-eye text-sm"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                @endforelse

                <!-- Payment Requests -->
                @forelse($paymentRequests as $payment)
                    <?php $refTempP = $payment->invoice_number ?? 'INV-' . $payment->id; ?>
                    <tr id="merchant-transaction-row-{{ $payment->id }}"
                        data-transaction-id="{{ $payment->id }}"
                        data-transaction-status="{{ $payment->status }}"
                                            data-updated-at="{{ $payment->updated_at?->toDateTimeString() }}"
                                            data-customer="{{ e($payment->recipient->name ?? '') }}"
                                            data-description="{{ e(__('merchant.transactions.invoice_number_prefix') . ' #' . $payment->invoice_number) }}"
                                            data-reference="{{ e($refTempP) }}"
                                            data-amount="{{ \App\Support\NumberHelper::formatCryptoAmount($payment->amount) }}"
                                            data-currency="{{ e($payment->currency) }}"
                                            data-txhash=""
                                            data-date="{{ $payment->created_at->format('Y/m/d H:i') }}"
                                            class="hover:bg-gray-50 cursor-pointer" onclick="viewTransactionDetail('{{ $payment->id }}', 'payment', event)">

                        <td class="px-3 py-2" dir="ltr"><code class="bg-gray-100 text-black px-2 py-1 rounded text-xs">{{ substr($refTempP, 0, 18) }}</code></td>
                        <td class="px-3 py-2 text-gray-700 font-semibold text-xs">{{ $payment->currency }}</td>
                        <td class="px-3 py-2"><span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">{{ __('merchant.transactions.invoice') }}</span></td>
                        <td class="px-3 py-2 font-semibold text-xs">{{ \App\Support\NumberHelper::formatCryptoAmount($payment->amount) }} <span class="text-gray-500">{{ $payment->currency }}</span></td>
                        <td class="px-3 py-2">
                            @php $paymentStatus = strtolower((string)$payment->status); @endphp
                            @if(in_array($paymentStatus, ['paid','completed']))
                                <span class="tx-status-cell inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">✓ {{ __('merchant.transactions.paid') }}</span>
                            @elseif(in_array($paymentStatus, ['pending','processing']))
                                <span class="tx-status-cell inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">⏱ {{ __('merchant.transactions.pending') }}</span>
                            @else
                                <span class="tx-status-cell inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">✗ {{ __('merchant.transactions.cancelled') }}</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-xs text-gray-700">-</td>
                        <td class="px-3 py-2 text-gray-600 text-xs">{{ $payment->created_at->format('Y/m/d H:i') }}</td>
                        <td class="px-3 py-2"><button onclick="event.stopPropagation(); viewTransactionDetail('{{ $payment->id }}', 'payment', event)" class="text-indigo-600 hover:text-indigo-800 p-1"><i class="fas fa-eye text-sm"></i></button></td>
                    </tr>
                @empty
                @endforelse

                @if($transactions->count() === 0 && $paymentRequests->count() === 0)
                    <tr>
                        <td colspan="8" class="px-3 py-8 text-center text-gray-500">
                            <i class="fas fa-inbox text-3xl text-gray-300 mb-4 block"></i>
                            {{ __('merchant.transactions.no_transactions') }}
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($transactions->hasPages())
        <div class="px-3 py-2 border-t border-gray-200 text-sm">
            {{ $transactions->links() }}
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
