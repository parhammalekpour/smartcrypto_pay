@extends('layouts.dashboard')

@section('title', __('merchant.customers.page_title') . ' - CryptoPay')
@section('page-title', __('merchant.customers.manage_customers'))
@section('page-subtitle', __('merchant.customers.page_subtitle'))

@section('content')
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-200">
        <div class="bg-indigo-100 p-2 rounded-lg">
            <i class="fas fa-user-friends text-indigo-600"></i>
        </div>
        <h3 class="text-lg font-semibold text-gray-800">{{ __('merchant.customers.add_new_customer') }}</h3>
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

    <form method="POST" action="{{ route('merchant.customers.store') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @csrf
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('merchant.customers.customer_username') }}</label>
            <input type="text" name="username" required placeholder="username" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm text-gray-900">
        </div>
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('merchant.customers.customer_email') }}</label>
            <input type="email" name="email" required placeholder="example@domain.com" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm text-gray-900">
        </div>
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('merchant.customers.phone') }}</label>
            <input type="text" name="phone" placeholder="0912xxxxxxx" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm text-gray-900">
        </div>
        <div class="md:col-span-3">
            <button type="submit" class="w-full md:w-auto bg-indigo-600 text-white py-2 px-6 rounded-lg font-semibold hover:bg-indigo-700 transition">
                <i class="fas fa-plus ml-2"></i>{{ __('merchant.customers.add_customer') }}
            </button>
        </div>
        <div class="md:col-span-3 text-sm text-gray-500">
            {{ __('merchant.customers.username_email_hint') }}
        </div>
    </form>
</div>

<div class="bg-white rounded-lg shadow p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h3 class="text-lg font-semibold text-gray-800">{{ __('merchant.customers.customer_list') }}</h3>
            <p class="text-sm text-gray-500">{{ __('merchant.customers.customer_list_subtitle') }}</p>
        </div>
    </div>

    @if($customers->count())
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-right font-semibold text-gray-700">{{ __('merchant.customers.name') }}</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-700">{{ __('merchant.customers.email') }}</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-700">{{ __('merchant.customers.phone') }}</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-700">{{ __('merchant.customers.invoices') }}</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-700">{{ __('merchant.customers.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($customers as $customer)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-gray-800">{{ $customer->user->name ?? $customer->name }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $customer->email ?: '—' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $customer->phone ?: '—' }}</td>
                            <td class="px-4 py-3 text-gray-800">{{ $customer->payment_requests_count }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('merchant.customers.show', $customer) }}" class="text-indigo-600 hover:underline text-xs font-semibold">
                                        {{ __('merchant.customers.view_cardex') }}
                                    </a>
                                    <a href="{{ route('merchant.customers.edit', $customer) }}" class="text-yellow-600 hover:underline text-xs font-semibold">
                                        {{ __('merchant.customers.edit') }}
                                    </a>
                                    <form method="POST" action="{{ route('merchant.customers.destroy', $customer) }}" class="inline" onsubmit="return confirm('{{ __('merchant.customers.confirm_delete') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline text-xs font-semibold">
                                            {{ __('merchant.customers.delete') }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $customers->links() }}
        </div>
    @else
        <div class="text-center py-12 text-gray-500">
            <i class="fas fa-user-friends text-4xl mb-4"></i>
            <p>{{ __('merchant.customers.no_customers') }}</p>
        </div>
    @endif
</div>
@endsection
