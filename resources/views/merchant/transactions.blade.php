@extends('layouts.dashboard')

@section('title', 'تراکنش‌های من - CryptoPay')
@section('page-title', 'تراکنش‌های من')
@section('page-subtitle', 'مشاهده تاریخچه تمام تراکنش‌های فروشنده')

@section('content')

<!-- Stats -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow p-6">
        <p class="text-gray-500 text-sm mb-2">کل تراکنش‌ها</p>
        <p class="text-3xl font-bold text-gray-800">{{ $totalCount }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <p class="text-gray-500 text-sm mb-2">تکمیل شده</p>
        <p class="text-3xl font-bold text-green-600">{{ $completedCount }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <p class="text-gray-500 text-sm mb-2">در انتظار</p>
        <p class="text-3xl font-bold text-yellow-600">{{ $pendingCount }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <p class="text-gray-500 text-sm mb-2">ناموفق</p>
        <p class="text-3xl font-bold text-red-600">{{ $failedCount }}</p>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <form method="GET" action="{{ route('merchant.transactions') }}" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">جستجو</label>
                <input type="text" name="search" value="{{ request('search') }}" 
                    placeholder="شناسه یا توضیح..." 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-gray-900">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">نوع</label>
                <select name="type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <option value="">همه</option>
                    <option value="deposit" {{ request('type') === 'deposit' ? 'selected' : '' }}>دریافت</option>
                    <option value="transfer" {{ request('type') === 'transfer' ? 'selected' : '' }}>انتقال</option>
                    <option value="withdrawal" {{ request('type') === 'withdrawal' ? 'selected' : '' }}>برداشت</option>
                    <option value="payment" {{ request('type') === 'payment' ? 'selected' : '' }}>پرداخت</option>
                    <option value="invoice" {{ request('type') === 'invoice' ? 'selected' : '' }}>فاکتور</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">وضعیت</label>
                <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <option value="">همه</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>تکمیل شده</option>
                    <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>پرداخت شده</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>در انتظار</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>ناموفق</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>لغو شده</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 bg-indigo-600 text-white py-2 rounded-lg font-semibold hover:bg-indigo-700 transition">
                    <i class="fas fa-search ml-2"></i>جستجو
                </button>
                <a href="{{ route('merchant.transactions') }}" class="flex-1 bg-gray-200 text-gray-800 py-2 rounded-lg font-semibold hover:bg-gray-300 transition text-center">
                    <i class="fas fa-times ml-2"></i>پاک کن
                </a>
            </div>
        </div>
    </form>
</div>

<!-- Transactions & Payments Table -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="p-6 border-b border-gray-200 flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-800">تراکنش‌ها و فاکتورها</h3>
        <span class="text-sm text-gray-600">نمایش {{ ($transactions->count() + $paymentRequests->count()) }} مورد از {{ $transactions->total() + $paymentRequests->count() }}</span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 text-right font-semibold text-gray-700 text-sm">شناسه</th>
                    <th class="px-6 py-3 text-right font-semibold text-gray-700 text-sm">نوع</th>
                    <th class="px-6 py-3 text-right font-semibold text-gray-700 text-sm">مشتری/منبع</th>
                    <th class="px-6 py-3 text-right font-semibold text-gray-700 text-sm">توضیح</th>
                    <th class="px-6 py-3 text-right font-semibold text-gray-700 text-sm">مبلغ</th>
                    <th class="px-6 py-3 text-right font-semibold text-gray-700 text-sm">وضعیت</th>
                    <th class="px-6 py-3 text-right font-semibold text-gray-700 text-sm">تاریخ</th>
                    <th class="px-6 py-3 text-right font-semibold text-gray-700 text-sm">عملیات</th>
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
                                    <i class="fas fa-arrow-down ml-1"></i>دریافت
                                </span>
                            @elseif($transaction->type === 'transfer')
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                                    <i class="fas fa-arrow-right ml-1"></i>انتقال
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                                    <i class="fas fa-arrow-up ml-1"></i>برداشت
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($transaction->type === 'deposit')
                                <span class="text-gray-700 font-semibold">{{ $transaction->sender->name ?? 'سیستم' }}</span>
                            @else
                                <span class="text-gray-600">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-gray-600 text-sm">{{ $transaction->description ?? '-' }}</td>
                        <td class="px-6 py-4 font-semibold {{ $transaction->type === 'deposit' ? 'text-green-600' : 'text-red-600' }}">
                            {{ $transaction->type === 'deposit' ? '+' : '-' }}{{ number_format($transaction->amount, 8) }} {{ $transaction->currency }}
                        </td>
                        <td class="px-6 py-4">
                            @if($transaction->status === 'completed')
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                    ✓ تکمیل شده
                                </span>
                            @elseif($transaction->status === 'pending')
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">
                                    ⏱ در انتظار
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                                    ✗ ناموفق
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
                                <i class="fas fa-file-invoice ml-1"></i>فاکتور
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-gray-700 font-semibold">{{ $payment->recipient->name ?? 'نامشخص' }}</span>
                        </td>
                        <td class="px-6 py-4 text-gray-600 text-sm">فاکتور #{{ $payment->invoice_number }}</td>
                        <td class="px-6 py-4 font-semibold text-blue-600">{{ number_format($payment->amount, 2) }} {{ $payment->currency }}</td>
                        <td class="px-6 py-4">
                            @if($payment->status === 'paid')
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                    ✓ پرداخت شده
                                </span>
                            @elseif($payment->status === 'pending')
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">
                                    ⏱ در انتظار
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                                    ✗ لغو شده
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
                            هیچ تراکنشی برای نمایش وجود ندارد
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
            <h3 class="text-lg font-semibold text-gray-800">جزئیات تراکنش</h3>
            <button onclick="closeDetailModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <div id="modalContent" class="space-y-4">
            <!-- Content will be loaded here -->
        </div>

        <div class="mt-8 flex gap-2 border-t border-gray-200 pt-4">
            <button onclick="closeDetailModal()" class="flex-1 bg-gray-200 text-gray-800 py-2 rounded-lg font-semibold hover:bg-gray-300 transition">
                بستن
            </button>
            <button onclick="downloadTransaction()" class="flex-1 bg-indigo-600 text-white py-2 rounded-lg font-semibold hover:bg-indigo-700 transition">
                <i class="fas fa-download ml-2"></i>دانلود
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
                        <p class="text-gray-500 text-sm">شناسه</p>
                        <p class="font-semibold text-gray-800">${cells[0].textContent.trim()}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">نوع</p>
                        <p class="font-semibold text-gray-800">${cells[1].textContent.trim()}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">توضیح</p>
                        <p class="font-semibold text-gray-800">${cells[2].textContent.trim()}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">مبلغ</p>
                        <p class="font-semibold text-gray-800">${cells[3].textContent.trim()}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">وضعیت</p>
                        <p class="font-semibold text-gray-800">${cells[4].textContent.trim()}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">تاریخ</p>
                        <p class="font-semibold text-gray-800">${cells[5].textContent.trim()}</p>
                    </div>
                </div>
                <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                    <p class="text-sm text-blue-800">
                        <i class="fas fa-info-circle ml-2"></i>
                        این تراکنش مربوط به تمام فعالیت‌های مالی شما است. شامل: دریافت، انتقال، برداشت، فاکتور و پرداخت‌ها
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
        alert('قابلیت دانلود برای پس‌تر آماده می‌شود');
    }
</script>
@endpush

@endsection
