@extends('layouts.dashboard')

@section('title', 'تیکت')
@section('page-title', $ticket->subject)

@section('content')
<div class="bg-white rounded-lg shadow p-6 max-w-3xl mx-auto">
    <div class="mb-6">
        <p class="text-xs text-gray-500">وضعیت: {{ $ticket->status }}</p>
        <p class="text-sm text-gray-400">آخرین فعالیت: {{ $ticket->last_message_at ? $ticket->last_message_at->diffForHumans() : $ticket->created_at->diffForHumans() }}</p>
    </div>

    <div class="space-y-4 mb-6">
        @foreach($messages as $m)
            <div class="p-4 rounded-lg @if($m->sender_type === 'admin') bg-indigo-50 @else bg-gray-100 @endif">
                <p class="text-xs text-gray-600">{{ ucfirst($m->sender_type) }} @if($m->sender_id) — {{ \App\Models\User::find($m->sender_id)->name ?? '' }} @endif</p>
                <p class="mt-2 text-sm text-gray-800">{{ $m->body }}</p>
                <p class="text-xs text-gray-400 mt-2">{{ $m->created_at->diffForHumans() }}</p>
            </div>
        @endforeach
    </div>

    <form method="POST" action="{{ route('tickets.message', $ticket->id) }}">
        @csrf
        <div class="mb-4">
            <textarea name="message" rows="4" class="w-full border p-3 rounded" placeholder="پیام خود را بنویسید"></textarea>
            @error('message') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>
        <div class="flex justify-between items-center">
            <a href="{{ route('tickets.index') }}" class="text-sm text-gray-500">بازگشت</a>
            <button class="bg-indigo-600 text-white py-2 px-4 rounded">ارسال پیام</button>
        </div>
    </form>
</div>
@endsection
