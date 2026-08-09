@extends('layouts.dashboard')

@section('title', __('user.receive.page_title') . ' - CryptoPay')
@section('page-title', __('user.receive.page_title'))
@section('page-subtitle', __('user.receive.page_subtitle'))

@section('content')
@php $isRtl = app()->getLocale() === 'fa'; @endphp

@push('styles')
<style>
    .receive-shell {
        padding: 0.2rem 0 0.25rem;
    }
    .receive-card {
        border: 1px solid #e2e8f0;
        border-radius: 24px;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
    }
    .receive-card:hover {
        box-shadow: 0 14px 34px rgba(15, 23, 42, 0.09);
    }
    .receive-address-pill {
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        padding: 0.6rem 0.7rem;
    }
    .receive-address-pill input {
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        letter-spacing: 0.01em;
    }
    .receive-qr-shell {
        width: 168px;
        height: 168px;
        border-radius: 20px;
        border: 1px solid #e2e8f0;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0.85rem;
    }
    .receive-help-card {
        border: 1px solid #e2e8f0;
        border-radius: 24px;
        background: #ffffff;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
    }
</style>
@endpush

<div class="receive-shell max-w-7xl" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
    <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1.6fr)_320px] gap-4 lg:gap-6">
        <div class="space-y-4">
            @if($wallets && $wallets->count() > 0)
                @foreach($wallets as $wallet)
                    <div class="receive-card p-5 md:p-6">
                        <div class="flex {{ $isRtl ? 'flex-row-reverse' : '' }} flex-wrap items-start justify-between gap-4 pb-4 border-b border-slate-200">
                            <div class="flex {{ $isRtl ? 'flex-row-reverse' : '' }} items-center gap-3 min-w-0">
                                <div class="flex items-center gap-2">
                                    <div class="h-6 w-6 flex items-center justify-center rounded-full
                                        @if($wallet->currency === 'BTC') bg-orange-100
                                        @elseif($wallet->currency === 'ETH') bg-slate-100
                                        @else bg-teal-100 @endif">
                                        @if($wallet->currency === 'BTC')
                                            <i class="fab fa-bitcoin text-orange-600 text-base"></i>
                                        @elseif($wallet->currency === 'ETH')
                                            <i class="fab fa-ethereum text-slate-700 text-base"></i>
                                        @else
                                            <i class="fas fa-coins text-teal-600 text-base"></i>
                                        @endif
                                    </div>
                                    <div class="{{ $isRtl ? 'text-right' : 'text-left' }}">
                                        <div class="flex {{ $isRtl ? 'flex-row-reverse' : '' }} items-center gap-2">
                                            <h3 class="text-lg font-semibold text-slate-900">{{ $wallet->currency }}</h3>
                                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">
                                                {{ $wallet->currency === 'BTC' ? 'Bitcoin' : ($wallet->currency === 'ETH' ? 'Ethereum' : 'USDT') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-600">
                                {{ __('wallets.address') }}
                            </div>
                        </div>

                        <div class="mt-5 grid grid-cols-1 lg:grid-cols-[1.1fr_0.9fr] gap-5 lg:gap-6">
                            <div class="min-w-0">
                                <div class="flex {{ $isRtl ? 'flex-row-reverse' : '' }} items-center justify-between gap-3">
                                    <label class="text-sm font-semibold text-slate-700">{{ __('wallets.address') }}</label>
                                    <span class="text-xs font-medium text-slate-400">{{ __('user.receive.share_hint') }}</span>
                                </div>
                                <div class="receive-address-pill mt-3 flex {{ $isRtl ? 'flex-row-reverse' : '' }} items-center gap-2">
                                    <input type="text" value="{{ substr($wallet->wallet_address, 0, 10) }}...{{ substr($wallet->wallet_address, -6) }}" readonly class="w-full border-0 bg-transparent px-1 py-1 text-sm font-medium text-slate-700 outline-none">
                                    <button type="button" onclick="copyToClipboard('{{ $wallet->wallet_address }}')" aria-label="{{ __('wallets.copy') }}"
                                        class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-3.5 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700">
                                        <i class="fas fa-copy"></i>
                                        <span class="hidden sm:inline">{{ __('wallets.copy') }}</span>
                                    </button>
                                </div>

                                <div class="mt-3 inline-flex {{ $isRtl ? 'flex-row-reverse' : '' }} items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700">
                                    <i class="fas fa-info-circle"></i>
                                    <span class="{{ $isRtl ? 'mr-2' : 'ml-2' }}">{{ __('user.receive.anyone_can_send') }}</span>
                                </div>
                            </div>

                            <div class="flex flex-col items-center justify-center rounded-[20px] border border-slate-200 bg-slate-50/70 p-4">
                                <p class="text-sm font-semibold text-slate-700">{{ __('user.receive.scan_qr_code') }}</p>
                                <div class="receive-qr-shell mt-3" id="qrcode-{{ $wallet->id }}"></div>
                                <p class="mt-3 text-sm text-slate-500">{{ __('user.receive.scan_and_send') }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="receive-card p-12 text-center">
                    <i class="fas fa-wallet text-5xl text-slate-300 mb-4"></i>
                    <p class="text-slate-600 mb-4">{{ __('wallets.no_wallets') }}</p>
                    <a href="{{ route('user.wallets') }}" class="inline-flex items-center justify-center rounded-2xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700">
                        {{ __('user.receive.create_wallet') }}
                    </a>
                </div>
            @endif
        </div>

        <div class="receive-help-card p-5 md:p-6 h-fit xl:sticky xl:top-6">
            <div class="flex {{ $isRtl ? 'flex-row-reverse' : '' }} items-center justify-between gap-3">
                <div>
                    <h4 class="text-lg font-semibold text-slate-900">{{ __('user.receive.help_title') }}</h4>
                    <p class="mt-1 text-sm text-slate-500">{{ __('user.receive.help_subtitle') }}</p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600">
                    <i class="fas fa-question-circle"></i>
                </div>
            </div>

            <div class="mt-5 space-y-3">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-sm font-semibold text-slate-900">💡 {{ __('user.receive.how_to_receive') }}</p>
                    <p class="mt-1 text-sm leading-6 text-slate-600">{{ __('user.receive.how_to_receive_desc') }}</p>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-sm font-semibold text-slate-900">⏱️ {{ __('user.receive.time_to_arrive') }}</p>
                    <p class="mt-1 text-sm leading-6 text-slate-600">{{ __('user.receive.time_to_arrive_desc') }}</p>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-sm font-semibold text-slate-900">🔒 {{ __('user.receive.security_title') }}</p>
                    <p class="mt-1 text-sm leading-6 text-slate-600">{{ __('user.receive.security_desc') }}</p>
                </div>
            </div>

            <div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 p-4">
                <p class="text-sm font-semibold text-amber-800">⚠️ {{ __('user.receive.warning_title') }}</p>
                <p class="mt-1 text-sm leading-6 text-amber-700">{{ __('user.receive.warning_desc') }}</p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    @if($wallets && $wallets->count() > 0)
        @foreach($wallets as $wallet)
            new QRCode(document.getElementById("qrcode-{{ $wallet->id }}"), {
                text: "{{ $wallet->wallet_address }}",
                width: 168,
                height: 168,
                correctLevel: QRCode.CorrectLevel.H
            });
        @endforeach
    @endif

    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            alert('{{ __('user.receive.copied') }}');
        }).catch(() => {
            alert('{{ __('user.receive.copy_error') }}');
        });
    }
</script>
@endpush

@endsection
