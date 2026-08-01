@extends('layouts.dashboard')

@section('title', 'مشتریان - CryptoPay')
@section('page-title', 'مشتریان')
@section('page-subtitle', 'لیست مشتری‌ها و فاکتورهای مرتبط با آن‌ها')

@section('content')
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-200">
        <div class="bg-indigo-100 p-2 rounded-lg">
            <i class="fas fa-user-plus text-indigo-600"></i>
        </div>
        <h3 class="text-lg font-semibold text-gray-800">افزودن مشتری جدید</h3>
    </div>

    @if(session('success'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">
            ✅ {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
            @foreach($errors->all() as $error)
                ❌ {{ $error }}<br>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('merchant.customers.store') }}" class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        @csrf
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">نام مشتری</label>
            <input type="text" name="name" value="{{ old('name') }}" required placeholder="نام مشتری"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm text-gray-900">
        </div>
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">ایمیل مشتری</label>
            <input type="email" name="email" value="{{ old('email') }}" required placeholder="example@domain.com"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm text-gray-900">
        </div>
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">شماره تلفن</label>
            <input type="text" name="phone" value="{{ old('phone') }}" placeholder="0912xxxxxxx"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm text-gray-900">
        </div>
        <div class="lg:col-span-2">
            <label class="block text-sm font-semibold text-gray-700 mb-2">رمز عبور (اختیاری)</label>
            <input type="password" name="password" placeholder="حداقل 8 کاراکتر"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm text-gray-900">
            <p class="text-xs text-gray-500 mt-2">اگر رمز وارد نکنید، یک رمز عبور موقت تولید می‌شود.</p>
        </div>
        <div class="lg:col-span-1 flex items-end">
            <button type="submit" class="w-full bg-indigo-600 text-white py-2 rounded-lg font-semibold hover:bg-indigo-700 transition">
                <i class="fas fa-plus ml-2"></i>افزودن مشتری
            </button>
        </div>
    </form>
</div>

<div class="bg-white rounded-lg shadow p-6">
    <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-200">
        <div>
            <h3 class="text-lg font-semibold text-gray-800">لیست مشتری‌ها</h3>
            <p class="text-sm text-gray-500 mt-1">نام، ایمیل، شماره تلفن و خلاصه فاکتورهای مشتری</p>
        </div>
        <form method="GET" action="{{ route('merchant.customers') }}" class="flex items-center gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="جستجو بر اساس نام، ایمیل یا تلفن"
                class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm text-gray-900">
            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition text-sm">جستجو</button>
        </form>
    </div>

    @if($customers->count())
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 font-semibold text-gray-700">نام</th>
                        <th class="px-4 py-3 font-semibold text-gray-700">ایمیل</th>
                        <th class="px-4 py-3 font-semibold text-gray-700">تلفن</th>
                        <th class="px-4 py-3 font-semibold text-gray-700">فاکتورهای مشتری</th>
                        <th class="px-4 py-3 font-semibold text-gray-700">در انتظار</th>
                        <th class="px-4 py-3 font-semibold text-gray-700">پرداخت شده</th>
                        <th class="px-4 py-3 font-semibold text-gray-700">لغو شده</th>
                        <th class="px-4 py-3 font-semibold text-gray-700">عملیات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($customers as $customer)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-4 text-gray-800">{{ $customer->name }}</td>
                            <td class="px-4 py-4 text-gray-600">{{ $customer->email }}</td>
                            <td class="px-4 py-4 text-gray-600">{{ $customer->phone ?? '-' }}</td>
                            <td class="px-4 py-4 text-gray-800 font-semibold">{{ $customer->merchant_total_invoices_count ?? 0 }}</td>
                            <td class="px-4 py-4 text-yellow-700">{{ $customer->merchant_pending_count ?? 0 }}</td>
                            <td class="px-4 py-4 text-green-700">{{ $customer->merchant_paid_count ?? 0 }}</td>
                            <td class="px-4 py-4 text-red-700">{{ $customer->merchant_cancelled_count ?? 0 }}</td>
                            <td class="px-4 py-4">
                                <a href="{{ route('merchant.customers.show', $customer->id) }}" class="text-indigo-600 hover:underline text-sm font-semibold">کارتکس</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $customers->links() }}
        </div>
    @else
        <div class="p-12 text-center text-gray-500">
            <i class="fas fa-user-friends text-4xl mb-4"></i>
            <p>مشتری‌ای یافت نشد. ابتدا می‌توانید مشتری جدیدی اضافه کنید.</p>
        </div>
    @endif
</div>
@endsection
