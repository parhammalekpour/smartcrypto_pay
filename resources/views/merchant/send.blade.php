@extends('layouts.dashboard')

@section('title', __('user.send_crypto_title') . ' - CryptoPay')
@section('page-title', __('user.send_crypto_title'))
@section('page-subtitle', __('user.send_crypto_description'))

@section('content')

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Transfer Form -->
    <div class="lg:col-span-2 bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-6 pb-4 border-b border-gray-200">{{ __('user.send_crypto_title') }}</h3>

        <form method="POST" action="{{ route('merchant.send.post') }}" class="space-y-6">
            @csrf

            <!-- Select Wallet to Send From -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('user.wallet_source') }}</label>
                <select name="sender_wallet_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <option value="">{{ __('user.select_wallet') }}</option>
                    @if($wallets && $wallets->count() > 0)
                        @foreach($wallets as $wallet)
                            <option value="{{ $wallet->id }}" @if(isset($preselected) && $preselected == $wallet->id) selected @endif>
                                {{ $wallet->currency }} - {{ \App\Support\NumberHelper::formatCryptoAmount($wallet->balance) }}
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
                <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('user.recipient_address') }}</label>
                <input type="text" name="wallet_address" required placeholder="{{ __('user.recipient_address_placeholder') }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" value="{{ old('wallet_address') }}">
                @error('wallet_address')
                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Amount -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('common.amount') }}</label>
                <input type="text" name="amount" required placeholder="0.001"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" value="{{ old('amount') }}">
                @error('amount')
                    <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            @php
                $two = \App\Models\TwoFactor::where('user_id', auth()->id())->first();
            @endphp

            @if(!$two || !$two->enabled_at)
                <div class="mb-4 p-4 rounded-lg bg-yellow-50 border border-yellow-200 text-yellow-700 text-sm">
                    {{ __('user.two_factor_hint') }}
                </div>
            @endif

            @if($two && $two->enabled_at)
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('user.two_factor_code') }}</label>
                    <input type="text" name="two_factor_token" required placeholder="{{ __('user.two_factor_code_placeholder') }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent" value="{{ old('two_factor_token') }}">
                    @error('two_factor_token')
                        <p class="text-red-600 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            <!-- Description (Optional) -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('user.description_optional') }}</label>
                <textarea name="description" placeholder="{{ __('user.description_placeholder') }}" rows="3"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">{{ old('description') }}</textarea>
            </div>

            <!-- Submit -->
            <div class="flex gap-3">
                <button type="submit" class="flex-1 bg-indigo-600 text-white py-2 rounded-lg font-semibold hover:bg-indigo-700 transition" @if(!$two || !$two->enabled_at) disabled @endif>
                    <i class="fas fa-paper-plane ml-2"></i>{{ __('common.send') }}
                </button>
                <a href="{{ route('merchant.wallets') }}" class="flex-1 bg-gray-200 text-gray-800 py-2 rounded-lg font-semibold hover:bg-gray-300 transition text-center">
                    {{ __('common.cancel') }}
                </a>
            </div>
        </form>
    </div>

    <!-- Info Card -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
        <div class="flex gap-3 mb-4">
            <i class="fas fa-info-circle text-blue-600 text-lg mt-1"></i>
            <div>
                <h4 class="font-semibold text-gray-800">{{ __('user.important_notes') }}</h4>
            </div>
        </div>

        <ul class="space-y-3 text-sm text-gray-700">
            <li class="flex gap-2">
                <span class="text-blue-600">•</span>
                <span>{{ __('user.transfer_note_1') }}</span>
            </li>
            <li class="flex gap-2">
                <span class="text-blue-600">•</span>
                <span>{{ __('user.transfer_note_2') }}</span>
            </li>
            <li class="flex gap-2">
                <span class="text-blue-600">•</span>
                <span>{{ __('user.transfer_note_3') }}</span>
            </li>
            <li class="flex gap-2">
                <span class="text-blue-600">•</span>
                <span>{{ __('user.transfer_note_4') }}</span>
            </li>
        </ul>
    </div>
</div>

@endsection