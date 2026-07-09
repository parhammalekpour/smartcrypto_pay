@extends('layouts.dashboard')

@section('title', 'دریافت کریپتو - CryptoPay')
@section('page-title', 'دریافت کریپتو')
@section('page-subtitle', 'دریافت رمزارز از دیگران')

@section('content')

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Wallets Display -->
    <div class="lg:col-span-2 space-y-6">
        @if($wallets && $wallets->count() > 0)
            @foreach($wallets as $wallet)
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center gap-4 mb-6 pb-4 border-b border-gray-200">
                        <div class="w-12 h-12 
                            @if($wallet->currency === 'BTC') bg-orange-100
                            @elseif($wallet->currency === 'ETH') bg-gray-100
                            @else bg-teal-100 @endif rounded-lg flex items-center justify-center">
                            @if($wallet->currency === 'BTC')
                                <i class="fas fa-bitcoin text-orange-600 text-lg"></i>
                            @elseif($wallet->currency === 'ETH')
                                <i class="fab fa-ethereum text-gray-600 text-lg"></i>
                            @else
                                <i class="fas fa-coins text-teal-600 text-lg"></i>
                            @endif
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-800">{{ $wallet->currency }}</h3>
                            <p class="text-xs text-gray-500">موجودی: {{ number_format($wallet->balance, 8) }}</p>
                        </div>
                    </div>

                    <!-- Content Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Address Section -->
                        <div>
                            <p class="text-sm font-semibold text-gray-700 mb-3">نشانی کیف پول</p>
                            <div class="flex items-center gap-2 mb-3">
                                <input type="text" value="{{ $wallet->wallet_address }}" readonly 
                                    class="flex-1 px-3 py-2 bg-gray-50 border border-gray-300 rounded-lg text-xs font-mono">
                                <button type="button" onclick="copyToClipboard('{{ $wallet->wallet_address }}')" 
                                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg font-semibold hover:bg-indigo-700 transition text-sm">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                            
                            <!-- Info -->
                            <div class="bg-green-50 border border-green-200 rounded-lg p-3">
                                <p class="text-sm text-green-800">
                                    <i class="fas fa-check-circle ml-2"></i>هر کسی می‌تواند به این نشانی ارسال کند
                                </p>
                            </div>
                        </div>

                        <!-- QR Code Section -->
                        <div class="flex flex-col items-center justify-center">
                            <p class="text-sm font-semibold text-gray-700 mb-3">کد QR</p>
                            <div id="qrcode-{{ $wallet->id }}" class="bg-white p-4 rounded-lg border-2 border-gray-300"></div>
                            <p class="text-xs text-gray-500 mt-3">اسکن کنید و ارسال کنید</p>
                        </div>
                    </div>
                </div>
            @endforeach
        @else
            <div class="bg-white rounded-lg shadow p-12 text-center">
                <i class="fas fa-inbox text-5xl text-gray-300 mb-4"></i>
                <p class="text-gray-500 mb-4">هنوز کیف پولی ایجاد نشده است</p>
                <a href="{{ route('user.wallets') }}" class="text-indigo-600 font-semibold hover:underline">
                    ایجاد کیف پول
                </a>
            </div>
        @endif
    </div>

    <!-- Help Card -->
    <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-6 h-fit sticky top-6">
        <h4 class="font-semibold text-gray-800 mb-4">راهنما</h4>
        
        <div class="space-y-4 text-sm text-gray-700">
            <div>
                <p class="font-semibold text-gray-800 mb-1">💡 چگونه دریافت کنم؟</p>
                <p>نشانی خود را کپی کنید یا کد QR را به اشتراک بگذارید</p>
            </div>

            <div class="border-t border-indigo-200 pt-4">
                <p class="font-semibold text-gray-800 mb-1">⏱️ چه مدت طول می‌کشد؟</p>
                <p>معمولاً چند ثانیه تا چند دقیقه</p>
            </div>

            <div class="border-t border-indigo-200 pt-4">
                <p class="font-semibold text-gray-800 mb-1">🔒 آیا خطری دارد؟</p>
                <p>کاملاً ایمن است! فقط نشانی خود را به اشتراک بگذارید</p>
            </div>

            <div class="border-t border-indigo-200 pt-4 bg-yellow-50 p-3 rounded-lg">
                <p class="font-semibold text-yellow-800 mb-1">⚠️ هشدار</p>
                <p class="text-yellow-700">هیچوقت کلید خصوصی خود را با کسی به اشتراک نگذارید</p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Generate QR codes for each wallet
    @if($wallets && $wallets->count() > 0)
        @foreach($wallets as $wallet)
            new QRCode(document.getElementById("qrcode-{{ $wallet->id }}"), {
                text: "{{ $wallet->wallet_address }}",
                width: 150,
                height: 150,
                correctLevel: QRCode.CorrectLevel.H
            });
        @endforeach
    @endif

    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            alert('✓ کپی شد!');
        }).catch(() => {
            alert('خطا در کپی');
        });
    }
</script>
@endpush

@endsection
