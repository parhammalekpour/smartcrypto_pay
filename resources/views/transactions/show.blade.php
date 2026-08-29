@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6" x-data="transactionPage({{ $transaction->id }})" x-init="init()">
    <div class="max-w-6xl mx-auto space-y-6">
        <div class="rounded-[32px] border border-slate-800/20 bg-slate-950/90 p-6 shadow-xl shadow-slate-950/10">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="space-y-2">
                    <p class="text-sm font-semibold uppercase tracking-[0.28em] text-slate-400">Transaction details</p>
                    <h1 class="text-3xl font-semibold text-white">Transaction overview</h1>
                    <p class="max-w-2xl text-sm leading-6 text-slate-400">Review the finalized blockchain status and wallet activity for this transaction.</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('user.transactions') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-700/80 bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:border-slate-500 hover:bg-slate-800">
                        Back to transactions
                    </a>
                    <button type="button" @click="refresh()" class="inline-flex items-center justify-center rounded-2xl bg-slate-700 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-600">
                        <span x-show="!isRefreshing">Refresh transactions</span>
                        <span x-show="isRefreshing" class="inline-flex items-center gap-2">
                            <svg class="h-4 w-4 animate-spin text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-80" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                            Refreshing...
                        </span>
                    </button>
                </div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1.3fr_0.7fr]">
            <section class="rounded-[32px] border border-slate-800/20 bg-slate-950/90 p-6 shadow-xl shadow-slate-950/10">
                <div class="grid gap-4">
                    <div class="grid gap-2 sm:grid-cols-2 sm:items-center">
                        <div class="space-y-1">
                            <p class="text-sm uppercase tracking-[0.2em] text-slate-400">{{ __('transactions.transaction_id') ?? 'Transaction ID' }}</p>
                            <p class="text-lg font-semibold text-white">#TX-{{ str_pad($transaction->id, 4, '0', STR_PAD_LEFT) }}</p>
                        </div>
                        <div class="rounded-3xl bg-slate-900 px-4 py-3 text-sm font-semibold text-slate-300">
                            <span x-text="displayStatus"></span>
                        </div>
                    </div>

                    <div class="grid gap-4 lg:grid-cols-2">
                        <div class="rounded-3xl border border-slate-800/40 bg-slate-900/80 p-5">
                            <p class="text-sm uppercase tracking-[0.2em] text-slate-400">{{ __('transactions.asset') ?? 'Asset' }}</p>
                            <p class="mt-3 text-xl font-semibold text-white">{{ $transaction->currency }}</p>
                        </div>
                        <div class="rounded-3xl border border-slate-800/40 bg-slate-900/80 p-5">
                            <p class="text-sm uppercase tracking-[0.2em] text-slate-400">{{ __('transactions.amount') ?? 'Amount' }}</p>
                            <p class="mt-3 text-xl font-semibold text-white">{{ number_format((float)$transaction->amount, 8, '.', '') }} {{ $transaction->currency }}</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="grid gap-4 lg:grid-cols-2">
                            <div class="rounded-3xl border border-slate-800/40 bg-slate-900/80 p-5">
                                <p class="text-sm uppercase tracking-[0.2em] text-slate-400">{{ __('transactions.sender') ?? 'Sender' }}</p>
                                <p class="mt-3 text-sm text-slate-200 break-words">{{ $transaction->sender_wallet_address ?? __('transactions.not_available') }}</p>
                            </div>
                            <div class="rounded-3xl border border-slate-800/40 bg-slate-900/80 p-5">
                                <p class="text-sm uppercase tracking-[0.2em] text-slate-400">{{ __('transactions.receiver') ?? 'Receiver' }}</p>
                                <p class="mt-3 text-sm text-slate-200 break-words">{{ $transaction->receiver_wallet_address ?? __('transactions.not_available') }}</p>
                            </div>
                        </div>

                        <div class="grid gap-4 lg:grid-cols-2">
                            <div class="rounded-3xl border border-slate-800/40 bg-slate-900/80 p-5">
                                <p class="text-sm uppercase tracking-[0.2em] text-slate-400">{{ __('transactions.transaction_hash') }}</p>
                                <p class="mt-3 text-sm text-slate-200 break-words" x-text="txHash ? txHash : 'Pending broadcast...'"></p>
                            </div>
                            <div class="rounded-3xl border border-slate-800/40 bg-slate-900/80 p-5">
                                <p class="text-sm uppercase tracking-[0.2em] text-slate-400">{{ __('transactions.confirmations') }}</p>
                                <p class="mt-3 text-lg font-semibold text-white" x-text="confirmations !== null ? confirmations : '—'"></p>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-4 lg:grid-cols-2">
                        <div class="rounded-3xl border border-slate-800/40 bg-slate-900/80 p-5">
                            <p class="text-sm uppercase tracking-[0.2em] text-slate-400">{{ __('transactions.block_number') ?? 'Block number' }}</p>
                            <p class="mt-3 text-sm text-slate-200" x-text="blockNumber !== null ? blockNumber : '—'"></p>
                        </div>
                        <div class="rounded-3xl border border-slate-800/40 bg-slate-900/80 p-5">
                            <p class="text-sm uppercase tracking-[0.2em] text-slate-400">{{ __('transactions.updated_at') ?? 'Updated at' }}</p>
                            <p class="mt-3 text-sm text-slate-200" x-text="updatedAt"></p>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-slate-800/40 bg-slate-900/80 p-5">
                        <p class="text-sm uppercase tracking-[0.2em] text-slate-400">{{ __('transactions.notes') ?? 'Notes' }}</p>
                        <p class="mt-3 text-sm leading-7 text-slate-300" x-text="statusDetail"></p>
                    </div>

                    <template x-if="failureReason">
                        <div class="rounded-3xl border border-rose-700/40 bg-rose-950/90 p-5">
                            <p class="text-sm uppercase tracking-[0.2em] text-rose-300">{{ __('transactions.failure_reason') ?? 'Failure reason' }}</p>
                            <p class="mt-3 text-sm leading-7 text-rose-100" x-text="failureReason"></p>
                        </div>
                    </template>
                </div>
            </section>

            <aside class="rounded-[32px] border border-slate-800/20 bg-slate-950/90 p-6 shadow-xl shadow-slate-950/10">
                <div class="space-y-6">
                    <div class="rounded-3xl border border-slate-800/40 bg-slate-900/80 p-5">
                        <p class="text-sm uppercase tracking-[0.2em] text-slate-400">{{ __('transactions.type') ?? 'Type' }}</p>
                        <p class="mt-3 text-lg font-semibold text-white">{{ ucfirst($transaction->type) }}</p>
                    </div>
                    <div class="rounded-3xl border border-slate-800/40 bg-slate-900/80 p-5">
                        <p class="text-sm uppercase tracking-[0.2em] text-slate-400">{{ __('transactions.created_at') ?? 'Created at' }}</p>
                        <p class="mt-3 text-sm text-slate-200">{{ $transaction->created_at?->toDateTimeString() }}</p>
                    </div>
                    <div class="rounded-3xl border border-slate-800/40 bg-slate-900/80 p-5">
                        <p class="text-sm uppercase tracking-[0.2em] text-slate-400">{{ __('transactions.source') ?? 'Source' }}</p>
                        <p class="mt-3 text-sm text-slate-200">{{ ($transaction->tx_hash ?? $transaction->reference) ? 'Blockchain' : 'Pending broadcast' }}</p>
                    </div>
                    <div class="rounded-3xl border border-slate-800/40 bg-slate-900/80 p-5">
                        <p class="text-sm uppercase tracking-[0.2em] text-slate-400">{{ __('transactions.recipient_wallet') ?? 'Recipient wallet' }}</p>
                        <p class="mt-3 text-sm text-slate-200 break-words">{{ $transaction->receiver_wallet_address ?? __('transactions.not_available') }}</p>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</div>

