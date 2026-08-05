@extends('layouts.admin')

@section('title', 'کاربران')

@section('content')
<div class="rounded-2xl bg-white p-5 shadow-sm">
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-lg font-semibold">لیست کاربران</h2>
        <span class="text-sm text-slate-500">{{ $users->total() }} کاربر</span>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b text-right text-slate-500">
                    <th class="py-2">نام</th>
                    <th class="py-2">ایمیل</th>
                    <th class="py-2">نقش</th>
                    <th class="py-2">وضعیت ایمیل</th>
                    <th class="py-2">تاریخ ثبت‌نام</th>
                    <th class="py-2">عملیات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr class="border-b">
                        <td class="py-3">{{ $user->name }}</td>
                        <td class="py-3">{{ $user->email }}</td>
                        <td class="py-3">{{ $user->role }}</td>
                        <td class="py-3">{{ $user->email_verified_at ? 'تایید شده' : 'تایید نشده' }}</td>
                        <td class="py-3">{{ $user->created_at }}</td>
                        <td class="py-3">
                            @if(!$user->isAdmin() && $user->id !== auth()->id())
                                <form method="POST" action="{{ route('admin.users.delete', ['user' => $user->id]) }}" onsubmit="return confirm('آیا از حذف این کاربر مطمئن هستید؟')">
                                    @csrf
                                    <button type="submit" class="rounded-lg bg-red-600 px-3 py-2 text-sm text-white">حذف کاربر</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-4 text-center text-slate-500">کاربری وجود ندارد.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $users->links() }}
    </div>
</div>
@endsection
