@extends('layouts.dashboard')

@section('title', 'کارتکس مشتری - CryptoPay')
@section('page-title', 'کارتکس مشتری')
@section('page-subtitle', 'نمایش جزئیات فاکتور و فعالیت‌های مشتری')

@section('content')
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4 mb-6 border-b border-gray-200 pb-4">
        <div>
            <h3 class="text-xl font-semibold text-gray-800">{{ $customer->name }}</h3>
            <p class="text-sm text-gray-500">{{ $customer->email }}</p>
            <p class="text-sm text-gray-500">{{ $customer->phone ?? 'تلفن ثبت نشده' }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('merchant.customers') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50 transition">بازگشت به مشتری‌ها</a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-gray-50 rounded-lg p-4">
            <p class="text-sm text-gray-500">کل فاکتورها</p>
            <p class="text-3xl font-bold text-gray-900">{{ $totals['count'] }}</p>
        </div>
        <div class="bg-gray-50 rounded-lg p-4">
            <p class="text-sm text-gray-500">مجموع مبلغ</p>
            <p class="text-3xl font-bold text-gray-900">{{ number_format($totals['total_amount'], 8) }}</p>
        </div>
        <div class="bg-yellow-50 rounded-lg p-4">
            <p class="text-sm text-gray-500">در انتظار پرداخت</p>
            <p class="text-3xl font-bold text-yellow-700">{{ $totals['pending'] }}</p>
        </div>
        <div class="bg-green-50 rounded-lg p-4">
            <p class="text-sm text-gray-500">پرداخت شده</p>
            <p class="text-3xl font-bold text-green-700">{{ $totals['paid'] }}</p>
        </div>
    </div>
</div>

<div class="bg-white rounded-lg shadow p-6">
    <div class="flex items-center justify-between mb-4 border-b border-gray-200 pb-4">
        <div>
            <h3 class="text-lg font-semibold text-gray-800">فاکتورهای مشتری</h3>
            <p class="text-sm text-gray-500">تمام فاکتورهای ایجاد شده برای این مشتری</p>
        </div>
    </div>

    @if($invoices->count())
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 font-semibold text-gray-700">شماره فاکتور</th>
                        <th class="px-4 py-3 font-semibold text-gray-700">مبلغ</th>
                        <th class="px-4 py-3 font-semibold text-gray-700">ارز</th>
                        <th class="px-4 py-3 font-semibold text-gray-700">وضعیت</th>
                        <th class="px-4 py-3 font-semibold text-gray-700">تاریخ</th>
                        <th class="px-4 py-3 font-semibold text-gray-700">عملیات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($invoices as $invoice)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-4 text-gray-800">{{ $invoice->invoice_number }}</td>
                            <td class="px-4 py-4 text-gray-800 font-semibold">{{ number_format($invoice->amount, 8) }}</td>
                            <td class="px-4 py-4 text-gray-600">{{ $invoice->currency }}</td>
                            <td class="px-4 py-4">
                                @if($invoice->status === 'pending')
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">در انتظار</span>
                                @elseif($invoice->status === 'paid')
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">پرداخت شده</span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">لغو شده</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-gray-600">{{ $invoice->created_at->format('Y/m/d H:i') }}</td>
                            <td class="px-4 py-4">
                                <a href="{{ url('/pay/' . $invoice->token) }}" target="_blank" class="text-indigo-600 hover:underline text-sm font-semibold">مشاهده</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="p-12 text-center text-gray-500">
            <i class="fas fa-file-invoice text-4xl mb-4"></i>
            <p>برای این مشتری هنوز فاکتوری ایجاد نشده است.</p>
        </div>
    @endif
</div>
@endsection
