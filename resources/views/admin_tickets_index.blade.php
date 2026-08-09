@extends('layouts.admin')

@section('title', __('admin_tickets.title'))
@section('page-title', __('admin_tickets.page_title'))

@section('content')
<div class="rounded-[24px] border border-slate-200 bg-white p-6 shadow-sm">
    <div class="mb-4 flex items-center justify-between">
        <h3 class="text-lg font-semibold text-slate-900">{{ __('admin_tickets.all_tickets') }}</h3>
    </div>

    @if($tickets->isEmpty())
        <div class="py-12 text-center">
            <i class="fas fa-inbox mb-4 text-4xl text-slate-300"></i>
            <p class="text-slate-500">{{ __('admin_tickets.no_tickets') }}</p>
        </div>
    @else
        <div class="divide-y divide-slate-200">
            @foreach($tickets as $ticket)
                <a href="{{ route('admin.tickets.show', $ticket->id) }}" class="flex items-center justify-between p-4 transition hover:bg-slate-50">
                    <div>
                        <p class="font-semibold text-slate-900">{{ $ticket->subject }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ __('admin_tickets.requester_label', ['name' => $ticket->user ? $ticket->user->name : ($ticket->merchant ? $ticket->merchant->name : __('admin_tickets.unknown_requester'))]) }}</p>
                    </div>
                    <div class="text-xs text-slate-400">{{ $ticket->last_message_at ? $ticket->last_message_at->diffForHumans() : $ticket->created_at->diffForHumans() }}</div>
                </a>
            @endforeach
        </div>
    @endif
</div>
@endsection
