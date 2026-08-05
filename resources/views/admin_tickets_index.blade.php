@extends('layouts.dashboard')

@section('title', 'تیکت‌ها (مدیریت)')
@section('page-title', 'تیکت‌های کاربران')

@section('content')
<div class="bg-white rounded-lg shadow p-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold">همه تیکت‌ها</h3>
    </div>

    @if($tickets->isEmpty())
        <div class="text-center py-12">
            <i class="fas fa-inbox text-4xl text-gray-300 mb-4"></i>
            <p class="text-gray-500">تیکتی وجود ندارد</p>
        </div>
    @else
        <div class="divide-y divide-gray-200">
            @foreach($tickets as $ticket)
                <a href="{{ route('admin.tickets.show', $ticket->id) }}" class="block p-4 hover:bg-gray-50 flex justify-between items-center">
                    <div>
                        <p class="font-semibold text-gray-800">{{ $ticket->subject }}</p>
                        <p class="text-xs text-gray-500 mt-1">درخواست‌دهنده: {{ $ticket->user ? $ticket->user->name : ($ticket->merchant ? $ticket->merchant->name : 'ناشناخته') }}</p>
                    </div>
                    <div class="text-xs text-gray-400">{{ $ticket->last_message_at ? $ticket->last_message_at->diffForHumans() : $ticket->created_at->diffForHumans() }}</div>
                </a>
            @endforeach
        </div>
    @endif
</div>
@endsection
