@extends('layouts.dashboard')

@section('title', 'فاکتورها - CryptoPay')
@section('page-title', 'فاکتورهای منتظر')
@section('page-subtitle', 'درخواست‌های پرداختی که در انتظار تسویه هستند')

@section('content')

<!-- Auto-refresh script -->
<script>
    // Check for invoice updates every 3 seconds
    let lastInvoiceCount = {{ $invoices->total() }};
    
    setInterval(() => {
        fetch(window.location.href, { headers: { 'X-Requested-With': 'fetch' } })
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const newDoc = parser.parseFromString(html, 'text/html');
                const newCount = newDoc.querySelectorAll('tbody tr[data-invoice-item]').length;
                
                if (newCount < lastInvoiceCount) {
                    // Invoice was paid - reload page
                    window.location.reload();
                } else if (newCount !== lastInvoiceCount) {
                    // Invoice count changed - reload page
                    window.location.reload();
                }
            })
            .catch(error => console.log('Auto-refresh check:', error));
    }, 3000); // Check every 3 seconds
</script>

<div class="bg-white rounded-lg shadow p-6">
    <!-- Header Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-yellow-50 rounded-lg p-4 border border-yellow-200">
            <p class="text-yellow-600 text-sm font-semibold">درخواست‌های منتظر</p>
            <p class="text-2xl font-bold text-yellow-800 mt-2">{{ $invoices->total() }}</p>
        </div>
        <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
            <p class="text-blue-600 text-sm font-semibold">کل مبلغ منتظر</p>
            <p class="text-2xl font-bold text-blue-800 mt-2">${{ number_format($invoices->sum('amount'), 2) }}</p>
        </div>
        <div class="bg-indigo-50 rounded-lg p-4 border border-indigo-200">
            <p class="text-indigo-600 text-sm font-semibold">اقدام مورد نیاز</p>
            <p class="text-2xl font-bold text-indigo-800 mt-2">{{ $invoices->total() }}</p>
        </div>
    </div>

    <!-- Invoices Table -->
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700">شماره فاکتور</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700">گیرنده</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700">مبلغ</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700">ارز</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700">تاریخ</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($invoices as $invoice)
                    <tr class="hover:bg-gray-50" data-invoice-item>
                        <td class="px-4 py-3">
                            <span class="font-semibold text-gray-800">{{ $invoice->invoice_number }}</span>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $invoice->recipient->name ?? 'نامشخص' }}</td>
                        <td class="px-4 py-3 text-gray-800 font-semibold">{{ number_format($invoice->amount, 8) }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                                {{ $invoice->currency }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $invoice->created_at->diffForHumans() }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ url('/pay/' . $invoice->token) }}" target="_blank" class="text-indigo-600 hover:underline font-semibold text-xs">
                                <i class="fas fa-external-link-alt ml-1"></i>ارسال پیوند
                            </a>

                            @if($invoice->status === 'pending')
                                <form action="{{ route('payments.cancel', $invoice->id) }}" method="POST" class="inline-block ml-2" onsubmit="return confirm('آیا از حذف/لغو این فاکتور مطمئن هستید؟');">
                                    @csrf
                                    <button type="submit" class="text-red-600 hover:underline font-semibold text-xs">
                                        <i class="fas fa-trash ml-1"></i>حذف
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center">
                            <i class="fas fa-inbox text-4xl text-gray-300 mb-4"></i>
                            <p class="text-gray-500 mb-2">فاکتوری در انتظار وجود ندارد</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $invoices->links() }}
    </div>
</div>
@endsection
