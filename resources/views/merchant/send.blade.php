@extends('layouts.dashboard')

@section('title', 'ارسال کریپتو - CryptoPay')
@section('page-title', 'ارسال کریپتو (فروشنده)')
@section('page-subtitle', 'ارسال رمزارز به کیف پول دیگری')

@section('content')

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Transfer Form -->
    <div class="lg:col-span-2 bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-6 pb-4 border-b border-gray-200">ارسال رمزارز</h3>

        <form method="POST" action="{{ route('merchant.send.post') }}" class="space-y-6">
            @csrf

            <!-- Select Wallet to Send From -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">کیف پول مبدا</label>
                <select name="sender_wallet_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <option value="">انتخاب کیف پول</option>
                    @if($wallets && $wallets->count() > 0)
                        @foreach($wallets as $wallet)
                            <option value="{{ $wallet->id }}" @if(isset($preselected) && $preselected == $wallet->id) selected @endif>
                                {{ $wallet->currency }} - {{ number_format($wallet->balance, 8) }}
                            </option>
                        @endforeach
                    @endif
                </select>
                @error('sender_wallet_id')
                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Recipient Wallet Address -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">نشانی گیرنده</label>
                <input type="text" name="wallet_address" required placeholder="نشانی کیف پول گیرنده" 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" value="{{ old('wallet_address') }}">
                @error('wallet_address')
                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Amount -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">مبلغ</label>
                <input type="number" step="0.00000001" name="amount" required placeholder="0.00000000" 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" value="{{ old('amount') }}">
                @error('amount')
                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Description (Optional) -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">توضیحات (اختیاری)</label>
                <textarea name="description" placeholder="یادداشت برای این انتقال" rows="3"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">{{ old('description') }}</textarea>
            </div>

            <!-- Submit -->
            <div class="flex gap-3">
                <button type="submit" class="flex-1 bg-indigo-600 text-white py-2 rounded-lg font-semibold hover:bg-indigo-700 transition">
                    <i class="fas fa-paper-plane ml-2"></i>ارسال
                </button>
                <a href="{{ route('merchant.wallets') }}" class="flex-1 bg-gray-200 text-gray-800 py-2 rounded-lg font-semibold hover:bg-gray-300 transition text-center">
                    انصراف
                </a>
            </div>
        </form>
    </div>

    <!-- Info Card -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
        <div class="flex gap-3 mb-4">
            <i class="fas fa-info-circle text-blue-600 text-lg mt-1"></i>
            <div>
                <h4 class="font-semibold text-gray-800">نکات مهم</h4>
            </div>
        </div>
        
        <ul class="space-y-3 text-sm text-gray-700">
            <li class="flex gap-2">
                <span class="text-blue-600">•</span>
                <span>ارسال‌ها معمولاً فوری هستند</span>
            </li>
            <li class="flex gap-2">
                <span class="text-blue-600">•</span>
                <span>نشانی گیرنده را دقیق بررسی کنید</span>
            </li>
            <li class="flex gap-2">
                <span class="text-blue-600">•</span>
                <span>ارسال بدون برگشت است</span>
            </li>
            <li class="flex gap-2">
                <span class="text-blue-600">•</span>
                <span>ارزهای مختلف را با هم ترکیب نکنید</span>
            </li>
        </ul>
    </div>
</div>

@endsection