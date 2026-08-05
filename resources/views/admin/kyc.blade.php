@extends('layouts.admin')

@section('title', 'KYC')

@section('content')
<div class="rounded-2xl bg-white p-5 shadow-sm">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-lg font-semibold">بررسی مدارک KYC</h2>
        <form method="GET" class="flex items-center gap-2">
            <select name="status" class="rounded-lg border border-slate-300 px-3 py-2">
                <option value="">همه</option>
                <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>تایید شده</option>
                <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>در انتظار</option>
            </select>
            <button type="submit" class="rounded-lg bg-indigo-600 px-3 py-2 text-sm text-white">فیلتر</button>
        </form>
    </div>

    <div class="space-y-4">
        @forelse($users as $user)
            <div class="rounded-2xl border border-slate-200 p-4">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="font-semibold">{{ $user->name }}</p>
                        <p class="text-sm text-slate-500">{{ $user->email }}</p>
                        <p class="mt-2 text-sm">
                            وضعیت: <span class="font-semibold {{ $user->kyc_verified ? 'text-green-600' : 'text-amber-600' }}">{{ $user->kyc_verified ? 'تایید شده' : 'در انتظار' }}</span>
                        </p>
                    </div>
                    <div class="flex gap-2">
                        <form method="POST" action="{{ route('admin.kyc.approve', ['user' => $user->id]) }}">
                            @csrf
                            <button type="submit" class="rounded-lg bg-green-600 px-3 py-2 text-sm text-white">تایید</button>
                        </form>
                        <form method="POST" action="{{ route('admin.kyc.reject', ['user' => $user->id]) }}">
                            @csrf
                            <button type="submit" class="rounded-lg bg-red-600 px-3 py-2 text-sm text-white">رد</button>
                        </form>
                    </div>
                </div>

                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div>
                        <h3 class="mb-2 text-sm font-semibold">سلفی</h3>
                        @if(!empty($user->kyc_selfie))
                            <a href="{{ route('admin.kyc.selfie', ['user' => $user->id]) }}" target="_blank" class="text-sm text-indigo-600">مشاهده سلفی</a>
                        @else
                            <p class="text-sm text-slate-500">سلفی موجود نیست.</p>
                        @endif
                    </div>
                    <div>
                        <h3 class="mb-2 text-sm font-semibold">مدارک</h3>
                        @if(!empty($user->kyc_documents))
                            <ul class="space-y-1 text-sm">
                                @foreach($user->kyc_documents as $doc)
                                    <li>
                                        <a href="{{ route('admin.kyc.document', ['user' => $user->id, 'filename' => basename($doc)]) }}" target="_blank" class="text-indigo-600">
                                            {{ basename($doc) }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-sm text-slate-500">مدرکی موجود نیست.</p>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <p class="text-sm text-slate-500">کاربری با مدارک KYC وجود ندارد.</p>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $users->links() }}
    </div>
</div>
@endsection
