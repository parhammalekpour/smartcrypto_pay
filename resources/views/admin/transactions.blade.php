@extends('layouts.admin')

@section('title', 'تراکنش‌ها')

@section('content')
<div class="rounded-2xl bg-white p-5 shadow-sm">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-lg font-semibold">مدیریت تراکنش‌ها</h2>
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="جستجو" class="rounded-lg border border-slate-300 px-3 py-2">
            <select name="status" class="rounded-lg border border-slate-300 px-3 py-2">
                <option value="">همه وضعیت‌ها</option>
                @foreach($statuses as $status)
                    <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ $status }}</option>
                @endforeach
            </select>
            <button type="submit" class="rounded-lg bg-indigo-600 px-3 py-2 text-sm text-white">فیلتر</button>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b text-right text-slate-500">
                    <th class="py-2">شناسه</th>
                    <th class="py-2">مبلغ</th>
                    <th class="py-2">نوع</th>
                    <th class="py-2">وضعیت</th>
                    <th class="py-2">ارسال کننده</th>
                    <th class="py-2">دریافت کننده</th>
                    <th class="py-2">توضیح</th>
                    <th class="py-2">عملیات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $transaction)
                    <tr class="border-b">
                        <td class="py-3">{{ $transaction->id }}</td>
                        <td class="py-3">{{ $transaction->amount }}</td>
                        <td class="py-3">{{ $transaction->type }}</td>
                        <td class="py-3">{{ $transaction->status }}</td>
                        <td class="py-3">{{ $transaction->sender->name ?? '—' }}</td>
                        <td class="py-3">{{ $transaction->recipient->name ?? '—' }}</td>
                        <td class="py-3">{{ $transaction->description }}</td>
                        <td class="py-3">
                            @if($transaction->status !== 'cancelled')
                                <form method="POST" action="{{ route('admin.transactions.cancel', ['transaction' => $transaction->id]) }}">
                                    @csrf
                                    <button type="submit" class="rounded-lg bg-red-600 px-3 py-2 text-sm text-white">لغو تراکنش</button>
                                </form>
                            @else
                                <span class="text-sm text-slate-500">لغو شده</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="py-4 text-center text-slate-500">تراکنشی وجود ندارد.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $transactions->links() }}
    </div>
</div>
@endsection
