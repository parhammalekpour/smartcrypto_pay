@extends('layouts.dashboard')

@section('title', 'کلید‌های API - CryptoPay')
@section('page-title', 'کلید‌های API')
@section('page-subtitle', 'مدیریت کلید‌های API برای یکپارچگی‌های سومی')

@section('content')

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow p-6">
        <p class="text-gray-500 text-sm mb-1">کلید عمومی</p>
        <p class="text-2xl font-bold text-gray-800">1</p>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <p class="text-gray-500 text-sm mb-1">کلید خصوصی</p>
        <p class="text-2xl font-bold text-gray-800">1</p>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
        <p class="text-gray-500 text-sm mb-1">Webhook فعال</p>
        <p class="text-2xl font-bold text-gray-800">0</p>
    </div>
</div>

<!-- Create New API Key -->
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <div class="flex items-center justify-between mb-4 pb-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-800">ایجاد کلید API جدید</h3>
    </div>

    <form class="space-y-4">
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">نام کلید</label>
            <input type="text" placeholder="نام توضیحی برای کلید" 
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">دسترسی‌ها</label>
            <div class="space-y-2">
                <label class="flex items-center">
                    <input type="checkbox" class="w-4 h-4 text-indigo-600 rounded">
                    <span class="ml-3 text-sm text-gray-700">خواندن</span>
                </label>
                <label class="flex items-center">
                    <input type="checkbox" class="w-4 h-4 text-indigo-600 rounded">
                    <span class="ml-3 text-sm text-gray-700">نوشتن</span>
                </label>
            </div>
        </div>

        <button type="submit" class="w-full bg-indigo-600 text-white py-2 rounded-lg font-semibold hover:bg-indigo-700 transition">
            <i class="fas fa-plus ml-2"></i>ایجاد کلید
        </button>
    </form>
</div>

<!-- API Keys List -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="p-6 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-800">کلید‌های موجود</h3>
    </div>

    <div class="divide-y divide-gray-200">
        <div class="p-6 hover:bg-gray-50">
            <div class="flex items-center justify-between mb-2">
                <div>
                    <p class="font-semibold text-gray-800">Production Key</p>
                    <p class="text-sm text-gray-500">ایجاد شده: 1403/03/15</p>
                </div>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                    ✓ فعال
                </span>
            </div>
            <div class="bg-gray-100 p-3 rounded font-mono text-xs text-gray-700 mb-3 flex items-center justify-between">
                <span>sk_live_51N****************************</span>
                <button class="text-indigo-600 hover:underline">کپی</button>
            </div>
            <div class="flex gap-2">
                <button class="text-red-600 hover:underline text-sm font-semibold">حذف</button>
            </div>
        </div>
    </div>
</div>

@endsection
