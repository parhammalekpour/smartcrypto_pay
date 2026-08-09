@extends('layouts.dashboard')

@section('title', 'کاردکس مشتری - CryptoPay')
@section('page-title', 'کاردکس مشتری')
@section('page-subtitle', 'مشاهده فاکتور‌های ثبت شده برای این مشتری')

@section('content')
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-6">
        <div>
            <h3 class="text-xl font-semibold text-gray-800">{{ $customer->user->name ?? $customer->name }}</h3>
            <p class="text-sm text-gray-500">نام کاربری: {{ $customer->user->name ?? $customer->name }}</p>
            <p class="text-sm text-gray-500">ایمیل: {{ $customer->email ?: 'ثبت نشده' }}</p>
            <p class="text-sm text-gray-500">شماره تلفن: {{ $customer->phone ?: 'ثبت نشده' }}</p>
        </div>
        <a href="{{ route('merchant.customers') }}" class="inline-flex items-center gap-2 bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition text-sm">
            <i class="fas fa-arrow-right"></i>بازگشت به مشتریان
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
            <p class="text-sm text-blue-600 font-semibold">تعداد فاکتور</p>
            <p class="text-3xl font-bold text-blue-800 mt-2">{{ $invoices->count() }}</p>
        </div>
        <div class="bg-green-50 rounded-lg p-4 border border-green-200">
            <p class="text-sm text-green-600 font-semibold">مبلغ کل</p>
            <p class="text-3xl font-bold text-green-800 mt-2">{{ \App\Support\NumberHelper::formatCryptoAmount($invoices->sum('amount')) }}</p>
        </div>
        <div class="bg-yellow-50 rounded-lg p-4 border border-yellow-200">
            <p class="text-sm text-yellow-600 font-semibold">فاکتورهای پرداخت نشده</p>
            <p class="text-3xl font-bold text-yellow-800 mt-2">{{ $invoices->where('status', 'pending')->count() }}</p>
        </div>
    </div>

    <div class="overflow-x-auto bg-white rounded-lg shadow-sm border border-gray-200">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700">شماره فاکتور</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700">وضعیت</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700">مبلغ</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700">ارز</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700">تاریخ</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700">عملیات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($invoices as $invoice)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-800">{{ $invoice->invoice_number }}</td>
                        <td class="px-4 py-3">
                            @if($invoice->status === 'pending')
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">
                                    <i class="fas fa-hourglass-end"></i>در انتظار
                                </span>
                            @elseif($invoice->status === 'paid')
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                    <i class="fas fa-check-circle"></i>پرداخت شده
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                                    <i class="fas fa-times-circle"></i>لغو شده
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-800">{{ \App\Support\NumberHelper::formatCryptoAmount($invoice->amount) }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $invoice->currency }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $invoice->created_at->format('Y/m/d') }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ url('/pay/' . $invoice->token) }}" target="_blank" class="text-indigo-600 hover:underline text-xs font-semibold">مشاهده فاکتور</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-gray-500">
                            <i class="fas fa-file-invoice text-4xl mb-4"></i>
                            این مشتری هنوز فاکتوری ندارد.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