<script>
    function transactionPage(transactionId) {
        return {
            id: transactionId,
            txHash: {{ ($transaction->tx_hash ?? $transaction->reference) ? json_encode($transaction->tx_hash ?? $transaction->reference) : 'null' }},
            reference: {{ $transaction->reference ? json_encode($transaction->reference) : 'null' }},
            status: '{{ $transaction->status }}',
            blockNumber: {{ $transaction->block_number !== null ? json_encode($transaction->block_number) : 'null' }},
            confirmations: {{ $transaction->confirmations !== null ? json_encode($transaction->confirmations) : 'null' }},
            createdAt: '{{ $transaction->created_at?->toDateTimeString() }}',
            updatedAt: '{{ $transaction->updated_at?->toDateTimeString() }}',
            displayStatus: '',
            statusDetail: '',
            failureReason: '{{ $transaction->status === 'failed' ? addslashes($transaction->description ?? '') : '' }}',
            explorerLink: '',
            isRefreshing: false,
            _pollTimer: null,
            statusLabels: {
                'processing': '{{ __('transactions.processing') }}',
                'pending': '{{ __('common.pending') }}',
                'confirmed': '{{ __('common.confirmed') }}',
                'completed': '{{ __('common.completed') }}',
                'failed': '{{ __('common.failed') }}',
                'cancelled': '{{ __('common.cancelled') }}'
            },

            _statusRank: {
                'processing': 1,
                'pending': 2,
                'confirmed': 3,
                'completed': 4,
                'failed': 100,
                'cancelled': 100
            },

            isFinalStatus(status) {
                return ['confirmed', 'completed', 'failed', 'cancelled'].includes((status||'').toLowerCase());
            },

            refresh() {
                this.isRefreshing = true;
                location.reload();
            },

            updateStatusDetail(status) {
                if (!status) { this.statusDetail = ''; return; }
                status = status.toLowerCase();
                if (status === 'failed') {
                    this.statusDetail = '{{ addslashes('The transaction failed on the network. Check the failure reason if available.') }}';
                    return;
                }
                if (status === 'confirmed') {
                    this.statusDetail = '{{ addslashes('This transaction has been confirmed on the blockchain.') }}';
                    return;
                }
                if (status === 'completed') {
                    this.statusDetail = '{{ addslashes('This transaction is completed and settled.') }}';
                    return;
                }
                if (status === 'pending') {
                    this.statusDetail = '{{ addslashes('The transaction is pending confirmation on blockchain.') }}';
                    return;
                }
                if (status === 'processing') {
                    this.statusDetail = '{{ addslashes('The transaction is being prepared and broadcast to the network.') }}';
                    return;
                }
                this.statusDetail = '';
            },

            _parseDate(value) {
                if (!value) return null;
                const t = Date.parse(value);
                return isNaN(t) ? null : new Date(t);
            },

            _shouldAccept(incoming) {
                const existingStatus = (this.status || '').toLowerCase();
                const incomingStatus = (incoming.status || '').toLowerCase();
                const existingUpdated = this._parseDate(this.updatedAt || '');
                const incomingUpdated = this._parseDate(incoming.updated_at || '');

                const existingIsFinal = this.isFinalStatus(existingStatus);
                const incomingIsFinal = this.isFinalStatus(incomingStatus);

                if (existingIsFinal && !incomingIsFinal) return false;

                if (!existingUpdated || !incomingUpdated) {
                    return (this._statusRank[incomingStatus] || 0) >= (this._statusRank[existingStatus] || 0);
                }

                if (incomingUpdated > existingUpdated) return true;
                if (incomingUpdated.getTime() === existingUpdated.getTime()) return (this._statusRank[incomingStatus] || 0) >= (this._statusRank[existingStatus] || 0);
                if (incomingUpdated < existingUpdated) return (!existingIsFinal) && ((this._statusRank[incomingStatus] || 0) > (this._statusRank[existingStatus] || 0));
                return false;
            },

            _applyIncoming(data) {
                if (!data) return false;
                // update txHash independently if present (fallback to reference)
                const incomingHash = data.tx_hash || data.reference || null;
                if (incomingHash && incomingHash !== this.txHash) {
                    this.txHash = incomingHash;
                    this.explorerLink = this.txHash ? ('https://sepolia.etherscan.io/tx/' + this.txHash) : '';
                }

                if (!this._shouldAccept(data)) {
                    return false;
                }

                // apply updates
                this.status = data.status || this.status;
                this.blockNumber = data.block_number !== undefined ? data.block_number : this.blockNumber;
                this.confirmations = data.confirmations !== undefined ? data.confirmations : this.confirmations;
                this.updatedAt = data.updated_at || this.updatedAt;
                this.displayStatus = this.statusLabels[this.status] || this.status;
                this.failureReason = data.failure_reason || this.failureReason;
                this.updateStatusDetail(this.status);

                return true;
            },

            init() {
                this.displayStatus = this.statusLabels[this.status] || this.status;
                this.explorerLink = this.txHash ? 'https://sepolia.etherscan.io/tx/' + this.txHash : '';
                this.updateStatusDetail(this.status);

                // stop immediately if already final
                if (this.isFinalStatus(this.status)) {
                    return;
                }

                const pollOnce = async () => {
                    try {
                        const resp = await fetch('/api/transaction/' + encodeURIComponent(this.id), { credentials: 'same-origin' });
                        if (!resp.ok) return;
                        const data = await resp.json();
                        if (!data) return;

                        const accepted = this._applyIncoming(data);

                        // if incoming indicates final state, stop polling
                        if (this.isFinalStatus(data.status)) {
                            if (this._pollTimer) { clearTimeout(this._pollTimer); this._pollTimer = null; }
                            return;
                        }
                    } catch (e) {
                        // ignore
                    }

                    this._pollTimer = setTimeout(pollOnce, 3000);
                };

                pollOnce();
            }
        };
    }
</script>
@endsection
