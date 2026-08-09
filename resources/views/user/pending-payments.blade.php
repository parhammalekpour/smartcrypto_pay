@extends('layouts.dashboard')

@section('title', __('user.pending_payments_title') . ' - CryptoPay')
@section('page-title', __('user.pending_payments_title'))
@section('page-subtitle', __('user.pending_payments_subtitle'))

@section('content')

<!-- Auto-refresh script -->
<script>
    // Check for payment updates every 3 seconds
    let lastPaymentCount = {{ $payments ? $payments->count() : 0 }};
    
    setInterval(() => {
        fetch(window.location.href, { headers: { 'X-Requested-With': 'fetch' } })
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const newDoc = parser.parseFromString(html, 'text/html');
                const newCount = newDoc.querySelectorAll('[data-payment-item]').length;
                
                if (newCount < lastPaymentCount && newCount === 0) {
                    // All payments completed - reload page
                    window.location.reload();
                } else if (newCount !== lastPaymentCount) {
                    // Payment status changed - reload page
                    window.location.reload();
                }
            })
            .catch(error => console.log('Auto-refresh check:', error));
    }, 3000); // Check every 3 seconds
</script>

@if($payments && $payments->count() > 0)
    <div class="grid grid-cols-1 gap-6">
        @foreach($payments as $payment)
            <div class="bg-white rounded-lg shadow p-6 border-l-4 border-yellow-500" data-payment-item>
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">{{ $payment->invoice_number ?? __('user.payment_request') }}</h3>
                                                <p class="text-sm text-gray-500">{{ __('user.from_merchant') }}: {{ $payment->merchant->name ?? 'نامشخص' }}</p>
                    </div>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-yellow-100 text-yellow-800">
                        <i class="fas fa-hourglass-end ml-2"></i>{{ __('user.payment_waiting') }}
                    </span>
                </div>

                <!-- Payment Details -->
                <div class="grid grid-cols-3 gap-4 mb-6 p-4 bg-gray-50 rounded-lg">
                    <div>
                        <p class="text-xs text-gray-500 mb-1">{{ __('common.amount') }}</p>
                        <p class="font-bold text-gray-800">{{ \App\Support\NumberHelper::formatCryptoAmount($payment->amount) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">{{ __('common.currency') }}</p>
                        <p class="font-bold text-gray-800">{{ $payment->currency }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">{{ __('common.date') }}</p>
                        <p class="font-bold text-gray-800">{{ $payment->created_at->format('Y/m/d') }}</p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex gap-3">
                    <a href="{{ url('/pay/' . $payment->token) }}" class="flex-1 bg-indigo-600 text-white py-2 rounded-lg font-semibold hover:bg-indigo-700 transition text-center">
                        <i class="fas fa-check-circle ml-2"></i>{{ __('user.pay_now_button') }}
                    </a>
                    <form action="{{ route('payment-request.reject', $payment->id) }}" method="POST" class="px-6">
                        @csrf
                        <button type="submit" class="px-6 py-2 bg-gray-200 text-gray-800 rounded-lg font-semibold hover:bg-gray-300 transition" onclick="return confirm('{{ __('user.reject_payment_confirm') }}')">
                            <i class="fas fa-times ml-2"></i>{{ __('user.reject_payment') }}
                        </button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Pagination -->
    <div class="mt-6 flex items-center justify-between">
        <p class="text-sm text-gray-600">{{ __('user.payment_request_count', ['count' => $payments->count()]) }}</p>
        <div class="flex gap-2">
            <button class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-semibold hover:bg-gray-50">قبلی</button>
            <button class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-semibold hover:bg-gray-50">بعدی</button>
        </div>
    </div>
@else
    <div class="bg-white rounded-lg shadow p-12 text-center">
        <i class="fas fa-check-circle text-5xl text-green-300 mb-4"></i>
        <p class="text-gray-500 mb-4">{{ __('user.no_pending_payments') }}</p>
        <a href="{{ route('user.dashboard') }}" class="text-indigo-600 font-semibold hover:underline">
            {{ __('common.view_dashboard') }}
        </a>
    </div>
@endif

@endsection
