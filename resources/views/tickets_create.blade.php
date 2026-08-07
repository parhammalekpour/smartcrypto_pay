@extends('layouts.dashboard')

@section('title', 'ایجاد تیکت جدید')
@section('page-title', 'ایجاد تیکت پشتیبانی')

@section('content')
<div class="bg-white rounded-lg shadow p-6 max-w-2xl mx-auto">
    <form method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="mb-4">
            <label class="block text-sm font-semibold mb-2">موضوع</label>
            <input name="subject" class="w-full border p-3 rounded text-gray-800 placeholder-gray-500 bg-white dark:bg-gray-100" value="{{ old('subject', request('subject')) }}" />
            @error('subject') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="mb-4">
            <label class="block text-sm font-semibold mb-2">پیام</label>
            <textarea name="message" class="w-full border p-3 rounded text-gray-800 placeholder-gray-500 bg-white dark:bg-gray-100" rows="6">{{ old('message', request('message')) }}</textarea>
            @error('message') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="mb-4">
            <label class="block text-sm font-semibold mb-2">ضمیمه (تصویر)</label>
            <div class="flex items-start gap-4">
                <input id="createAttachment" type="file" name="attachment" accept="image/*" class="w-full max-w-xs" />
                <div id="createAttachmentPreviewContainer" class="hidden">
                    <img id="createAttachmentPreview" src="" class="max-w-xs max-h-40 object-contain rounded"/>
                </div>
            </div>
            @error('attachment') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="flex justify-end">
            <button class="bg-indigo-600 text-white py-2 px-4 rounded">ارسال تیکت</button>
        </div>
    </form>
</div>

<script>
    (function(){
        const createInput = document.getElementById('createAttachment');
        const preview = document.getElementById('createAttachmentPreview');
        const previewContainer = document.getElementById('createAttachmentPreviewContainer');
        if (createInput) {
            createInput.addEventListener('change', function(){
                const file = this.files && this.files[0];
                if (file && file.type.startsWith('image/')) {
                    preview.src = URL.createObjectURL(file);
                    previewContainer.classList.remove('hidden');
                } else {
                    preview.src = '';
                    previewContainer.classList.add('hidden');
                }
            });
        }
    })();
</script>
@endsection
