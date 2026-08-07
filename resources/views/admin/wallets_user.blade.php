@extends('layouts.admin')

@section('title', 'کیف پول‌های کاربر')

@section('content')
<div class="rounded-2xl bg-white p-5 shadow-sm">
    <div class="mb-4 flex items-center justify-between">
        <div>
            <h2 class="text-lg font-semibold">کیف پول‌های {{ $user->name }}</h2>
            <p class="text-sm text-slate-500">{{ $user->email }}</p>
        </div>
        <a href="{{ route('admin.users') }}" class="text-sm text-indigo-600">بازگشت به لیست کاربران</a>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b text-right text-slate-500">
                    <th class="py-2">ارز</th>
                    <th class="py-2">آدرس</th>
                    <th class="py-2">موجودی</th>
                    <th class="py-2">ایجاد شده</th>
                    <th class="py-2">عملیات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($wallets as $wallet)
                    <tr class="border-b">
                        <td class="py-3">{{ $wallet->currency }}</td>
                        <td class="py-3 font-mono break-all">{{ $wallet->wallet_address }}</td>
                        <td class="py-3">{{ number_format($wallet->balance, 8) }}</td>
                        <td class="py-3">{{ $wallet->created_at }}</td>
                        <td class="py-3">
                            <form method="POST" action="{{ route('admin.wallets.destroy', ['wallet' => $wallet->id]) }}" onsubmit="return confirm('آیا از حذف این کیف پول اطمینان دارید؟ این عملیات غیرقابل بازگشت است و تراکنش‌های مرتبط حذف خواهند شد.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-lg bg-red-600 px-3 py-2 text-sm text-white">حذف کیف پول</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-4 text-center text-slate-500">هیچ کیف پولی یافت نشد.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $wallets->links() }}
    </div>
</div>
@endsection