@extends('layouts.dashboard')

@section('title', 'ایجاد تیکت جدید')
@section('page-title', 'ایجاد تیکت پشتیبانی')

@section('content')
<div class="bg-white rounded-lg shadow p-6 max-w-2xl mx-auto">
    <form method="POST" action="{{ route('tickets.store') }}">
        @csrf
        <div class="mb-4">
            <label class="block text-sm font-semibold mb-2">موضوع</label>
            <input name="subject" class="w-full border p-3 rounded" value="{{ old('subject') }}" />
            @error('subject') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="mb-4">
            <label class="block text-sm font-semibold mb-2">پیام</label>
            <textarea name="message" class="w-full border p-3 rounded" rows="6">{{ old('message') }}</textarea>
            @error('message') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="flex justify-end">
            <button class="bg-indigo-600 text-white py-2 px-4 rounded">ارسال تیکت</button>
        </div>
    </form>
</div>
@endsection
