@extends('layouts.dashboard')

@section('title', 'کیف پول‌های من - CryptoPay')
@section('page-title', 'کیف پول‌های من')
@section('page-subtitle', 'مدیریت و نظارت بر کیف پول‌های رمزنگاری‌شده')

@section('content')

<!-- Add Wallet Button -->
<div class="mb-6 flex justify-end">
    <button onclick="openAddWalletModal()" class="bg-indigo-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-indigo-700 transition flex items-center gap-2">
        <i class="fas fa-plus"></i>اضافه کردن کیف پول جدید
    </button>
</div>

<!-- Wallets Grid -->
@if($wallets && $wallets->count() > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
        @foreach($wallets as $wallet)
            <div class="bg-white rounded-lg shadow hover:shadow-lg transition p-6 border-t-4 
                @if($wallet->currency === 'BTC') border-t-orange-500
                @elseif($wallet->currency === 'ETH') border-t-gray-500
                @else border-t-teal-500 @endif">
                
                <!-- Header -->
                <div class="flex items-center justify-between mb-4 pb-4 border-b border-gray-200">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 
                            @if($wallet->currency === 'BTC') bg-orange-100
                            @elseif($wallet->currency === 'ETH') bg-gray-100
                            @else bg-teal-100 @endif rounded-lg flex items-center justify-center">
                            @if($wallet->currency === 'BTC')
                                <i class="fab fa-bitcoin text-orange-600 text-xl"></i>
                            @elseif($wallet->currency === 'ETH')
                                <i class="fab fa-ethereum text-gray-600 text-lg"></i>
                            @else
                                <i class="fas fa-coins text-teal-600 text-lg"></i>
                            @endif
                        </div>
                        <div>
                            <p class="font-bold text-gray-800">{{ $wallet->currency }}</p>
                            <p class="text-xs text-gray-500">{{ $wallet->currency === 'BTC' ? 'Bitcoin' : ($wallet->currency === 'ETH' ? 'Ethereum' : 'Tether') }}</p>
                        </div>
                    </div>
                    <div class="relative group">
                        <button class="p-2 hover:bg-gray-100 rounded-lg">
                            <i class="fas fa-ellipsis-v text-gray-600"></i>
                        </button>
                        <div class="absolute right-0 mt-2 w-40 bg-white border border-gray-200 rounded-lg shadow-lg hidden group-hover:block z-10">
                            <button class="block w-full text-right px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                <i class="fas fa-edit ml-2"></i>ویرایش
                            </button>
                            <button class="block w-full text-right px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                <i class="fas fa-trash ml-2"></i>حذف
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Balance -->
                <div class="mb-4">
                    <p class="text-gray-500 text-xs mb-1">موجودی</p>
                    <p class="text-3xl font-bold text-gray-800">{{ number_format($wallet->balance, 8) }}</p>
                    <p class="text-xs text-gray-400 mt-1">≈ ${{ number_format($wallet->balance * ($wallet->currency === 'BTC' ? 45000 : ($wallet->currency === 'ETH' ? 2500 : 1)), 2) }}</p>
                </div>

                <!-- Address -->
                <div class="mb-4">
                    <p class="text-gray-500 text-xs mb-1">نشانی کیف پول</p>
                    <div class="p-2 bg-gray-50 rounded border border-gray-200">
                        <p class="text-xs text-gray-700 font-mono break-all" id="address-{{ $wallet->id }}">{{ $wallet->wallet_address }}</p>
                    </div>
                </div>

                <!-- Status -->
                <div class="mb-4">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-500">وضعیت</span>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                            <i class="fas fa-check-circle ml-1"></i>فعال
                        </span>
                    </div>
                </div>

                <!-- Actions -->
                <div class="grid grid-cols-2 gap-2">
                    <button class="p-2 bg-indigo-50 text-indigo-600 rounded-lg text-sm font-semibold hover:bg-indigo-100 transition flex items-center justify-center gap-1"
                        onclick="copyAddress('{{ $wallet->wallet_address }}')">
                        <i class="fas fa-copy"></i>کپی نشانی
                    </button>
                    <button class="p-2 bg-gray-50 text-gray-600 rounded-lg text-sm font-semibold hover:bg-gray-100 transition flex items-center justify-center gap-1"
                        onclick="viewOnExplorer('{{ $wallet->wallet_address }}', '{{ $wallet->currency }}')">
                        <i class="fas fa-external-link"></i>مشاهده
                    </button>
                </div>
            </div>
        @endforeach
    </div>
@else
    <div class="bg-white rounded-lg shadow p-12 text-center">
        <i class="fas fa-wallet text-5xl text-gray-300 mb-4"></i>
        <p class="text-gray-500 mb-4">هنوز کیف پولی برای این فروشنده ایجاد نشده است</p>
        <button onclick="openAddWalletModal()" class="bg-indigo-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-indigo-700 transition">
            ایجاد کیف پول اول
        </button>
    </div>
@endif

<!-- Add Wallet Modal -->
<div id="addWalletModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md mx-4">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">اضافه کردن کیف پول جدید</h3>
        
        <form method="POST" action="{{ route('merchant.wallets.store') }}" class="space-y-4">
            @csrf
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">انتخاب ارز</label>
                <select name="currency" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <option value="">انتخاب کنید</option>
                    <option value="BTC">Bitcoin (BTC)</option>
                    <option value="ETH">Ethereum (ETH)</option>
                    <option value="USDT">Tether (USDT)</option>
                </select>
                @error('currency')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex gap-2">
                <button type="submit" class="flex-1 bg-indigo-600 text-white py-2 rounded-lg font-semibold hover:bg-indigo-700 transition">
                    اضافه کردن
                </button>
                <button type="button" onclick="closeAddWalletModal()" class="flex-1 bg-gray-200 text-gray-800 py-2 rounded-lg font-semibold hover:bg-gray-300 transition">
                    انصراف
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function openAddWalletModal() {
        document.getElementById('addWalletModal').classList.remove('hidden');
    }

    function closeAddWalletModal() {
        document.getElementById('addWalletModal').classList.add('hidden');
    }

    function copyAddress(address) {
        navigator.clipboard.writeText(address).then(() => {
            // Show success message
            const toast = document.createElement('div');
            toast.className = 'fixed bottom-4 left-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
            toast.textContent = 'نشانی کپی شد!';
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.remove();
            }, 3000);
        }).catch(err => {
            console.error('Failed to copy:', err);
            alert('خرابی در کپی نشانی');
        });
    }

    function viewOnExplorer(address, currency) {
        let explorerUrl = '';
        
        switch(currency) {
            case 'BTC':
                explorerUrl = `https://blockchair.com/bitcoin/address/${address}`;
                break;
            case 'ETH':
                explorerUrl = `https://etherscan.io/address/${address}`;
                break;
            case 'USDT':
                explorerUrl = `https://etherscan.io/address/${address}`;
                break;
            default:
                alert('اطلاعات Blockchain Explorer برای این ارز موجود نیست');
                return;
        }
        
        window.open(explorerUrl, '_blank');
    }
</script>
@endpush

@endsection
