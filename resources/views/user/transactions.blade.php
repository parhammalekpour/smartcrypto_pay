@extends('layouts.dashboard')

@section('title', 'تاریخچه تراکنش‌ها - CryptoPay')
@section('page-title', 'تاریخچه تراکنش‌ها')
@section('page-subtitle', 'مشاهده تمام تراکنش‌های شما')

@section('content')

<!-- Filter Section -->
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">فیلتر تراکنش‌ها</h3>
    
    <form method="GET" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Type Filter -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">نوع تراکنش</label>
                <select name="type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <option value="">همه</option>
                    <option value="transfer" @if(request('type') === 'transfer') selected @endif>ارسال</option>
                    <option value="deposit" @if(request('type') === 'deposit') selected @endif>دریافت / دمو</option>
                </select>
            </div>

            <!-- Currency Filter -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">ارز</label>
                <select name="currency" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <option value="">همه</option>
                    <option value="BTC" @if(request('currency') === 'BTC') selected @endif>Bitcoin (BTC)</option>
                    <option value="ETH" @if(request('currency') === 'ETH') selected @endif>Ethereum (ETH)</option>
                    <option value="USDT" @if(request('currency') === 'USDT') selected @endif>Tether (USDT)</option>
                </select>
            </div>

            <!-- Amount Filter -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">میزان</label>
                <select name="amount_range" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <option value="">همه</option>
                    <option value="0-0.1" @if(request('amount_range') === '0-0.1') selected @endif>کمتر از 0.1</option>
                    <option value="0.1-1" @if(request('amount_range') === '0.1-1') selected @endif>0.1 تا 1</option>
                    <option value="1-10" @if(request('amount_range') === '1-10') selected @endif>1 تا 10</option>
                    <option value="10+" @if(request('amount_range') === '10+') selected @endif>بیشتر از 10</option>
                </select>
            </div>

            <!-- Search Box -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">جستجو</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="نام یا شناسه" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex gap-3">
            <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-indigo-700 transition flex items-center gap-2">
                <i class="fas fa-search"></i>جستجو
            </button>
            <a href="{{ route('user.transactions') }}" class="bg-gray-200 text-gray-800 px-6 py-2 rounded-lg font-semibold hover:bg-gray-300 transition flex items-center gap-2">
                <i class="fas fa-redo"></i>پاک کردن فیلتر
            </a>
        </div>
    </form>
</div>

<!-- Results Summary -->
@if(request('type') || request('currency') || request('search') || request('amount_range'))
<div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg flex items-start gap-3">
    <i class="fas fa-info-circle text-blue-600 text-xl mt-0.5"></i>
    <div>
        <p class="font-semibold text-blue-800">نتایج فیلتر شده</p>
        <p class="text-sm text-blue-700">
            @if(request('type'))
                نوع: <strong>{{ request('type') === 'transfer' ? 'ارسال' : 'دریافت' }}</strong>
            @endif
            @if(request('currency'))
                ارز: <strong>{{ request('currency') }}</strong>
            @endif
            @if(request('search'))
                جستجو: <strong>{{ request('search') }}</strong>
            @endif
        </p>
    </div>
    <button onclick="location.href='{{ route('user.transactions') }}'" class="ml-auto text-blue-600 hover:text-blue-800">
        <i class="fas fa-times"></i>
    </button>
</div>
@endif

<!-- Transactions List -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="p-6 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-800">
            تراکنش‌های شما
            @if($transactions && $transactions->total() > 0)
                <span class="text-sm font-normal text-gray-600">({{ $transactions->total() }} تراکنش)</span>
            @endif
        </h3>
    </div>

    <div class="divide-y divide-gray-200">
        @if($transactions && $transactions->count() > 0)
            @foreach($transactions as $transaction)
            <div class="p-6 hover:bg-gray-50 transition">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 @if($transaction->type === 'transfer') bg-orange-100 @else bg-green-100 @endif rounded-full flex items-center justify-center">
                            <i class="fas @if($transaction->type === 'transfer') fa-arrow-left text-orange-600 @else fa-arrow-right text-green-600 @endif text-lg"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-800">{{ $transaction->display_title }}</p>
                            <p class="text-sm text-gray-500">{{ $transaction->created_at->format('Y/m/d H:i') }} • {{ $transaction->wallet->currency ?? 'UNKNOWN' }}</p>
                            @if($transaction->type === 'deposit')
                                @if($transaction->wallet?->wallet_address)
                                    <p class="text-xs text-gray-500 mt-1">آدرس دریافت‌کننده: <code dir="ltr" class="text-gray-700">{{ $transaction->wallet->wallet_address }}</code></p>
                                @endif
                                <p class="text-xs text-gray-500 mt-1">شناسه تراکنش: <code dir="ltr" class="text-gray-700">{{ $transaction->reference }}</code></p>
                            @endif
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="font-semibold text-lg @if($transaction->type === 'transfer') text-orange-600 @else text-green-600 @endif">
                            @if($transaction->type === 'transfer')
                                -{{ number_format($transaction->amount, 8) }}
                            @else
                                +{{ number_format($transaction->amount, 8) }}
                            @endif
                        </p>
                        <p class="text-xs text-gray-500 mt-1">{{ $transaction->wallet->currency ?? 'UNKNOWN' }}</p>
                    </div>
                </div>
                <div class="mt-3 p-3 bg-gray-50 rounded flex items-center justify-between">
                    <p class="text-xs text-gray-600">
                        <span class="font-semibold">شناسه:</span>
                        <code dir="ltr" class="text-gray-700">
                            @if($transaction->paymentRequest)
                                {{ 'INV-' . $transaction->paymentRequest->invoice_number }}
                            @elseif($transaction->reference)
                                {{ $transaction->reference }}
                            @else
                                TRX-{{ $transaction->id }}
                            @endif
                        </code>
                    </p>
                </div>
            </div>
            @endforeach
        @else
        <div class="p-12 text-center">
            <i class="fas fa-inbox text-5xl text-gray-300 mb-4"></i>
            <p class="text-gray-500 text-lg">تراکنشی یافت نشد</p>
            <p class="text-sm text-gray-400 mt-2">
                @if(request('type') || request('currency') || request('search') || request('amount_range'))
                    با فیلترهای فعلی تراکنشی وجود ندارد
                @else
                    هنوز تراکنشی انجام نشده است
                @endif
            </p>
        </div>
        @endif
    </div>
</div>

<!-- Pagination -->
@if($transactions && $transactions->hasPages())
<div class="mt-6">
    {{ $transactions->links() }}
</div>
@endif

@endsection
