@extends('layouts.dashboard')

@section('title', 'ویرایش مشتری - CryptoPay')
@section('page-title', 'ویرایش مشتری')
@section('page-subtitle', 'به‌روزرسانی اطلاعات مشتری')

@section('content')
<div class="bg-white rounded-lg shadow p-6">
    <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-200">
        <div class="bg-yellow-100 p-2 rounded-lg">
            <i class="fas fa-user-edit text-yellow-600"></i>
        </div>
        <h3 class="text-lg font-semibold text-gray-800">ویرایش مشتری</h3>
    </div>

    @if($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
            @foreach($errors->all() as $error)
                ❌ {{ $error }}<br>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('merchant.customers.update', $customer) }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">نام کاربری مشتری</label>
            <input type="text" name="username" required value="{{ old('username', $customer->user->name ?? $customer->name) }}" placeholder="username" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm text-gray-900">
        </div>
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">ایمیل مشتری</label>
            <input type="email" name="email" required value="{{ old('email', $customer->email) }}" placeholder="example@domain.com" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm text-gray-900">
        </div>
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">شماره تلفن</label>
            <input type="text" name="phone" value="{{ old('phone', $customer->phone) }}" placeholder="0912xxxxxxx" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm text-gray-900">
        </div>

        <div class="md:col-span-3">
            <button type="submit" class="w-full md:w-auto bg-yellow-600 text-white py-2 px-6 rounded-lg font-semibold hover:bg-yellow-700 transition">
                <i class="fas fa-save ml-2"></i>ذخیره تغییرات
            </button>
            <a href="{{ route('merchant.customers') }}" class="inline-flex items-center justify-center mt-3 md:mt-0 ml-3 px-6 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50 transition">
                بازگشت به لیست مشتریان
            </a>
        </div>

        <div class="md:col-span-3 text-sm text-gray-500">
            نام کاربری و ایمیل باید متعلق به یک کاربر موجود در سیستم باشد. مشتری تکراری قابل ثبت نیست.
        </div>
    </form>
</div>
@endsection
