@extends('layouts.admin')

@section('title', __('admin.kyc.page_title'))

@section('content')
<div class="rounded-2xl bg-white p-5 shadow-sm">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-lg font-semibold">{{ __('admin.kyc.review_documents') }}</h2>
        <form method="GET" class="flex items-center gap-2">
            <select name="status" class="rounded-lg border border-slate-300 px-3 py-2">
                <option value="">{{ __('admin.kyc.all') }}</option>
                <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>{{ __('admin.kyc.approved') }}</option>
                <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>{{ __('admin.kyc.pending') }}</option>
            </select>
            <button type="submit" class="rounded-lg bg-indigo-600 px-3 py-2 text-sm text-white">{{ __('admin.kyc.filter') }}</button>
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
                            {{ __('admin.kyc.status') }}: <span class="font-semibold {{ $user->kyc_verified ? 'text-green-600' : 'text-amber-600' }}">{{ $user->kyc_verified ? __('admin.kyc.approved') : __('admin.kyc.pending') }}</span>
                        </p>
                    </div>
                    <div class="flex gap-2">
                        <form method="POST" action="{{ route('admin.kyc.approve', ['user' => $user->id]) }}">
                            @csrf
                            <button type="submit" class="rounded-lg bg-green-600 px-3 py-2 text-sm text-white">{{ __('admin.kyc.approve') }}</button>
                        </form>
                        <form method="POST" action="{{ route('admin.kyc.reject', ['user' => $user->id]) }}">
                            @csrf
                            <button type="submit" class="rounded-lg bg-red-600 px-3 py-2 text-sm text-white">{{ __('admin.kyc.reject') }}</button>
                        </form>
                    </div>
                </div>

                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div>
                        <h3 class="mb-2 text-sm font-semibold">{{ __('admin.kyc.selfie') }}</h3>
                        @if(!empty($user->kyc_selfie))
                            <a href="{{ route('admin.kyc.selfie', ['user' => $user->id]) }}" target="_blank" class="text-sm text-indigo-600">{{ __('admin.kyc.view_selfie') }}</a>
                        @else
                            <p class="text-sm text-slate-500">{{ __('admin.kyc.no_selfie') }}</p>
                        @endif
                    </div>
                    <div>
                        <h3 class="mb-2 text-sm font-semibold">{{ __('admin.kyc.documents') }}</h3>
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
                            <p class="text-sm text-slate-500">{{ __('admin.kyc.no_documents') }}</p>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <p class="text-sm text-slate-500">{{ __('admin.kyc.no_users_with_documents') }}</p>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $users->links() }}
    </div>
</div>
@endsection
