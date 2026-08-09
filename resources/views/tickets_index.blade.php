@extends('layouts.dashboard')

@section('title', __('tickets.title'))
@section('page-title', __('tickets.page_title'))

@section('content')
<div class="rounded-[24px] border border-slate-200 bg-white p-6 shadow-sm">
    <div class="mb-4 flex items-center justify-between">
        <h3 class="text-lg font-semibold text-slate-900">{{ __('tickets.your_tickets') }}</h3>
        <a href="{{ route('tickets.create') }}" class="font-semibold text-indigo-600 transition hover:text-indigo-700">{{ __('tickets.create_new') }}</a>
    </div>

    @if($tickets->isEmpty())
        <div class="py-12 text-center">
            <i class="fas fa-inbox mb-4 text-4xl text-slate-300"></i>
            <p class="text-slate-500">{{ __('tickets.no_tickets') }}</p>
        </div>
    @else
        <div class="divide-y divide-slate-200">
            @foreach($tickets as $ticket)
                <a href="{{ route('tickets.show', $ticket->id) }}" class="flex items-center justify-between p-4 transition hover:bg-slate-50">
                    <div>
                        <p class="font-semibold text-slate-900">{{ $ticket->subject }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ __('tickets.status_label', ['status' => $ticket->status]) }}</p>
                    </div>
                    <div class="text-xs text-slate-400">{{ $ticket->last_message_at ? $ticket->last_message_at->diffForHumans() : $ticket->created_at->diffForHumans() }}</div>
                </a>
            @endforeach
        </div>
    @endif
</div>
@endsection
