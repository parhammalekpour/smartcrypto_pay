@extends('layouts.admin')

@section('title', __('admin.users.page_title'))

@section('content')
<div class="rounded-2xl bg-white p-5 shadow-sm">
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-lg font-semibold">{{ __('admin.users.list_title') }}</h2>
        <span class="text-sm text-slate-500">{{ __('admin.users.total_users_count', ['count' => $users->total()]) }}</span>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b text-right text-slate-500">
                    <th class="py-2">{{ __('admin.users.name') }}</th>
                    <th class="py-2">{{ __('admin.users.email') }}</th>
                    <th class="py-2">{{ __('admin.users.role') }}</th>
                    <th class="py-2">{{ __('admin.users.email_status') }}</th>
                    <th class="py-2">{{ __('admin.users.registered_at') }}</th>
                    <th class="py-2">{{ __('admin.users.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr class="border-b">
                        <td class="py-3">{{ $user->name }}</td>
                        <td class="py-3">{{ $user->email }}</td>
                        <td class="py-3">{{ $user->role }}</td>
                        <td class="py-3">{{ $user->email_verified_at ? __('admin.users.verified') : __('admin.users.unverified') }}</td>
                        <td class="py-3">{{ $user->created_at }}</td>
                        <td class="py-3">
                            @if(!$user->isAdmin() && $user->id !== auth()->id())
                                <div class="flex gap-2">
                                    <a href="{{ route('admin.users.wallets', ['user' => $user->id]) }}" class="rounded-lg bg-indigo-600 px-3 py-2 text-sm text-white">{{ __('admin.users.view_wallets') }}</a>
                                    <form method="POST" action="{{ route('admin.users.delete', ['user' => $user->id]) }}" onsubmit="return confirm('{{ __('admin.users.delete_confirm') }}')">
                                        @csrf
                                        <button type="submit" class="rounded-lg bg-red-600 px-3 py-2 text-sm text-white">{{ __('admin.users.delete_user') }}</button>
                                    </form>
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-4 text-center text-slate-500">{{ __('admin.users.no_users') }}</td>
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
