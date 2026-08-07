@extends('layouts.admin')

@section('title', 'داشبورد')

@section('content')
<div class="space-y-6">
    <div class="grid gap-4 md:grid-cols-4">
        <div class="rounded-2xl bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">تعداد کاربران</p>
            <p class="mt-2 text-3xl font-bold">{{ $stats['users'] }}</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">KYC تایید شده</p>
            <p class="mt-2 text-3xl font-bold">{{ $stats['verified_kyc'] }}</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">KYC در انتظار</p>
            <p class="mt-2 text-3xl font-bold">{{ $stats['pending_kyc'] }}</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">تراکنش‌ها</p>
            <p class="mt-2 text-3xl font-bold">{{ $stats['transactions'] }}</p>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-2xl bg-white p-5 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold">کاربران اخیر</h2>
                <a href="{{ route('admin.users') }}" class="text-sm text-indigo-600">مشاهده همه</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b text-right text-slate-500">
                            <th class="py-2">نام</th>
                            <th class="py-2">ایمیل</th>
                            <th class="py-2">نقش</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentUsers as $user)
                            <tr class="border-b">
                                <td class="py-3">{{ $user->name }}</td>
                                <td class="py-3">{{ $user->email }}</td>
                                <td class="py-3">{{ $user->role }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-4 text-center text-slate-500">کاربری وجود ندارد.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-2xl bg-white p-5 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold">KYC در انتظار</h2>
                <a href="{{ route('admin.kyc') }}" class="text-sm text-indigo-600">مشاهده همه</a>
            </div>
            <div class="space-y-3">
                @forelse($pendingKycUsers as $user)
                    <div class="rounded-xl border border-slate-200 p-3">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="font-semibold">{{ $user->name }}</p>
                                <p class="text-sm text-slate-500">{{ $user->email }}</p>
                            </div>
                            <div class="flex gap-2">
                                <a href="{{ route('admin.kyc') }}" class="rounded-lg bg-indigo-600 px-3 py-2 text-sm text-white">بررسی</a>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">در حال حاضر KYC در انتظار وجود ندارد.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-2xl bg-white p-5 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold">تراکنش‌های اخیر</h2>
                <a href="{{ route('admin.transactions') }}" class="text-sm text-indigo-600">مشاهده همه</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b text-right text-slate-500">
                            <th class="py-2">شرح</th>
                            <th class="py-2">مبلغ</th>
                            <th class="py-2">نوع</th>
                            <th class="py-2">وضعیت</th>
                            <th class="py-2">تاریخ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentTransactions as $transaction)
                            <tr class="border-b">
                                <td class="py-3">
                                    {{ $transaction->display_title }}
                                    <div class="text-xs text-slate-400 mt-1">
                                        {{ $transaction->currency }} • {{ $transaction->created_at->format('Y/m/d H:i') }}
                                        @if($transaction->reference)
                                            • {{ $transaction->reference }}
                                        @elseif($transaction->paymentRequest && $transaction->paymentRequest->invoice_number)
                                            • INV-{{ $transaction->paymentRequest->invoice_number }}
                                        @endif
                                    </div>
                                </td>
                                <td class="py-3">{{ number_format($transaction->amount, 8) }} {{ $transaction->currency }}</td>
                                <td class="py-3">{{ ucfirst($transaction->type) }}</td>
                                <td class="py-3">{{ ucfirst($transaction->status) }}</td>
                                <td class="py-3">{{ $transaction->created_at->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-4 text-center text-slate-500">تراکنشی ثبت نشده است.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-2xl bg-white p-5 shadow-sm">
            <div class="mb-4">
                <h2 class="text-lg font-semibold">فعالیت اخیر</h2>
            </div>
            <div class="space-y-3">
                @forelse($activity as $item)
                    <div class="rounded-xl border border-slate-200 p-3">
                        <p class="font-semibold">{{ $item->action }}</p>
                        <p class="text-sm text-slate-500">{{ $item->actor_name ?? 'سیستم' }} → {{ $item->user_name ?? 'کاربر' }}</p>
                        <p class="mt-1 text-xs text-slate-400">{{ $item->created_at }}</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">هنوز فعالیتی ثبت نشده است.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
