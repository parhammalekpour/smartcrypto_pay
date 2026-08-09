@extends('layouts.admin')

@section('title', __('admin.wallets.page_title'))

@section('content')
<div class="rounded-2xl bg-white p-5 shadow-sm">
    <div class="mb-4 flex items-center justify-between">
        <div>
            <h2 class="text-lg font-semibold">{{ __('admin.wallets.list_title', ['name' => $user->name]) }}</h2>
            <p class="text-sm text-slate-500">{{ $user->email }}</p>
        </div>
        <a href="{{ route('admin.users') }}" class="text-sm text-indigo-600">{{ __('admin.wallets.back_to_users') }}</a>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b text-right text-slate-500">
                    <th class="py-2">{{ __('admin.wallets.currency') }}</th>
                    <th class="py-2">{{ __('admin.wallets.address') }}</th>
                    <th class="py-2">{{ __('admin.wallets.balance') }}</th>
                    <th class="py-2">{{ __('admin.wallets.created_at') }}</th>
                    <th class="py-2">{{ __('admin.wallets.actions') }}</th>
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
                            <form method="POST" action="{{ route('admin.wallets.destroy', ['wallet' => $wallet->id]) }}" onsubmit="return confirm('{{ __('admin.wallets.delete_confirm') }}');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-lg bg-red-600 px-3 py-2 text-sm text-white">{{ __('admin.wallets.delete_wallet') }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-4 text-center text-slate-500">{{ __('admin.wallets.no_wallets') }}</td>
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